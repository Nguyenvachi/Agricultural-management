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

class OrderFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function orderTypeId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, $code);
    }

    private function orderStatusId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, $code);
    }

    private function roleId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, $code);
    }

    private function makeAgency(string $suffix = ''): Agency
    {
        return Agency::query()->create([
            'code' => 'TEST-AG' . $suffix . rand(100, 999),
            'name' => 'Test Agency ' . $suffix,
            'is_active' => true,
        ]);
    }

    private function makeItem(): Item
    {
        $category = Category::query()->first();
        if (! $category) {
            $category = Category::query()->create([
                'code' => 'TEST-CAT',
                'name' => 'Test Category',
            ]);
        }

        return Item::query()->create([
            'code' => 'TEST-ITM' . rand(1000, 9999),
            'name' => 'Test Item ' . rand(1000, 9999),
            'category_id' => $category->id,
            'unit' => 'kg',
        ]);
    }

    private function makeUser(Agency $agency, string $roleCode = LookupCode::USER_ADMIN): User
    {
        return User::query()->create([
            'role_id' => $this->roleId($roleCode),
            'agency_id' => $agency->id,
            'username' => 'test_user_' . $roleCode . '_' . rand(10000, 99999),
            'password_hash' => bcrypt('password'),
            'full_name' => 'Test User ' . $roleCode,
            'is_active' => true,
        ]);
    }

    private function assertOrderStatus(Order $order, string $expectedCode): void
    {
        $order->refresh();
        $order->load('status');

        $this->assertEquals($expectedCode, $order->status?->code);
    }

    private function createOrder(User $user, array $payload): Order
    {
        $beforeId = (int) (Order::query()->max('id') ?? 0);

        $response = $this->actingAs($user)->post(route('orders.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        return Order::query()
            ->where('id', '>', $beforeId)
            ->latest('id')
            ->firstOrFail();
    }

    private function moveOrderToProcessing(User $user, Order $order): void
    {
        $this->actingAs($user)->post(route('orders.process', $order))->assertRedirect(route('orders.show', $order));
        $this->assertOrderStatus($order, LookupCode::ORDER_PROCESSING);
    }

    private function completeOrder(User $user, Order $order): void
    {
        $this->actingAs($user)->post(route('orders.complete', $order))->assertRedirect(route('orders.show', $order));
        $this->assertOrderStatus($order, LookupCode::ORDER_COMPLETED);
    }

    public function test_create_order_starts_in_pending_without_inventory_changes(): void
    {
        $agency = $this->makeAgency('A');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_price' => 10000,
        ]);

        $this->assertOrderStatus($order, LookupCode::ORDER_PENDING);
        $this->assertDatabaseMissing('inventories', [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_complete_purchase_order_increases_inventory_only_after_processing(): void
    {
        $agency = $this->makeAgency('B');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_price' => 10000,
        ]);

        $this->moveOrderToProcessing($user, $order);
        $this->assertDatabaseMissing('inventories', [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
        ]);

        $this->completeOrder($user, $order);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertEquals(50.0, (float) $inventory->quantity);
    }

    public function test_complete_sales_order_decreases_inventory_after_completion(): void
    {
        $agency = $this->makeAgency('C');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_SALES),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 30,
            'unit_price' => 15000,
        ]);

        $this->moveOrderToProcessing($user, $order);
        $this->completeOrder($user, $order);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(70.0, (float) $inventory->quantity);
    }

    public function test_complete_sales_order_fails_when_insufficient_stock_and_keeps_processing(): void
    {
        $agency = $this->makeAgency('D');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_SALES),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 15000,
        ]);

        $this->moveOrderToProcessing($user, $order);

        $response = $this->actingAs($user)->post(route('orders.complete', $order));
        $response->assertRedirect();
        $response->assertSessionHasErrors('order');

        $this->assertOrderStatus($order, LookupCode::ORDER_PROCESSING);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(5.0, (float) $inventory->quantity);
    }

    public function test_cancel_pending_order_does_not_touch_inventory(): void
    {
        $agency = $this->makeAgency('E');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 12,
            'unit_price' => 1000,
        ]);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));

        $this->assertOrderStatus($order, LookupCode::ORDER_CANCELLED);
        $this->assertDatabaseMissing('inventories', [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_cancel_completed_order_rolls_back_inventory(): void
    {
        $agency = $this->makeAgency('F');
        $item = $this->makeItem();
        $user = $this->makeUser($agency);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_price' => 10000,
        ]);

        $this->moveOrderToProcessing($user, $order);
        $this->completeOrder($user, $order);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));

        $this->assertOrderStatus($order, LookupCode::ORDER_CANCELLED);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(0.0, (float) $inventory?->quantity);
    }

    public function test_create_order_with_multi_details_calculates_total_while_pending(): void
    {
        $agency = $this->makeAgency('G');
        $user = $this->makeUser($agency);

        $category = Category::query()->first() ?? Category::query()->create(['code' => 'C-MUL', 'name' => 'Multi Test']);
        $item1 = Item::query()->create(['code' => 'MUL1-' . rand(100, 999), 'name' => 'Item Multi 1', 'category_id' => $category->id, 'unit' => 'kg']);
        $item2 = Item::query()->create(['code' => 'MUL2-' . rand(100, 999), 'name' => 'Item Multi 2', 'category_id' => $category->id, 'unit' => 'kg']);

        $order = $this->createOrder($user, [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item1->id,
            'quantity' => 10,
            'unit_price' => 5000,
            'details' => [
                ['item_id' => $item2->id, 'quantity' => 20, 'unit_price' => 8000],
            ],
        ]);

        $order->load('details', 'status');

        $this->assertCount(2, $order->details);
        $this->assertEquals(210000.0, (float) $order->total_amount);
        $this->assertEquals(LookupCode::ORDER_PENDING, $order->status?->code);
    }

    public function test_internal_transfer_moves_stock_only_when_completed(): void
    {
        $fromAgency = $this->makeAgency('H1');
        $toAgency = $this->makeAgency('H2');
        $item = $this->makeItem();
        $admin = $this->makeUser($fromAgency);

        Inventory::query()->create([
            'agency_id' => $fromAgency->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $order = $this->createOrder($admin, [
            'agency_id' => $fromAgency->id,
            'to_agency_id' => $toAgency->id,
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_INTERNAL_TRANSFER),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 40,
            'unit_price' => 0,
        ]);

        $this->moveOrderToProcessing($admin, $order);
        $this->completeOrder($admin, $order);

        $fromInventory = Inventory::query()->where('agency_id', $fromAgency->id)->where('item_id', $item->id)->first();
        $toInventory = Inventory::query()->where('agency_id', $toAgency->id)->where('item_id', $item->id)->first();

        $this->assertEquals(60.0, (float) $fromInventory?->quantity);
        $this->assertEquals(40.0, (float) $toInventory?->quantity);
    }

    public function test_return_order_restores_inventory_after_completion_based_on_reference_sales_order(): void
    {
        $agency = $this->makeAgency('I');
        $item = $this->makeItem();
        $admin = $this->makeUser($agency);

        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $salesOrder = $this->createOrder($admin, [
            'agency_id' => $agency->id,
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_SALES),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 30,
            'unit_price' => 15000,
        ]);
        $this->moveOrderToProcessing($admin, $salesOrder);
        $this->completeOrder($admin, $salesOrder);

        $returnOrder = $this->createOrder($admin, [
            'agency_id' => $agency->id,
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_RETURN),
            'reference_order_id' => $salesOrder->id,
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 0,
        ]);
        $this->moveOrderToProcessing($admin, $returnOrder);
        $this->completeOrder($admin, $returnOrder);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(80.0, (float) $inventory?->quantity);
    }

    public function test_adjustment_order_can_decrease_inventory_when_completed(): void
    {
        $agency = $this->makeAgency('J');
        $item = $this->makeItem();
        $admin = $this->makeUser($agency);

        Inventory::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        $order = $this->createOrder($admin, [
            'agency_id' => $agency->id,
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_ADJUSTMENT),
            'adjustment_direction' => LookupCode::TRANSACTION_EXPORT,
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 15,
            'unit_price' => 0,
        ]);

        $this->moveOrderToProcessing($admin, $order);
        $this->completeOrder($admin, $order);

        $inventory = Inventory::query()
            ->where('agency_id', $agency->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertEquals(35.0, (float) $inventory?->quantity);
    }

    public function test_agency_user_forged_payload_is_forced_back_to_own_agency_and_identity(): void
    {
        $agencyA = $this->makeAgency('K1');
        $agencyB = $this->makeAgency('K2');
        $item = $this->makeItem();
        $agencyUser = $this->makeUser($agencyA, LookupCode::USER_AGENCY);
        $otherUser = $this->makeUser($agencyB);

        $this->actingAs($agencyUser)->post(route('orders.store'), [
            'agency_id' => $agencyB->id,
            'user_id' => $otherUser->id,
            'created_by' => $otherUser->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_PURCHASE),
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 1000,
        ])->assertRedirect();

        $this->assertDatabaseMissing('orders', [
            'agency_id' => $agencyB->id,
            'user_id' => $otherUser->id,
            'created_by' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'agency_id' => $agencyA->id,
            'user_id' => $agencyUser->id,
            'created_by' => $agencyUser->id,
            'status_id' => $this->orderStatusId(LookupCode::ORDER_PENDING),
        ]);
    }

    public function test_agency_user_cannot_create_adjustment_order_even_if_payload_is_forged(): void
    {
        $agency = $this->makeAgency('L');
        $item = $this->makeItem();
        $agencyUser = $this->makeUser($agency, LookupCode::USER_AGENCY);

        $response = $this->actingAs($agencyUser)->post(route('orders.store'), [
            'agency_id' => $agency->id,
            'user_id' => $agencyUser->id,
            'created_by' => $agencyUser->id,
            'order_type_id' => $this->orderTypeId(LookupCode::ORDER_ADJUSTMENT),
            'adjustment_direction' => LookupCode::TRANSACTION_IMPORT,
            'order_date' => now()->toDateString(),
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('order');
    }
}
