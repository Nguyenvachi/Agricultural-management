<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PriceListFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAgency(string $suffix = ''): Agency
    {
        return Agency::query()->create([
            'code' => 'PL-AG' . $suffix . rand(100, 999),
            'name' => 'Price Agency ' . $suffix,
            'is_active' => true,
        ]);
    }

    private function makeItem(): Item
    {
        $category = Category::query()->first();
        if (! $category) {
            $category = Category::query()->create([
                'code' => 'PL-CAT',
                'name' => 'Price Category',
            ]);
        }

        return Item::query()->create([
            'code' => 'PL-ITM' . rand(1000, 9999),
            'name' => 'Price Item ' . rand(1000, 9999),
            'category_id' => $category->id,
            'unit' => 'kg',
        ]);
    }

    private function makeAdmin(): User
    {
        $agency = $this->makeAgency('ADMIN');

        return User::query()->create([
            'role_id' => LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'price_admin_' . rand(1000, 9999),
            'password_hash' => bcrypt('password'),
            'full_name' => 'Price Admin',
            'is_active' => true,
        ]);
    }

    private function priceTypeId(string $code): int
    {
        return LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, $code);
    }

    public function test_cannot_create_overlapping_active_price_list(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency('A');
        $item = $this->makeItem();

        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 10000,
            'effective_from' => '2026-05-01',
            'effective_to' => '2026-05-31',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('price-lists.store'), [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 12000,
            'effective_from' => '2026-05-15',
            'effective_to' => '2026-06-15',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('effective_from');
    }

    public function test_can_create_non_overlapping_active_price_list(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency('B');
        $item = $this->makeItem();

        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_SELL),
            'price' => 10000,
            'effective_from' => '2026-05-01',
            'effective_to' => '2026-05-31',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('price-lists.store'), [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_SELL),
            'price' => 12500,
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('price_lists', [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price' => 12500,
        ]);
    }

    public function test_cannot_update_price_list_into_overlapping_active_range(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency('C');
        $item = $this->makeItem();
        $priceTypeId = $this->priceTypeId(LookupCode::PRICE_BUY);

        $first = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
            'effective_from' => '2026-05-01',
            'effective_to' => '2026-05-31',
            'is_active' => true,
        ]);

        $second = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 12000,
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('price-lists.update', $second), [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 12000,
            'effective_from' => '2026-05-15',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('effective_from');

        $first->refresh();
        $second->refresh();
        $this->assertEquals('2026-06-01', $second->effective_from?->format('Y-m-d'));
    }

    public function test_destroy_deactivates_price_list_and_closes_effective_date(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency('D');
        $item = $this->makeItem();

        $priceList = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 10000,
            'effective_from' => now()->subDays(2)->toDateString(),
            'effective_to' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)->delete(route('price-lists.destroy', $priceList))
            ->assertRedirect(route('price-lists.index'));

        $priceList->refresh();
        $this->assertFalse($priceList->is_active);
        $this->assertEquals(today()->toDateString(), $priceList->effective_to?->toDateString());
    }

    public function test_price_list_show_displays_current_effective_status(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency('E');
        $item = $this->makeItem();

        $priceList = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_SELL),
            'price' => 22000,
            'effective_from' => today()->subDay()->toDateString(),
            'effective_to' => today()->addDay()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('price-lists.show', $priceList));

        $response->assertOk();
        $response->assertSee('Dang hieu luc');
    }
}
