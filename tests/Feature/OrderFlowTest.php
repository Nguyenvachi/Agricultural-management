<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * OrderFlowTest — test nghiệp vụ order + kho.
 *
 * Dùng DatabaseTransactions thay vì RefreshDatabase vì project DB-first
 * (không có migration file riêng cho schema chính).
 * Mỗi test wrap trong transaction → rollback sau khi xong, không ảnh hưởng data thật.
 */
class OrderFlowTest extends TestCase
{
    use DatabaseTransactions;

    // ─────────────────────────────────────────────────
    // Helper: lấy ID lookup từ DB thật (đã được seed sẵn)
    // ─────────────────────────────────────────────────
    private function orderTypeId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, $code);
    }

    private function makeAgency(string $suffix = ''): Agency
    {
        return Agency::query()->create([
            'code'      => 'TEST-AG' . $suffix . rand(100, 999),
            'name'      => 'Đại lý Test ' . $suffix,
            'is_active' => true,
        ]);
    }

    private function makeItem(): Item
    {
        // Dùng category đầu tiên đã có trong DB (không tạo mới để tránh conflict unique)
        $cat = Category::query()->first();
        if (!$cat) {
            $cat = Category::query()->create(['code' => 'TEST-CAT', 'name' => 'Test Category']);
        }

        return Item::query()->create([
            'code'        => 'TEST-ITM' . rand(1000, 9999),
            'name'        => 'Test Item ' . rand(1000, 9999),
            'category_id' => $cat->id,
            'unit'        => 'kg',
        ]);
    }

    private function makeUser(Agency $agency): User
    {
        $roleId = LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN);
        return User::query()->create([
            'role_id'       => $roleId,
            'agency_id'     => $agency->id,
            'username'      => 'test_user_' . rand(10000, 99999),
            'password_hash' => bcrypt('password'),
            'full_name'     => 'Test User',
            'is_active'     => true,
        ]);
    }

    // ─────────────────────────────────────────────────
    // Test 1: PURCHASE_ORDER → tồn kho tăng đúng
    // ─────────────────────────────────────────────────
    public function test_create_purchase_order_increases_inventory(): void
    {
        $agency = $this->makeAgency('A');
        $item   = $this->makeItem();
        $user   = $this->makeUser($agency);

        $response = $this->post(route('orders.store'), [
            'agency_id'     => $agency->id,
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date'    => now()->toDateString(),
            'item_id'       => $item->id,
            'quantity'      => 50,
            'unit_price'    => 10000,
        ]);

        $response->assertRedirect();
        $this->assertFalse($response->isRedirect(route('orders.create')), 'Không được redirect về form (có lỗi).');

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($inventory, 'Inventory record phải được tạo sau PURCHASE_ORDER.');
        $this->assertEquals(50.0, (float) $inventory->quantity, 'Tồn kho phải tăng lên 50 sau PURCHASE_ORDER.');
    }

    // ─────────────────────────────────────────────────
    // Test 2: SALES_ORDER → tồn kho giảm đúng
    // ─────────────────────────────────────────────────
    public function test_create_sales_order_decreases_inventory(): void
    {
        $agency = $this->makeAgency('B');
        $item   = $this->makeItem();
        $user   = $this->makeUser($agency);

        // Seed tồn kho ban đầu = 100
        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id'   => $item->id,
            'quantity'  => 100,
        ]);

        $response = $this->post(route('orders.store'), [
            'agency_id'     => $agency->id,
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_SALES),
            'order_date'    => now()->toDateString(),
            'item_id'       => $item->id,
            'quantity'      => 30,
            'unit_price'    => 15000,
        ]);

        $response->assertRedirect();

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(70.0, (float) $inventory->quantity, 'Tồn kho phải giảm còn 70 sau SALES_ORDER.');
    }

    // ─────────────────────────────────────────────────
    // Test 3: SALES_ORDER vượt tồn kho → bị từ chối, kho giữ nguyên
    // ─────────────────────────────────────────────────
    public function test_sales_order_fails_when_insufficient_stock(): void
    {
        $agency = $this->makeAgency('C');
        $item   = $this->makeItem();
        $user   = $this->makeUser($agency);

        // Tồn kho chỉ có 5
        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id'   => $item->id,
            'quantity'  => 5,
        ]);

        $response = $this->post(route('orders.store'), [
            'agency_id'     => $agency->id,
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_SALES),
            'order_date'    => now()->toDateString(),
            'item_id'       => $item->id,
            'quantity'      => 10, // Vượt tồn kho
            'unit_price'    => 15000,
        ]);

        // Phải redirect về lại với lỗi
        $response->assertRedirect();
        $response->assertSessionHasErrors('order');

        // Tồn kho phải giữ nguyên = 5
        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();
        $this->assertEquals(5.0, (float) $inventory->quantity, 'Tồn kho không được thay đổi khi xuất thất bại.');
    }

    // ─────────────────────────────────────────────────
    // Test 4: Cancel order → rollback tồn kho về 0
    // ─────────────────────────────────────────────────
    public function test_cancel_order_rollbacks_inventory(): void
    {
        $agency = $this->makeAgency('D');
        $item   = $this->makeItem();
        $user   = $this->makeUser($agency);

        // Tạo PURCHASE_ORDER → kho tăng lên 50
        $this->post(route('orders.store'), [
            'agency_id'     => $agency->id,
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date'    => now()->toDateString(),
            'item_id'       => $item->id,
            'quantity'      => 50,
            'unit_price'    => 10000,
        ]);

        // Lấy order vừa tạo
        $order = Order::query()
            ->whereHas('details', fn ($q) => $q->where('item_id', $item->id))
            ->latest()
            ->first();
        $this->assertNotNull($order, 'Order phải được tạo trước khi cancel.');

        // Cancel đơn
        $response = $this->post(route('orders.cancel', $order));
        $response->assertRedirect(route('orders.show', $order));

        // Kho phải rollback về 0
        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(0.0, (float) $inventory->quantity, 'Tồn kho phải rollback về 0 sau khi hủy đơn.');

        // Status phải là CANCELLED
        $order->refresh();
        $order->load('status');
        $this->assertEquals(LookupCode::ORDER_CANCELLED, $order->status?->code, 'Status phải là CANCELLED.');
    }

    // ─────────────────────────────────────────────────
    // Test 5: Multi order_details → total_amount tính đúng
    // ─────────────────────────────────────────────────
    public function test_create_order_with_multi_details_calculates_total(): void
    {
        $agency = $this->makeAgency('E');
        $user   = $this->makeUser($agency);

        $cat   = Category::query()->first() ?? Category::query()->create(['code' => 'C-MUL', 'name' => 'Multi Test']);
        $item1 = Item::query()->create(['code' => 'MUL1-' . rand(100,999), 'name' => 'Item Multi 1', 'category_id' => $cat->id, 'unit' => 'kg']);
        $item2 = Item::query()->create(['code' => 'MUL2-' . rand(100,999), 'name' => 'Item Multi 2', 'category_id' => $cat->id, 'unit' => 'kg']);

        // Dòng đầu: 10 × 5000 = 50000
        // Dòng bổ sung: 20 × 8000 = 160000
        // Total kỳ vọng: 210000
        $this->post(route('orders.store'), [
            'agency_id'     => $agency->id,
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date'    => now()->toDateString(),
            'item_id'       => $item1->id,
            'quantity'      => 10,
            'unit_price'    => 5000,
            'details'       => [
                ['item_id' => $item2->id, 'quantity' => 20, 'unit_price' => 8000],
            ],
        ]);

        $order = Order::query()
            ->whereHas('details', fn ($q) => $q->where('item_id', $item1->id))
            ->with('details')
            ->latest()
            ->first();

        $this->assertNotNull($order, 'Order phải được tạo.');
        $this->assertCount(2, $order->details, 'Order phải có đúng 2 order_details (1 + 1 bổ sung).');
        $this->assertEquals(210000.0, (float) $order->total_amount, 'total_amount phải là 210000.');
    }
}
