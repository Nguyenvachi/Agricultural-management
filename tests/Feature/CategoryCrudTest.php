<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\SysLookupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SysLookupSeeder::class);
    }

    /**
     * Helper để tạo category với random code
     */
    private function makeCategory(array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'code' => 'CAT-' . rand(10000, 99999),
            'name' => 'Test Category ' . rand(1000, 9999),
            'description' => 'Test Description',
        ], $overrides));
    }

    /**
     * Helper để tạo admin user
     */
    private function makeAdmin(): User
    {
        $agency = Agency::query()->create([
            'code' => 'AG-CAT-' . rand(10000, 99999),
            'name' => 'Admin Agency',
            'is_active' => true,
        ]);
        return User::query()->create([
            'role_id' => LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'cat_admin_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Category Admin',
            'is_active' => true,
        ]);
    }

    public function test_can_list_categories_with_pagination(): void
    {
        $admin = $this->makeAdmin();
        // Create 20 categories để test pagination (15 per page)
        for ($i = 0; $i < 20; $i++) {
            $this->makeCategory(['code' => 'CAT-LIST-' . $i]);
        }

        $response = $this->actingAs($admin)->get(route('categories.index'));
        $response->assertOk();
        $response->assertViewHas('categories');

        $categories = $response['categories'];
        $this->assertCount(15, $categories);
        $this->assertTrue($categories->hasPages());
    }

    public function test_can_create_category_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $payload = [
            'code' => 'CAT-NEW-' . rand(10000, 99999),
            'name' => 'New Test Category',
            'description' => 'New Category Description',
        ];

        $response = $this->actingAs($admin)->post(route('categories.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'code' => $payload['code'],
            'name' => $payload['name'],
            'description' => $payload['description'],
        ]);
    }

    public function test_cannot_create_category_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $existing = $this->makeCategory();

        $response = $this->actingAs($admin)->post(route('categories.store'), [
            'code' => $existing->code,
            'name' => 'Another Category',
            'description' => 'Another Description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    }

    public function test_cannot_create_category_without_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('categories.store'), [
            'code' => '',
            'name' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code', 'name']);
    }

    public function test_can_view_category_detail(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $response = $this->actingAs($admin)->get(route('categories.show', $category));
        $response->assertOk();
        $response->assertViewHas('category', $category);
    }

    public function test_can_update_category_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $newData = [
            'code' => $category->code, // Code không đổi (unique constraint)
            'name' => 'Updated Category Name',
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($admin)->put(route('categories.update', $category), $newData);
        $response->assertRedirect(route('categories.show', $category));

        $category->refresh();
        $this->assertEquals('Updated Category Name', $category->name);
        $this->assertEquals('Updated Description', $category->description);
    }

    public function test_cannot_update_category_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $category1 = $this->makeCategory();
        $category2 = $this->makeCategory();

        $response = $this->actingAs($admin)->put(route('categories.update', $category1), [
            'code' => $category2->code, // Duplicate code
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');

        $category1->refresh();
        $this->assertNotEquals('Updated Name', $category1->name);
    }

    public function test_can_soft_delete_category(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category));
        $response->assertRedirect(route('categories.index'));

        // Soft delete: record vẫn ở DB nhưng deleted_at != null
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);

        $category->refresh();
        $this->assertNotNull($category->deleted_at);
    }

    public function test_can_restore_soft_deleted_category(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $this->actingAs($admin)->delete(route('categories.destroy', $category));
        $category->refresh();
        $this->assertNotNull($category->deleted_at);

        // Restore using withTrashed
        $category->restore();

        $this->assertNull($category->refresh()->deleted_at);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_show_category_edit_form(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $response = $this->actingAs($admin)->get(route('categories.edit', $category));
        $response->assertOk();
        $response->assertViewHas('category', $category);
    }
}
