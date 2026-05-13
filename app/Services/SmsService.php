<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $baseUrl = 'https://smslenz.lk/api';
    private string $userId;
    private string $apiKey;
    private string $senderId;

    public function __construct()
    {
        $this->userId   = config('services.smslenz.user_id', '1344');
        $this->apiKey   = config('services.smslenz.api_key', '14d351ad-4d8b-4868-ae9d-1fc702f8d1d8');
        $this->senderId = config('services.smslenz.sender_id', 'SMSlenzDEMO');
    }

    /**
     * Send SMS to a single contact.
     * Returns ['success' => bool, 'response' => array]
     */
    public function sendSingle(string $contact, string $message, string $senderId = null): array
    {
        $contact = $this->normalizePhone($contact);

        try {
            $response = Http::timeout(30)->withoutVerifying()->post("{$this->baseUrl}/send-sms", [
                'user_id'   => $this->userId,
                'api_key'   => $this->apiKey,
                'sender_id' => $senderId ?? $this->senderId,
                'contact'   => $contact,
                'message'   => $message,
            ]);

            $body = $response->json() ?? [];

            Log::info('SMSlenz single send', [
                'contact'  => $contact,
                'status'   => $response->status(),
                'body'     => $body,
            ]);

            return [
                'success'  => $response->successful() && ($body['status'] ?? '') !== 'error',
                'response' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('SMSlenz single send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'response' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Send bulk SMS to multiple contacts.
     * Returns ['success' => bool, 'response' => array, 'success_count' => int, 'failed_count' => int]
     */
    public function sendBulk(array $contacts, string $message, string $senderId = null): array
    {
        $contacts = array_values(array_filter(array_map([$this, 'normalizePhone'], $contacts)));

        if (empty($contacts)) {
            return ['success' => false, 'response' => ['message' => 'No valid contacts'], 'success_count' => 0, 'failed_count' => 0];
        }

        try {
            $response = Http::timeout(60)->withoutVerifying()->post("{$this->baseUrl}/send-bulk-sms", [
                'user_id'   => $this->userId,
                'api_key'   => $this->apiKey,
                'sender_id' => $senderId ?? $this->senderId,
                'contacts'  => $contacts,
                'message'   => $message,
            ]);

            $body = $response->json() ?? [];

            Log::info('SMSlenz bulk send', [
                'count'  => count($contacts),
                'status' => $response->status(),
                'body'   => $body,
            ]);

            $success = $response->successful() && ($body['status'] ?? '') !== 'error';

            return [
                'success'       => $success,
                'response'      => $body,
                'success_count' => $success ? count($contacts) : 0,
                'failed_count'  => $success ? 0 : count($contacts),
            ];
        } catch (\Exception $e) {
            Log::error('SMSlenz bulk send failed', ['error' => $e->getMessage()]);
            return [
                'success'       => false,
                'response'      => ['message' => $e->getMessage()],
                'success_count' => 0,
                'failed_count'  => count($contacts),
            ];
        }
    }

    /**
     * Normalize a Sri Lankan phone number to +94XXXXXXXXX format.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $cleaned = preg_replace('/\D/', '', $phone);

        // 07XXXXXXXX → +947XXXXXXXX
        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '0')) {
            return '+94' . substr($cleaned, 1);
        }

        // 94XXXXXXXXX → +94XXXXXXXXX
        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '94')) {
            return '+' . $cleaned;
        }

        // Already in correct format
        if (str_starts_with($phone, '+94') && strlen($cleaned) === 11) {
            return '+' . $cleaned;
        }

        return null; // Invalid — skip
    }
}
