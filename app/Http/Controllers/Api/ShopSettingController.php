<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    private const ALLOWED_KEYS = [
        'shop_name', 'address', 'phone', 'br_number', 'logo_url', 'print_mode',
    ];

    public function branding()
    {
        $all = ShopSetting::allAsObject();

        return response()->json([
            'shop_name' => $all['shop_name'] ?? config('app.name', 'Jewellery Store'),
            'logo_url'  => $all['logo_url'] ?? '',
        ]);
    }

    public function index()
    {
        return response()->json(ShopSetting::allAsObject());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_name'  => 'nullable|string|max:200',
            'address'    => 'nullable|string|max:500',
            'phone'      => 'nullable|string|max:50',
            'br_number'  => 'nullable|string|max:100',
            'logo_url'   => 'nullable|url|max:1000',
            'print_mode' => 'nullable|in:pos,a5',
        ]);

        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                ShopSetting::setValue($key, $data[$key]);
            }
        }

        return response()->json(ShopSetting::allAsObject());
    }
}
