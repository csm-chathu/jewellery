<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Branch ─────────────────────────────────────────────────────────
        $branch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'is_active' => true]
        );

        // ── 2. Users (admin + one per role) ──────────────────────────────────
        $this->call(UserSeeder::class);

        // ── 3. Gold carats ────────────────────────────────────────────────────
        $this->call(CaratSeeder::class);

        // ── 4. Jewellery categories ───────────────────────────────────────────
        $categories = [
            ['name' => 'Rings',     'slug' => 'rings'],
            ['name' => 'Necklaces', 'slug' => 'necklaces'],
            ['name' => 'Bracelets', 'slug' => 'bracelets'],
            ['name' => 'Earrings',  'slug' => 'earrings'],
            ['name' => 'Bangles',   'slug' => 'bangles'],
            ['name' => 'Pendants',  'slug' => 'pendants'],
            ['name' => 'Chains',    'slug' => 'chains'],
            ['name' => 'Anklets',   'slug' => 'anklets'],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug'], 'branch_id' => $branch->id],
                array_merge($cat, ['is_active' => true, 'branch_id' => $branch->id])
            );
        }

        // ── 5. Default suppliers ──────────────────────────────────────────────
        $suppliers = [
            ['name' => 'Gold Masters Ltd',  'email' => 'info@goldmasters.com',   'phone' => '+94-11-000-0001'],
            ['name' => 'Silver Craft Co.',  'email' => 'orders@silvercraft.com', 'phone' => '+94-11-000-0002'],
            ['name' => 'Diamond Hub',       'email' => 'contact@diamondhub.com', 'phone' => '+94-11-000-0003'],
        ];
        foreach ($suppliers as $sup) {
            Supplier::updateOrCreate(
                ['email' => $sup['email']],
                array_merge($sup, ['is_active' => true, 'branch_id' => $branch->id])
            );
        }

        // ── 6. Shop settings (defaults) ───────────────────────────────────────
        $this->call(ShopSettingSeeder::class);

        // ── 7. EPF / ETF settings (Sri Lanka statutory rates) ─────────────────
        $this->call(EpfEtfSeeder::class);
    }
}
