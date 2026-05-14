<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'is_active' => true]
        );

        $downtownBranch = Branch::firstOrCreate(
            ['code' => 'DWTN'],
            ['name' => 'Downtown Branch', 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'admin@jewellery.com'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'branch_id' => $mainBranch->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'branch@jewellery.com'],
            [
                'name'      => 'Main Branch User',
                'password'  => Hash::make('password'),
                'role'      => 'branch',
                'branch_id' => $downtownBranch->id,
            ]
        );

        $this->call(CaratSeeder::class);

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
                ['slug' => $cat['slug'], 'branch_id' => $mainBranch->id],
                array_merge($cat, ['is_active' => true, 'branch_id' => $mainBranch->id])
            );
        }

        $suppliers = [
            ['name' => 'Gold Masters Ltd',    'email' => 'info@goldmasters.com',    'phone' => '+1-800-111-2222', 'city' => 'New York',    'country' => 'USA'],
            ['name' => 'Silver Craft Co.',    'email' => 'orders@silvercraft.com',  'phone' => '+44-20-1234-5678','city' => 'London',      'country' => 'UK'],
            ['name' => 'Diamond Hub',         'email' => 'contact@diamondhub.com',  'phone' => '+91-98765-43210', 'city' => 'Mumbai',      'country' => 'India'],
            ['name' => 'Precious Stone Inc.', 'email' => 'sales@preciousstone.com', 'phone' => '+1-888-333-4444', 'city' => 'Los Angeles', 'country' => 'USA'],
        ];
        foreach ($suppliers as $sup) {
            Supplier::updateOrCreate(
                ['email' => $sup['email']],
                array_merge($sup, ['is_active' => true, 'branch_id' => $mainBranch->id])
            );
        }

        $categoryIds = Category::pluck('id')->toArray();
        $supplierIds = Supplier::pluck('id')->toArray();
        $materials   = ['Gold', 'Silver', 'Platinum', 'White Gold', 'Rose Gold'];
        $karats      = ['18k', '22k', '24k', '14k'];
        $gemstones   = ['Diamond', 'Ruby', 'Emerald', 'Sapphire', 'Pearl', null];

        for ($i = 1; $i <= 30; $i++) {
            Product::create([
                'sku'             => 'JEW-' . strtoupper(Str::random(8)),
                'name'            => $materials[array_rand($materials)] . ' ' . ['Ring', 'Necklace', 'Bracelet', 'Earring'][array_rand(['a','b','c','d'])],
                'category_id'     => $categoryIds[array_rand($categoryIds)],
                'material'        => $materials[array_rand($materials)],
                'karat'           => $karats[array_rand($karats)],
                'gemstone'        => $gemstones[array_rand($gemstones)],
                'weight'          => round(mt_rand(10, 100) / 10, 1),
                'purchase_price'  => mt_rand(500, 10000),
                'selling_price'   => mt_rand(1000, 25000),
                'stock_quantity'  => mt_rand(2, 50),
                'min_stock_level' => 5,
                'supplier_id'     => $supplierIds[array_rand($supplierIds)],
                'is_active'       => true,
                'branch_id'       => $mainBranch->id,
            ]);
        }

        $customers = [
            ['name' => 'Alice Johnson',  'email' => 'alice@example.com',  'phone' => '+1-555-001'],
            ['name' => 'Bob Smith',      'email' => 'bob@example.com',    'phone' => '+1-555-002'],
            ['name' => 'Carol Williams', 'email' => 'carol@example.com',  'phone' => '+1-555-003'],
            ['name' => 'David Brown',    'email' => 'david@example.com',  'phone' => '+1-555-004'],
            ['name' => 'Emma Davis',     'email' => 'emma@example.com',   'phone' => '+1-555-005'],
        ];
        foreach ($customers as $cust) {
            Customer::updateOrCreate(
                ['email' => $cust['email']],
                array_merge($cust, ['branch_id' => $mainBranch->id])
            );
        }
    }
}
