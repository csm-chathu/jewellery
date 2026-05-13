<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(private SmsService $sms) {}

    /**
     * GET /api/sms-logs
     * List SMS history with filters.
     */
    public function logs(Request $request)
    {
        $query = SmsLog::with('sentBy:id,name')
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->from_date, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to_date, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($query);
    }

    /**
     * POST /api/sms/send-custom
     * Send a custom SMS to selected customers or manually entered numbers.
     */
    public function sendCustom(Request $request)
    {
        $data = $request->validate([
            'contacts'      => 'required|array|min:1',
            'contacts.*'    => 'string',
            'message'       => 'required|string|max:621',
            'sender_id'     => 'nullable|string|max:20',
            'campaign_name' => 'nullable|string|max:100',
        ]);

        $result = count($data['contacts']) === 1
            ? $this->singleResult($data['contacts'][0], $data['message'], $data['sender_id'] ?? null)
            : $this->sms->sendBulk($data['contacts'], $data['message'], $data['sender_id'] ?? null);

        $log = SmsLog::create([
            'type'          => 'custom',
            'sender_id'     => $data['sender_id'] ?? config('services.smslenz.sender_id', 'SMSlenzDEMO'),
            'recipients'    => $data['contacts'],
            'message'       => $data['message'],
            'total_count'   => count($data['contacts']),
            'success_count' => $result['success_count'] ?? ($result['success'] ? 1 : 0),
            'failed_count'  => $result['failed_count'] ?? ($result['success'] ? 0 : 1),
            'api_response'  => $result['response'],
            'status'        => $this->resolveStatus($result),
            'campaign_name' => $data['campaign_name'] ?? null,
            'sent_by'       => $request->user()->id,
        ]);

        return response()->json([
            'message' => $result['success'] ? 'SMS sent successfully' : 'SMS sending failed',
            'log'     => $log->load('sentBy:id,name'),
            'result'  => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/sms/send-promotion
     * Send a promotional SMS to all/filtered customers with valid phone numbers.
     */
    public function sendPromotion(Request $request)
    {
        $data = $request->validate([
            'message'       => 'required|string|max:621',
            'sender_id'     => 'nullable|string|max:20',
            'campaign_name' => 'nullable|string|max:100',
            'customer_ids'  => 'nullable|array',
            'customer_ids.*'=> 'integer|exists:customers,id',
        ]);

        $query = Customer::whereNotNull('phone')->where('phone', '!=', '');
        if (!empty($data['customer_ids'])) {
            $query->whereIn('id', $data['customer_ids']);
        }

        $phones = $query->pluck('phone')->toArray();

        if (empty($phones)) {
            return response()->json(['message' => 'No customers with valid phone numbers found'], 422);
        }

        $result = $this->sms->sendBulk($phones, $data['message'], $data['sender_id'] ?? null);

        $log = SmsLog::create([
            'type'          => 'promotion',
            'sender_id'     => $data['sender_id'] ?? config('services.smslenz.sender_id', 'SMSlenzDEMO'),
            'recipients'    => $phones,
            'message'       => $data['message'],
            'total_count'   => count($phones),
            'success_count' => $result['success_count'],
            'failed_count'  => $result['failed_count'],
            'api_response'  => $result['response'],
            'status'        => $this->resolveStatus($result),
            'campaign_name' => $data['campaign_name'] ?? null,
            'sent_by'       => $request->user()->id,
        ]);

        return response()->json([
            'message' => "SMS sent to {$result['success_count']} of " . count($phones) . " customers",
            'log'     => $log->load('sentBy:id,name'),
            'result'  => $result,
        ]);
    }

    /**
     * POST /api/sms/send-birthdays
     * Send birthday wishes to customers whose birthday is today (or on a given date).
     */
    public function sendBirthdays(Request $request)
    {
        $data = $request->validate([
            'message'   => 'required|string|max:621',
            'sender_id' => 'nullable|string|max:20',
            'date'      => 'nullable|date',
        ]);

        $date = $data['date'] ?? now()->toDateString();
        $parsed = \Carbon\Carbon::parse($date);

        $customers = Customer::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $parsed->month)
            ->whereDay('date_of_birth', $parsed->day)
            ->get(['id', 'name', 'phone']);

        if ($customers->isEmpty()) {
            return response()->json(['message' => 'No customers with birthdays on this date', 'count' => 0], 200);
        }

        $phones = $customers->pluck('phone')->toArray();
        $result = $this->sms->sendBulk($phones, $data['message'], $data['sender_id'] ?? null);

        $log = SmsLog::create([
            'type'          => 'birthday',
            'sender_id'     => $data['sender_id'] ?? config('services.smslenz.sender_id', 'SMSlenzDEMO'),
            'recipients'    => $phones,
            'message'       => $data['message'],
            'total_count'   => count($phones),
            'success_count' => $result['success_count'],
            'failed_count'  => $result['failed_count'],
            'api_response'  => $result['response'],
            'status'        => $this->resolveStatus($result),
            'campaign_name' => "Birthday Wishes - {$parsed->format('d M Y')}",
            'sent_by'       => $request->user()->id,
        ]);

        return response()->json([
            'message'   => "Birthday wishes sent to {$result['success_count']} customer(s)",
            'customers' => $customers,
            'log'       => $log->load('sentBy:id,name'),
            'result'    => $result,
        ]);
    }

    /**
     * GET /api/sms/birthday-preview
     * Preview customers with birthdays on a date (no SMS sent).
     */
    public function birthdayPreview(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $parsed = \Carbon\Carbon::parse($date);

        $customers = Customer::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $parsed->month)
            ->whereDay('date_of_birth', $parsed->day)
            ->get(['id', 'name', 'phone', 'date_of_birth']);

        return response()->json(['customers' => $customers, 'count' => $customers->count()]);
    }

    /**
     * GET /api/sms/customer-list
     * Get all customers with valid phones for promotion targeting.
     */
    public function customerList(Request $request)
    {
        $customers = Customer::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return response()->json(['customers' => $customers, 'count' => $customers->count()]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function singleResult(string $contact, string $message, ?string $senderId): array
    {
        $r = $this->sms->sendSingle($contact, $message, $senderId);
        return array_merge($r, [
            'success_count' => $r['success'] ? 1 : 0,
            'failed_count'  => $r['success'] ? 0 : 1,
        ]);
    }

    private function resolveStatus(array $result): string
    {
        if (!$result['success']) return 'failed';
        if (($result['failed_count'] ?? 0) > 0) return 'partial';
        return 'sent';
    }
}
