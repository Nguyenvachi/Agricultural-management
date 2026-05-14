<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\SysLookupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure lookup data is seeded before running tests
        $this->seed(SysLookupSeeder::class);
    }

    /**
     * Helper để tạo agency
     */
    private function makeAgency(array $overrides = []): Agency
    {
        return Agency::query()->create(array_merge([
            'code' => 'AG-USER-' . rand(10000, 99999),
            'name' => 'Test Agency ' . rand(1000, 9999),
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Helper để lấy role ID từ lookup code
     */
    private function roleId(string $code): int
    {
        return (int) LookupHelper::getValueId(LookupCode::TYPE_USER_ROLE, $code);
    }

    /**
     * Helper để tạo user
     */
    private function makeUser(array $overrides = []): User
    {
        $agency = $overrides['agency'] ?? $this->makeAgency();
        unset($overrides['agency']);

        return User::query()->create(array_merge([
            'role_id' => $this->roleId(LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'test_user_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Test User ' . rand(1000, 9999),
            'phone' => '0900000000',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Helper để tạo admin user
     */
    private function makeAdmin(): User
    {
        $agency = Agency::query()->create([
            'code' => 'AG-USER-' . rand(10000, 99999),
            'name' => 'Admin Agency',
            'is_active' => true,
        ]);
        return User::query()->create([
            'role_id' => $this->roleId(LookupCode::USER_ADMIN),
            'agency_id' => $agency->id,
            'username' => 'admin_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Test Admin',
            'phone' => '0900000000',
            'is_active' => true,
        ]);
    }

    public function test_can_list_users_with_relations(): void
    {
        $admin = $this->makeAdmin();

        // Create 20 users to test pagination
        for ($i = 0; $i < 20; $i++) {
            $this->makeUser();
        }

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertOk();
        $response->assertViewHas('users');

        $users = $response['users'];
        $this->assertCount(15, $users);
        $this->assertTrue($users->hasPages());

        // Verify relations were eager loaded
        foreach ($users as $user) {
            $this->assertNotNull($user->agency);
            $this->assertNotNull($user->role);
        }
    }

    public function test_can_create_user_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $payload = [
            'role_id' => $this->roleId(LookupCode::USER_AGENCY),
            'agency_id' => $agency->id,
            'username' => 'newuser_' . rand(100000, 999999),
            'password_hash' => 'SecurePassword123!',
            'full_name' => 'New Test User',
            'phone' => '0912345678',
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => $payload['username'],
            'full_name' => $payload['full_name'],
            'agency_id' => $agency->id,
        ]);

        // Verify password was hashed
        $user = User::where('username', $payload['username'])->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('SecurePassword123!', $user->password_hash);
    }

    public function test_cannot_create_user_with_duplicate_username(): void
    {
        $admin = $this->makeAdmin();
        $existing = $this->makeUser();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role_id' => $this->roleId(LookupCode::USER_ADMIN),
            'agency_id' => $this->makeAgency()->id,
            'username' => $existing->username,
            'password_hash' => 'SecurePassword123!',
            'full_name' => 'Another User',
            'phone' => '0912345678',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('username');
    }

    public function test_cannot_create_user_without_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role_id' => '',
            'agency_id' => '',
            'username' => '',
            'password_hash' => '',
            'full_name' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['role_id', 'username', 'password_hash', 'full_name']);
    }

    public function test_cannot_create_user_with_invalid_role(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role_id' => 99999,
            'agency_id' => $this->makeAgency()->id,
            'username' => 'newuser_' . rand(100000, 999999),
            'password_hash' => 'SecurePassword123!',
            'full_name' => 'New User',
            'phone' => '0912345678',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('role_id');
    }

    public function test_cannot_create_user_with_invalid_agency(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role_id' => $this->roleId(LookupCode::USER_ADMIN),
            'agency_id' => 99999,
            'username' => 'newuser_' . rand(100000, 999999),
            'password_hash' => 'SecurePassword123!',
            'full_name' => 'New User',
            'phone' => '0912345678',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('agency_id');
    }

    public function test_can_view_user_detail_with_relations(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $response = $this->actingAs($admin)->get(route('users.show', $user));
        $response->assertOk();
        $response->assertViewHas('user');

        $viewUser = $response['user'];
        $this->assertEquals($user->id, $viewUser->id);
        $this->assertNotNull($viewUser->agency);
        $this->assertNotNull($viewUser->role);
    }

    public function test_can_update_user_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $newAgency = $this->makeAgency();

        $newData = [
            'role_id' => $this->roleId(LookupCode::USER_FARMER),
            'agency_id' => $newAgency->id,
            'username' => $user->username,
            'full_name' => 'Updated User Name',
            'phone' => '0987654321',
            'is_active' => false,
        ];

        $response = $this->actingAs($admin)->put(route('users.update', $user), $newData);
        $response->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals($newAgency->id, $user->agency_id);
        $this->assertEquals('Updated User Name', $user->full_name);
        $this->assertEquals('0987654321', $user->phone);
        $this->assertFalse($user->is_active);
    }

    public function test_cannot_update_user_with_duplicate_username(): void
    {
        $admin = $this->makeAdmin();
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        $response = $this->actingAs($admin)->put(route('users.update', $user1), [
            'role_id' => $user1->role_id,
            'agency_id' => $user1->agency_id,
            'username' => $user2->username, // Duplicate username
            'full_name' => 'Updated Name',
            'phone' => '0987654321',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('username');

        $user1->refresh();
        $this->assertNotEquals('Updated Name', $user1->full_name);
    }

    public function test_can_update_user_to_inactive(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $this->assertTrue($user->is_active);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'role_id' => $user->role_id,
            'agency_id' => $user->agency_id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_can_soft_delete_user(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));
        $response->assertRedirect(route('users.index'));

        // Soft delete: user should have deleted_at set
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        $user->refresh();
        $this->assertNotNull($user->deleted_at);
        // Should also be marked inactive
        $this->assertFalse($user->is_active);
    }

    public function test_can_restore_soft_deleted_user(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin)->delete(route('users.destroy', $user));
        $this->assertNotNull($user->refresh()->deleted_at);

        // Restore using withTrashed
        $user->restore();

        $this->assertNull($user->refresh()->deleted_at);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_show_user_edit_form_with_roles_and_agencies(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $response = $this->actingAs($admin)->get(route('users.edit', $user));
        $response->assertOk();
        $response->assertViewHas('user');
        $response->assertViewHas('roles');
        $response->assertViewHas('agencies');

        $this->assertGreaterThan(0, count($response['roles']));
        $this->assertGreaterThan(0, count($response['agencies']));
    }

    public function test_user_role_helpers_work_correctly(): void
    {
        // Note: These helpers don't require authentication
        $admin = $this->makeUser(['role_id' => $this->roleId(LookupCode::USER_ADMIN)]);
        $agency = $this->makeUser(['role_id' => $this->roleId(LookupCode::USER_AGENCY)]);
        $farmer = $this->makeUser(['role_id' => $this->roleId(LookupCode::USER_FARMER)]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isAgency());
        $this->assertFalse($admin->isFarmer());

        $this->assertFalse($agency->isAdmin());
        $this->assertTrue($agency->isAgency());
        $this->assertFalse($agency->isFarmer());

        $this->assertFalse($farmer->isAdmin());
        $this->assertFalse($farmer->isAgency());
        $this->assertTrue($farmer->isFarmer());
    }
}
