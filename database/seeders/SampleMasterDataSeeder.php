<?php

namespace Database\Seeders;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Item;
use App\Models\PriceList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SampleMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Đảm bảo lookup có trước (đặc biệt khi cache đã lưu 0)
        $this->call(SysLookupSeeder::class);
        Cache::flush();

        // ── Agencies ─────────────────────────────────────────────────
        $agencies = [
            [
                'code' => 'AG001',
                'name' => 'Đại lý mẫu AG001',
                'address' => 'HCM',
                'phone' => '0900000001',
                'is_active' => 1,
            ],
            [
                'code' => 'AG002',
                'name' => 'Đại lý mẫu AG002',
                'address' => 'HN',
                'phone' => '0900000005',
                'is_active' => 1,
            ],
        ];

        foreach ($agencies as $data) {
            $agency = Agency::withTrashed()->updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            if (method_exists($agency, 'trashed') && $agency->trashed()) {
                $agency->restore();
            }
        }

        // ── Categories ───────────────────────────────────────────────
        $categories = [
            ['code' => 'CAT-FRUIT', 'name' => 'Trái cây', 'description' => 'Nhóm trái cây'],
            ['code' => 'CAT-GRAIN', 'name' => 'Ngũ cốc', 'description' => 'Nhóm ngũ cốc'],
        ];

        $categoryIdByCode = [];
        foreach ($categories as $data) {
            $category = Category::withTrashed()->updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            if (method_exists($category, 'trashed') && $category->trashed()) {
                $category->restore();
            }

            $categoryIdByCode[$data['code']] = (int) $category->id;
        }

        // ── Items ────────────────────────────────────────────────────
        $items = [
            [
                'code' => 'ITEM-RICE',
                'name' => 'Gạo',
                'unit' => 'kg',
                'category_code' => 'CAT-GRAIN',
            ],
            [
                'code' => 'ITEM-CORN',
                'name' => 'Bắp',
                'unit' => 'kg',
                'category_code' => 'CAT-GRAIN',
            ],
            [
                'code' => 'ITEM-MANGO',
                'name' => 'Xoài',
                'unit' => 'kg',
                'category_code' => 'CAT-FRUIT',
            ],
        ];

        foreach ($items as $data) {
            $categoryId = $categoryIdByCode[$data['category_code']] ?? 0;
            if ($categoryId <= 0) {
                continue;
            }

            $item = Item::withTrashed()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'unit' => $data['unit'],
                ]
            );

            if (method_exists($item, 'trashed') && $item->trashed()) {
                $item->restore();
            }
        }

        // ── Price Lists ───────────────────────────────────────────────
        $buyTypeId = LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, LookupCode::PRICE_BUY);
        $sellTypeId = LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, LookupCode::PRICE_SELL);

        if ($buyTypeId <= 0 || $sellTypeId <= 0) {
            // Nếu lookup bị cache sai, flush & lấy lại
            Cache::flush();
            $buyTypeId = LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, LookupCode::PRICE_BUY);
            $sellTypeId = LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, LookupCode::PRICE_SELL);
        }

        $effectiveFrom = now()->startOfMonth()->toDateString();

        $allAgencies = Agency::query()->orderBy('id')->get();
        $allItems = Item::query()->orderBy('id')->get();

        foreach ($allAgencies as $agency) {
            foreach ($allItems as $item) {
                // Giá thu mua - only create if not exists to avoid overwriting manual prices
                PriceList::query()->firstOrCreate(
                    [
                        'agency_id' => $agency->id,
                        'item_id' => $item->id,
                        'price_type_id' => $buyTypeId,
                        'effective_from' => $effectiveFrom,
                    ],
                    [
                        'price' => $this->defaultBuyPriceByItemCode((string) $item->code),
                        'effective_to' => null,
                        'is_active' => 1,
                    ]
                );

                // Giá bán - only create if not exists to avoid overwriting manual prices
                PriceList::query()->firstOrCreate(
                    [
                        'agency_id' => $agency->id,
                        'item_id' => $item->id,
                        'price_type_id' => $sellTypeId,
                        'effective_from' => $effectiveFrom,
                    ],
                    [
                        'price' => $this->defaultSellPriceByItemCode((string) $item->code),
                        'effective_to' => null,
                        'is_active' => 1,
                    ]
                );
            }
        }
    }

    private function defaultBuyPriceByItemCode(string $itemCode): float
    {
        return match ($itemCode) {
            'ITEM-RICE' => 12000,
            'ITEM-CORN' => 9000,
            'ITEM-MANGO' => 25000,
            default => 10000,
        };
    }

    private function defaultSellPriceByItemCode(string $itemCode): float
    {
        return match ($itemCode) {
            'ITEM-RICE' => 15000,
            'ITEM-CORN' => 12000,
            'ITEM-MANGO' => 32000,
            default => 13000,
        };
    }
}
