<?php

namespace Database\Seeders;

use App\Constants\LookupCode;
use App\Models\SysLookupType;
use App\Models\SysLookupValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SysLookupSeeder extends Seeder
{
    public function run(): void
    {
        // Seed sys_lookup_types
        $types = [
            [
                'code' => LookupCode::TYPE_USER_ROLE,
                'name' => 'Vai trò người dùng',
                'description' => null,
            ],
            [
                'code' => LookupCode::TYPE_PRICE_TYPE,
                'name' => 'Loại bảng giá',
                'description' => null,
            ],
            [
                'code' => LookupCode::TYPE_ORDER_TYPE,
                'name' => 'Loại đơn hàng',
                'description' => null,
            ],
            [
                'code' => LookupCode::TYPE_ORDER_STATUS,
                'name' => 'Trạng thái đơn hàng',
                'description' => null,
            ],
            [
                'code' => LookupCode::TYPE_TRANSACTION_TYPE,
                'name' => 'Loại biến động kho',
                'description' => null,
            ],
        ];

        $typeIdByCode = [];

        foreach ($types as $type) {
            $model = SysLookupType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                ]
            );

            $typeIdByCode[$type['code']] = (int) $model->id;
        }

        // Seed sys_lookup_values
        $valuesByType = [
            LookupCode::TYPE_USER_ROLE => [
                ['code' => LookupCode::USER_ADMIN, 'display_name' => 'Quản trị viên', 'sort_order' => 1],
                ['code' => LookupCode::USER_AGENCY, 'display_name' => 'Đại lý', 'sort_order' => 2],
                ['code' => LookupCode::USER_FARMER, 'display_name' => 'Nông hộ', 'sort_order' => 3],
                ['code' => LookupCode::USER_CUSTOMER, 'display_name' => 'Khách hàng', 'sort_order' => 4],
            ],
            LookupCode::TYPE_PRICE_TYPE => [
                ['code' => LookupCode::PRICE_BUY, 'display_name' => 'Giá thu mua', 'sort_order' => 1],
                ['code' => LookupCode::PRICE_SELL, 'display_name' => 'Giá bán', 'sort_order' => 2],
            ],
            LookupCode::TYPE_ORDER_TYPE => [
                ['code' => LookupCode::ORDER_PURCHASE, 'display_name' => 'Đơn nhập hàng', 'sort_order' => 1],
                ['code' => LookupCode::ORDER_SALES, 'display_name' => 'Đơn bán hàng', 'sort_order' => 2],
                ['code' => LookupCode::ORDER_INTERNAL_TRANSFER, 'display_name' => 'Chuyển kho nội bộ', 'sort_order' => 3],
                ['code' => LookupCode::ORDER_RETURN, 'display_name' => 'Đơn trả hàng', 'sort_order' => 4],
                ['code' => LookupCode::ORDER_ADJUSTMENT, 'display_name' => 'Điều chỉnh kho', 'sort_order' => 5],
            ],
            LookupCode::TYPE_ORDER_STATUS => [
                ['code' => LookupCode::ORDER_PENDING, 'display_name' => 'Chờ xử lý', 'sort_order' => 1],
                ['code' => LookupCode::ORDER_PROCESSING, 'display_name' => 'Đang xử lý', 'sort_order' => 2],
                ['code' => LookupCode::ORDER_COMPLETED, 'display_name' => 'Hoàn thành', 'sort_order' => 3],
                ['code' => LookupCode::ORDER_CANCELLED, 'display_name' => 'Đã hủy', 'sort_order' => 4],
            ],
            LookupCode::TYPE_TRANSACTION_TYPE => [
                ['code' => LookupCode::TRANSACTION_IMPORT, 'display_name' => 'Nhập kho', 'sort_order' => 1],
                ['code' => LookupCode::TRANSACTION_EXPORT, 'display_name' => 'Xuất kho', 'sort_order' => 2],
            ],
        ];

        foreach ($valuesByType as $typeCode => $values) {
            $typeId = $typeIdByCode[$typeCode] ?? 0;
            if ($typeId <= 0) {
                continue;
            }

            foreach ($values as $value) {
                SysLookupValue::query()->updateOrCreate(
                    [
                        'type_id' => $typeId,
                        'code' => $value['code'],
                    ],
                    [
                        'display_name' => $value['display_name'],
                        'sort_order' => $value['sort_order'] ?? 0,
                        'is_active' => 1,
                    ]
                );
            }
        }

        // IMPORTANT: LookupHelper caches ID forever; clear caches after seeding to avoid cached 0 values.
        Cache::flush();
    }
}
