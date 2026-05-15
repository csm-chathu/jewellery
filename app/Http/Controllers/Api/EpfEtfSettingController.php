<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EpfEtfSetting;
use Illuminate\Http\Request;

class EpfEtfSettingController extends Controller
{
    private function authorise(Request $request): void
    {
        if (!in_array($request->user()->role, ['admin', 'manager'])) {
            abort(403, 'Access denied.');
        }
    }

    public function index(Request $request)
    {
        $this->authorise($request);
        return EpfEtfSetting::with('creator:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();
    }

    public function current()
    {
        return response()->json(EpfEtfSetting::current());
    }

    public function store(Request $request)
    {
        $this->authorise($request);

        $data = $request->validate([
            'epf_employee_rate' => 'required|numeric|min:0|max:100',
            'epf_employer_rate' => 'required|numeric|min:0|max:100',
            'etf_employer_rate' => 'required|numeric|min:0|max:100',
            'effective_from'    => 'required|date',
            'notes'             => 'nullable|string|max:500',
        ]);

        // Deactivate previous active settings
        EpfEtfSetting::where('is_active', true)->update(['is_active' => false]);

        $setting = EpfEtfSetting::create(array_merge($data, [
            'is_active'  => true,
            'created_by' => $request->user()->id,
        ]));

        AuditLog::record('epf_etf_updated', "EPF/ETF rates updated: EPF emp {$setting->epf_employee_rate}%, EPF emr {$setting->epf_employer_rate}%, ETF {$setting->etf_employer_rate}%", $setting, [], $data);

        return response()->json($setting->load('creator:id,name'), 201);
    }
}
