<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Carat;
use App\Models\GoldRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoldRateController extends Controller
{
    /** Today's rates per carat + last 30 days history + active carats */
    public function index()
    {
        $carats  = Carat::active()->get();
        $today   = GoldRate::todayRates();

        $history = GoldRate::with(['carat', 'createdBy:id,name'])
            ->orderByDesc('date')
            ->take(90)
            ->get()
            ->groupBy(fn ($r) => $r->date->toDateString());

        return response()->json([
            'carats'  => $carats,
            'today'   => $today->values(),
            'history' => $history,
        ]);
    }

    /** Upsert rates for one or more carats */
    public function store(Request $request)
    {
        if (!$request->user()->canOverrideGoldRate()) {
            abort(403, 'You do not have permission to set gold rates.');
        }

        $data = $request->validate([
            'date'                    => 'nullable|date',
            'rates'                   => 'required|array|min:1',
            'rates.*.carat_id'        => 'required|integer|exists:carats,id',
            'rates.*.rate_per_gram'   => 'required|numeric|min:1',
        ]);

        $date    = $data['date'] ?? today()->toDateString();
        $saved   = [];
        $user    = $request->user();

        DB::transaction(function () use ($data, $date, $user, &$saved) {
            foreach ($data['rates'] as $item) {
                $old = GoldRate::where('date', $date)
                    ->where('carat_id', $item['carat_id'])
                    ->first();

                $rate = GoldRate::updateOrCreate(
                    ['date' => $date, 'carat_id' => $item['carat_id']],
                    ['rate_per_gram' => $item['rate_per_gram'], 'created_by' => $user->id]
                );

                $carat = Carat::find($item['carat_id']);
                AuditLog::record(
                    'gold_rate_updated',
                    "Gold rate ({$carat->label}) for {$date} set to LKR {$item['rate_per_gram']}/g by {$user->name}",
                    $rate,
                    $old ? ['rate_per_gram' => $old->rate_per_gram] : [],
                    ['rate_per_gram' => $item['rate_per_gram']]
                );

                $saved[] = $rate->load('carat');
            }
        });

        return response()->json($saved, 201);
    }

    /** All of today's rates — lightweight for other modules */
    public function todayRate()
    {
        return response()->json(GoldRate::todayRates()->values());
    }

    /** Calculate price for weight + carat_id using today's rate */
    public function calculate(Request $request)
    {
        $request->validate([
            'weight'   => 'required|numeric|min:0',
            'carat_id' => 'required|integer|exists:carats,id',
        ]);

        $rate = GoldRate::todayForCarat((int) $request->carat_id);
        if (!$rate) {
            return response()->json(['message' => 'No gold rate set for today for this carat.'], 404);
        }

        $rate->load('carat');

        return response()->json([
            'price'         => $rate->calculate((float) $request->weight),
            'rate_per_gram' => $rate->rate_per_gram,
            'carat'         => $rate->carat,
            'date'          => $rate->date,
        ]);
    }
}
