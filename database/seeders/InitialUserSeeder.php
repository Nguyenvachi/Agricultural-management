<?php

namespace Database\Seeders;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class InitialUserSeeder extends Seeder
{
    public function run()
    {
        // Seed mẫu cho 4 tài khoản: admin/agency/farmer/customer.
        // Nếu đã tồn tại đủ 4 tài khoản thì bỏ qua; nếu thiếu thì seed bổ sung.
        $sampleUsernames = ['admin', 'agency', 'farmer', 'customer'];
        $existingSampleUsers = User::withTrashed()
            ->whereIn('username', $sampleUsernames)
            ->count();
        if ($existingSampleUsers >= count($sampleUsernames)) {
            return;
        }

        $adminRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN);
        $agencyRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_AGENCY);
        $farmerRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_FARMER);
        $customerRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_CUSTOMER);

        // Nếu lookup chưa được seed hoặc cache đã lưu 0 trước đó, seed lại lookup trước khi tạo user.
        if ($adminRoleId <= 0 || $agencyRoleId <= 0 || $farmerRoleId <= 0 || $customerRoleId <= 0) {
            $this->call(SysLookupSeeder::class);
            Cache::flush();

            $adminRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN);
            $agencyRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_AGENCY);
            $farmerRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_FARMER);
            $customerRoleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_CUSTOMER);
        }

        // Tạo agency mẫu để gán cho user role AGENCY.
        $sampleAgency = Agency::withTrashed()->updateOrCreate(
            ['code' => 'AG001'],
            [
                'name' => 'Đại lý mẫu AG001',
                'address' => 'HCM',
                'phone' => '0900000001',
                'is_active' => 1,
            ]
        );
        if (method_exists($sampleAgency, 'trashed') && $sampleAgency->trashed()) {
            $sampleAgency->restore();
        }

        $sampleUsers = [
            'admin' => [
                'role_id' => $adminRoleId,
                'agency_id' => null,
                'username' => 'admin',
                'password_hash' => Hash::make('admin123'),
                'full_name' => 'Admin',
                'phone' => null,
                'is_active' => 1,
            ],
            'agency' => [
                'role_id' => $agencyRoleId,
                'agency_id' => (int) $sampleAgency->id,
                'username' => 'agency',
                'password_hash' => Hash::make('agency123'),
                'full_name' => 'Agency',
                'phone' => '0900000002',
                'is_active' => 1,
            ],
            'farmer' => [
                'role_id' => $farmerRoleId,
                'agency_id' => null,
                'username' => 'farmer',
                'password_hash' => Hash::make('farmer123'),
                'full_name' => 'Farmer',
                'phone' => '0900000003',
                'is_active' => 1,
            ],
            'customer' => [
                'role_id' => $customerRoleId,
                'agency_id' => null,
                'username' => 'customer',
                'password_hash' => Hash::make('customer123'),
                'full_name' => 'Customer',
                'phone' => '0900000004',
                'is_active' => 1,
            ],
        ];

        foreach ($sampleUsers as $username => $payload) {
            $existing = User::withTrashed()->where('username', $username)->first();

            if (! $existing) {
                // create once with hashed password; do not re-hash on subsequent seeds
                $user = User::create($payload);
            } else {
                // update non-sensitive fields only; avoid changing password_hash every seed run
                $existing->update([
                    'role_id' => $payload['role_id'],
                    'agency_id' => $payload['agency_id'],
                    'full_name' => $payload['full_name'],
                    'phone' => $payload['phone'],
                    'is_active' => $payload['is_active'],
                ]);
                $user = $existing;
            }

            if (method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }
        }
    }
}
