<?php

namespace Database\Seeders;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUserSeeder extends Seeder
{
    public function run()
    {
        if (User::query()->exists()) {
            return;
        }

        $adminRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN);

        User::query()->create([
            'role_id' => $adminRoleId,
            'agency_id' => null,
            'username' => 'admin',
            'password_hash' => Hash::make('admin123'),
            'full_name' => 'Admin',
            'phone' => null,
            'is_active' => 1,
        ]);
    }
}
