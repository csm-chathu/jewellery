<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('code', 'MAIN')->firstOrFail();

        $users = [
            [
                'name'                    => 'Admin',
                'email'                   => 'admin@jewellery.com',
                'role'                    => 'admin',
                'can_override_gold_rate'  => true,
                'can_delete_transactions' => true,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Manager',
                'email'                   => 'manager@jewellery.com',
                'role'                    => 'manager',
                'can_override_gold_rate'  => true,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Accountant',
                'email'                   => 'accountant@jewellery.com',
                'role'                    => 'accountant',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Cashier',
                'email'                   => 'cashier@jewellery.com',
                'role'                    => 'cashier',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Auditor',
                'email'                   => 'auditor@jewellery.com',
                'role'                    => 'auditor',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'HR Officer',
                'email'                   => 'hr@jewellery.com',
                'role'                    => 'hr',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Finance Officer',
                'email'                   => 'finance@jewellery.com',
                'role'                    => 'finance',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Gold Buyer',
                'email'                   => 'goldbuyer@jewellery.com',
                'role'                    => 'gold_buyer',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Branch Staff',
                'email'                   => 'branch@jewellery.com',
                'role'                    => 'branch',
                'can_override_gold_rate'  => false,
                'can_delete_transactions' => false,
                'is_active'               => true,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password'  => Hash::make('password'),
                    'branch_id' => $branch->id,
                ])
            );
        }
    }
}
