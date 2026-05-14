<?php

namespace Database\Seeders;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SampleOrderInventorySeeder extends Seeder
{
    public function run(): void
    {
        // Đảm bảo master data + lookup + sample users có trước
        $this->call(SampleMasterDataSeeder::class);
        $this->call(InitialUserSeeder::class);

        // Tránh seed lặp (đơn mẫu)
        // Previously we returned early if any SAMPLE note existed, which can leave a
        // partially-seeded state when rerun. Instead we check per-sample note and
        // create missing ones only.

        $admin = User::withTrashed()->where('username', 'admin')->first();
        if (!$admin) {
            return;
        }
        if (method_exists($admin, 'trashed') && $admin->trashed()) {
            $admin->restore();
        }

        $agencyUser = User::withTrashed()->where('username', 'agency')->first();
        if ($agencyUser && method_exists($agencyUser, 'trashed') && $agencyUser->trashed()) {
            $agencyUser->restore();
        }

        $ag1 = Agency::query()->where('code', 'AG001')->first();
        $ag2 = Agency::query()->where('code', 'AG002')->first();
        if (!$ag1 || !$ag2) {
            return;
        }

        $itemRice = Item::query()->where('code', 'ITEM-RICE')->first();
        $itemCorn = Item::query()->where('code', 'ITEM-CORN')->first();
        $itemMango = Item::query()->where('code', 'ITEM-MANGO')->first();
        if (!$itemRice || !$itemCorn || !$itemMango) {
            return;
        }

        $purchaseTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_PURCHASE);
        $salesTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_SALES);
        $transferTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_INTERNAL_TRANSFER);

        if ($purchaseTypeId <= 0 || $salesTypeId <= 0 || $transferTypeId <= 0) {
            Cache::flush();
            $purchaseTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_PURCHASE);
            $salesTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_SALES);
            $transferTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_INTERNAL_TRANSFER);
        }

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $orderDate = now()->toDateString();
        $createdBy = (int) $admin->id;
        $actorUserId = (int) (($agencyUser?->id) ?: $admin->id);

        // 1) PURCHASE_ORDER: nhập kho cho AG001 (tạo tồn kho ban đầu)
        $note1 = '[SAMPLE] Nhập kho ban đầu (AG001)';
        if (! Order::query()->where('note', $note1)->exists()) {
            $orderService->createOrderWithDetails(
                orderData: [
                    'agency_id' => (int) $ag1->id,
                    'user_id' => $actorUserId,
                    'order_type_id' => $purchaseTypeId,
                    'order_date' => $orderDate,
                    'created_by' => $createdBy,
                    'note' => $note1,
                ],
                detailsData: [
                    ['item_id' => (int) $itemRice->id, 'quantity' => 200, 'unit_price' => 12000],
                    ['item_id' => (int) $itemCorn->id, 'quantity' => 150, 'unit_price' => 9000],
                    ['item_id' => (int) $itemMango->id, 'quantity' => 80, 'unit_price' => 25000],
                ]
            );
        }

        // 2) SALES_ORDER: bán một phần từ AG001
        $note2 = '[SAMPLE] Bán hàng (AG001)';
        if (! Order::query()->where('note', $note2)->exists()) {
            $orderService->createOrderWithDetails(
                orderData: [
                    'agency_id' => (int) $ag1->id,
                    'user_id' => $actorUserId,
                    'order_type_id' => $salesTypeId,
                    'order_date' => $orderDate,
                    'created_by' => $createdBy,
                    'note' => $note2,
                ],
                detailsData: [
                    ['item_id' => (int) $itemRice->id, 'quantity' => 30, 'unit_price' => 15000],
                    ['item_id' => (int) $itemMango->id, 'quantity' => 10, 'unit_price' => 32000],
                ]
            );
        }

        // 3) INTERNAL_TRANSFER: chuyển kho từ AG001 sang AG002
        $note3 = '[SAMPLE] Chuyển kho nội bộ AG001 → AG002';
        if (! Order::query()->where('note', $note3)->exists()) {
            $orderService->createOrderWithOneDetail(
                orderData: [
                    'agency_id' => (int) $ag1->id,
                    'to_agency_id' => (int) $ag2->id,
                    'user_id' => $actorUserId,
                    'order_type_id' => $transferTypeId,
                    'order_date' => $orderDate,
                    'created_by' => $createdBy,
                    'note' => $note3,
                ],
                detailData: [
                    'item_id' => (int) $itemCorn->id,
                    'quantity' => 40,
                    'unit_price' => 0,
                ]
            );
        }
    }
}
