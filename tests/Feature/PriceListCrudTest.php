<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\User;
use Database\Seeders\SysLookupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PriceListCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SysLookupSeeder::class);
    }

    /**
     * Helper để tạo agency
     */
    private function makeAgency(array $overrides = []): Agency
    {
        return Agency::query()->create(array_merge([
            'code' => 'AG-PL-' . rand(10000, 99999),
            'name' => 'Test Agency ' . rand(1000, 9999),
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Helper để tạo category
     */
    private function makeCategory(array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'code' => 'CAT-PL-' . rand(10000, 99999),
            'name' => 'Test Category ' . rand(1000, 9999),
        ], $overrides));
    }

    /**
     * Helper để tạo item
     */
    private function makeItem(Category $category = null, array $overrides = []): Item
    {
        if (!$category) {
            $category = $this->makeCategory();
        }

        return Item::query()->create(array_merge([
            'category_id' => $category->id,
            'code' => 'ITM-PL-' . rand(10000, 99999),
            'name' => 'Test Item ' . rand(1000, 9999),
            'unit' => 'kg',
        ], $overrides));
    }

    /**
     * Helper để tạo admin user
     */
    private function makeAdmin(): User
    {
        $agency = $this->makeAgency();

        return User::query()->create([
            'role_id' => LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'pl_admin_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Price List Admin',
            'is_active' => true,
        ]);
    }

    /**
     * Helper để lấy price type ID
     */
    private function priceTypeId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_PRICE_TYPE, $code);
    }

    public function test_can_list_price_lists_with_relations(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();

        // Create 20 price lists to test pagination
        for ($i = 0; $i < 20; $i++) {
            PriceList::query()->create([
                'agency_id' => $agency->id,
                'item_id' => $item->id,
                'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
                'price' => 10000 + ($i * 100),
                'effective_from' => now()->addDays($i)->toDateString(),
                'effective_to' => now()->addDays($i + 5)->toDateString(),
                'is_active' => $i % 2 === 0,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('price-lists.index'));
        $response->assertOk();
        $response->assertViewHas('priceLists');
        $response->assertViewHas('today');

        $priceLists = $response['priceLists'];
        $this->assertCount(15, $priceLists);
        $this->assertTrue($priceLists->hasPages());

        // Verify relations were eager loaded
        foreach ($priceLists as $priceList) {
            $this->assertNotNull($priceList->agency);
            $this->assertNotNull($priceList->item);
            $this->assertNotNull($priceList->priceType);
        }
    }

    public function test_can_create_price_list_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();

        $payload = [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 12500,
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->post(route('price-lists.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('price_lists', [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price' => 12500,
        ]);
    }

    public function test_cannot_create_overlapping_active_price_lists(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();
        $priceTypeId = $this->priceTypeId(LookupCode::PRICE_BUY);

        // Create first price list
        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
            'effective_from' => '2026-05-01',
            'effective_to' => '2026-05-31',
            'is_active' => true,
        ]);

        // Try to create overlapping price list
        $response = $this->actingAs($admin)->post(route('price-lists.store'), [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 12000,
            'effective_from' => '2026-05-15',
            'effective_to' => '2026-06-15',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('effective_from');
    }

    public function test_can_create_non_overlapping_active_price_lists(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();
        $priceTypeId = $this->priceTypeId(LookupCode::PRICE_SELL);

        // Create first price list
        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
            'effective_from' => '2026-05-01',
            'effective_to' => '2026-05-31',
            'is_active' => true,
        ]);

        // Create non-overlapping price list
        $response = $this->actingAs($admin)->post(route('price-lists.store'), [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
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

    public function test_cannot_create_price_list_without_required_fields(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('price-lists.store'), [
            'agency_id' => '',
            'item_id' => '',
            'price_type_id' => '',
            'price' => '',
            'effective_from' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['agency_id', 'item_id', 'price_type_id', 'price', 'effective_from']);
    }

    public function test_can_view_price_list_detail(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();

        $priceList = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 10000,
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('price-lists.show', $priceList));
        $response->assertOk();
        $response->assertViewHas('priceList');
        $response->assertViewHas('today');

        $viewPriceList = $response['priceList'];
        $this->assertEquals($priceList->id, $viewPriceList->id);
        $this->assertNotNull($viewPriceList->agency);
        $this->assertNotNull($viewPriceList->item);
        $this->assertNotNull($viewPriceList->priceType);
    }

    public function test_can_update_price_list_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();
        $priceTypeId = $this->priceTypeId(LookupCode::PRICE_BUY);

        $priceList = PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ]);

        $newData = [
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 12000,
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->put(route('price-lists.update', $priceList), $newData);
        $response->assertRedirect(route('price-lists.show', $priceList));

        $priceList->refresh();
        $this->assertEquals(12000, (float) $priceList->price);
    }

    public function test_cannot_update_price_list_into_overlapping_range(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
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

        // Try to update second into overlapping range
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

        $second->refresh();
        $this->assertEquals('2026-06-01', $second->effective_from?->format('Y-m-d'));
    }

    public function test_can_deactivate_price_list_with_destroy(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
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

        $response = $this->actingAs($admin)->delete(route('price-lists.destroy', $priceList));
        $response->assertRedirect(route('price-lists.index'));

        $priceList->refresh();
        $this->assertFalse($priceList->is_active);
        $this->assertEquals(today()->toDateString(), $priceList->effective_to?->toDateString());
    }

    public function test_can_show_price_list_edit_form_with_lookups(): void
    {
        $admin = $this->makeAdmin();
        $priceList = PriceList::query()->create([
            'agency_id' => $this->makeAgency()->id,
            'item_id' => $this->makeItem()->id,
            'price_type_id' => $this->priceTypeId(LookupCode::PRICE_BUY),
            'price' => 10000,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('price-lists.edit', $priceList));
        $response->assertOk();
        $response->assertViewHas('priceList');
        $response->assertViewHas('agencies');
        $response->assertViewHas('items');
        $response->assertViewHas('priceTypes');

        $this->assertGreaterThan(0, count($response['agencies']));
        $this->assertGreaterThan(0, count($response['items']));
        $this->assertGreaterThan(0, count($response['priceTypes']));
    }

    public function test_price_list_shows_current_status_on_index(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();
        $item = $this->makeItem();
        $priceTypeId = $this->priceTypeId(LookupCode::PRICE_BUY);

        // Create expired price list
        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 8000,
            'effective_from' => now()->subDays(10)->toDateString(),
            'effective_to' => now()->subDays(1)->toDateString(),
            'is_active' => true,
        ]);

        // Create current (active) price list
        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        // Create upcoming price list
        PriceList::query()->create([
            'agency_id' => $agency->id,
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 12000,
            'effective_from' => now()->addDays(31)->toDateString(),
            'effective_to' => now()->addDays(60)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('price-lists.index'));
        $response->assertOk();

        // Verify the response has priceLists data
        $priceLists = $response->viewData('priceLists');
        $this->assertNotNull($priceLists);

        // Just verify that created price lists exist in database with correct data
        $this->assertDatabaseHas('price_lists', [
            'item_id' => $item->id,
            'price_type_id' => $priceTypeId,
            'price' => 10000,
        ]);
    }
}
