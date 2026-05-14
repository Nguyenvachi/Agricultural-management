<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\SysLookupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ItemCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SysLookupSeeder::class);
    }

    /**
     * Helper để tạo category
     */
    private function makeCategory(array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'code' => 'CAT-ITEM-' . rand(10000, 99999),
            'name' => 'Test Category ' . rand(1000, 9999),
        ], $overrides));
    }

    /**
     * Helper để tạo item với random code
     */
    private function makeItem(Category $category = null, array $overrides = []): Item
    {
        if (!$category) {
            $category = $this->makeCategory();
        }

        return Item::query()->create(array_merge([
            'category_id' => $category->id,
            'code' => 'ITM-' . rand(10000, 99999),
            'name' => 'Test Item ' . rand(1000, 9999),
            'unit' => 'kg',
        ], $overrides));
    }

    /**
     * Helper để tạo admin user
     */
    private function makeAdmin(): User
    {
        $agency = Agency::query()->create([
            'code' => 'AG-ITM-' . rand(10000, 99999),
            'name' => 'Admin Agency',
            'is_active' => true,
        ]);
        return User::query()->create([
            'role_id' => LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'itm_admin_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Item Admin',
            'is_active' => true,
        ]);
    }

    public function test_can_list_items_with_category_relation(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        // Create 20 items để test pagination
        for ($i = 0; $i < 20; $i++) {
            $this->makeItem($category, ['code' => 'ITM-LIST-' . $i]);
        }

        $response = $this->actingAs($admin)->get(route('items.index'));
        $response->assertOk();
        $response->assertViewHas('items');

        $items = $response['items'];
        $this->assertCount(15, $items);
        $this->assertTrue($items->hasPages());

        // Verify category relation was eager loaded
        foreach ($items as $item) {
            $this->assertNotNull($item->category);
            $this->assertEquals($category->id, $item->category->id);
        }
    }

    public function test_can_create_item_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $payload = [
            'category_id' => $category->id,
            'code' => 'ITM-NEW-' . rand(10000, 99999),
            'name' => 'New Test Item',
            'unit' => 'ton',
        ];

        $response = $this->actingAs($admin)->post(route('items.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('items', [
            'category_id' => $category->id,
            'code' => $payload['code'],
            'name' => $payload['name'],
            'unit' => 'ton',
        ]);
    }

    public function test_cannot_create_item_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $existing = $this->makeItem($category);

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'category_id' => $category->id,
            'code' => $existing->code,
            'name' => 'Another Item',
            'unit' => 'kg',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    }

    public function test_cannot_create_item_with_invalid_category(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('items.store'), [
            'category_id' => 99999, // Non-existent category
            'code' => 'ITM-NEW-' . rand(10000, 99999),
            'name' => 'New Item',
            'unit' => 'kg',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('category_id');
    }

    public function test_cannot_create_item_without_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('items.store'), [
            'category_id' => '',
            'code' => '',
            'name' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['category_id', 'code', 'name']);
    }

    public function test_can_view_item_detail_with_category(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item = $this->makeItem($category);

        $response = $this->actingAs($admin)->get(route('items.show', $item));
        $response->assertOk();
        $response->assertViewHas('item');

        $viewItem = $response['item'];
        $this->assertEquals($item->id, $viewItem->id);
        $this->assertNotNull($viewItem->category);
        $this->assertEquals($category->id, $viewItem->category->id);
    }

    public function test_can_update_item_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $category1 = $this->makeCategory();
        $category2 = $this->makeCategory();
        $item = $this->makeItem($category1);

        $newData = [
            'category_id' => $category2->id,
            'code' => $item->code,
            'name' => 'Updated Item Name',
            'unit' => 'liter',
        ];

        $response = $this->actingAs($admin)->put(route('items.update', $item), $newData);
        $response->assertRedirect(route('items.show', $item));

        $item->refresh();
        $this->assertEquals($category2->id, $item->category_id);
        $this->assertEquals('Updated Item Name', $item->name);
        $this->assertEquals('liter', $item->unit);
    }

    public function test_cannot_update_item_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item1 = $this->makeItem($category);
        $item2 = $this->makeItem($category);

        $response = $this->actingAs($admin)->put(route('items.update', $item1), [
            'category_id' => $category->id,
            'code' => $item2->code, // Duplicate code
            'name' => 'Updated Name',
            'unit' => 'kg',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');

        $item1->refresh();
        $this->assertNotEquals('Updated Name', $item1->name);
    }

    public function test_cannot_update_item_with_invalid_category(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item = $this->makeItem($category);

        $response = $this->actingAs($admin)->put(route('items.update', $item), [
            'category_id' => 99999, // Non-existent category
            'code' => $item->code,
            'name' => 'Updated Name',
            'unit' => 'kg',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('category_id');

        $item->refresh();
        $this->assertEquals($category->id, $item->category_id);
    }

    public function test_can_soft_delete_item(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item = $this->makeItem($category);

        $response = $this->actingAs($admin)->delete(route('items.destroy', $item));
        $response->assertRedirect(route('items.index'));

        $this->assertSoftDeleted('items', [
            'id' => $item->id,
        ]);

        $item->refresh();
        $this->assertNotNull($item->deleted_at);
    }

    public function test_can_restore_soft_deleted_item(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item = $this->makeItem($category);

        $this->actingAs($admin)->delete(route('items.destroy', $item));
        $this->assertNotNull($item->refresh()->deleted_at);

        // Restore using withTrashed
        $item->restore();

        $this->assertNull($item->refresh()->deleted_at);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_show_item_edit_form_with_categories(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $item = $this->makeItem($category);

        $response = $this->actingAs($admin)->get(route('items.edit', $item));
        $response->assertOk();
        $response->assertViewHas('item');
        $response->assertViewHas('categories');

        $this->assertGreaterThan(0, count($response['categories']));
    }
}
