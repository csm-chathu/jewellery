<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'logo_url'   => 'nullable|string|max:1000',
            'print_mode' => 'nullable|in:pos,a5',
        ]);

        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                ShopSetting::setValue($key, $data[$key]);
            }
        }

        return response()->json(ShopSetting::allAsObject());
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        // Delete old logo if it was a locally stored file
        $old = ShopSetting::getValue('logo_url');
        if ($old && str_contains($old, '/storage/logos/')) {
            $oldPath = str_replace('/storage/', 'public/', parse_url($old, PHP_URL_PATH));
            Storage::delete($oldPath);
        }

        $path = $request->file('logo')->store('public/logos');
        $url  = Storage::url($path);

        ShopSetting::setValue('logo_url', $url);

        return response()->json(['logo_url' => $url]);
    }
}
