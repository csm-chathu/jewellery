<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'shop_name'         => 'My Jewellery Store',
            'shop_address'      => '',
            'shop_phone'        => '',
            'shop_email'        => '',
            'currency'          => 'LKR',
            'currency_symbol'   => 'Rs.',
            'tax_number'        => '',
            'receipt_footer'    => 'Thank you for shopping with us!',
            'low_stock_alert'   => '5',
            'default_gold_rate' => '0',
            'sms_enabled'       => '0',
            'sms_api_key'       => '',
            'sms_sender_id'     => '',
            'logo_url'          => '',
            'logo_public_id'    => '',
            'print_mode'        => 'pos',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('shop_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
