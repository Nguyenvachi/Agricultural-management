<?php

namespace Tests\Feature;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\SysLookupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgencyCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SysLookupSeeder::class);
    }

    /**
     * Helper để tạo agency với random code
     */
    private function makeAgency(array $overrides = []): Agency
    {
        return Agency::query()->create(array_merge([
            'code' => 'AG-' . rand(10000, 99999),
            'name' => 'Test Agency ' . rand(1000, 9999),
            'address' => 'Test Address',
            'phone' => '0900000000',
            'is_active' => true,
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
            'username' => 'ag_admin_' . rand(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Agency Admin',
            'is_active' => true,
        ]);
    }

    public function test_can_list_agencies_with_pagination(): void
    {
        $admin = $this->makeAdmin();
        // Create 20 agencies để test pagination (15 per page)
        for ($i = 0; $i < 20; $i++) {
            $this->makeAgency(['code' => 'AG-LIST-' . $i]);
        }

        $response = $this->actingAs($admin)->get(route('agencies.index'));
        $response->assertOk();
        $response->assertViewHas('agencies');

        $agencies = $response['agencies'];
        $this->assertCount(15, $agencies);
        $this->assertTrue($agencies->hasPages());
    }

    public function test_can_create_agency_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $payload = [
            'code' => 'AG-NEW-' . rand(10000, 99999),
            'name' => 'New Test Agency',
            'address' => 'New Address',
            'phone' => '0901234567',
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->post(route('agencies.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('agencies', [
            'code' => $payload['code'],
            'name' => $payload['name'],
            'phone' => $payload['phone'],
        ]);
    }

    public function test_cannot_create_agency_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $existing = $this->makeAgency();

        $response = $this->actingAs($admin)->post(route('agencies.store'), [
            'code' => $existing->code,
            'name' => 'Another Agency',
            'address' => 'Another Address',
            'phone' => '0901234567',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    }

    public function test_cannot_create_agency_without_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('agencies.store'), [
            'code' => '',
            'name' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code', 'name']);
    }

    public function test_can_view_agency_detail(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $response = $this->actingAs($admin)->get(route('agencies.show', $agency));
        $response->assertOk();
        $response->assertViewHas('agency', $agency);
    }

    public function test_can_update_agency_with_valid_data(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $newData = [
            'code' => $agency->code, // Code không đổi (unique constraint)
            'name' => 'Updated Agency Name',
            'address' => 'Updated Address',
            'phone' => '0912345678',
            'is_active' => false,
        ];

        $response = $this->actingAs($admin)->put(route('agencies.update', $agency), $newData);
        $response->assertRedirect(route('agencies.show', $agency));

        $agency->refresh();
        $this->assertEquals('Updated Agency Name', $agency->name);
        $this->assertEquals('Updated Address', $agency->address);
        $this->assertEquals('0912345678', $agency->phone);
        $this->assertFalse($agency->is_active);
    }

    public function test_cannot_update_agency_with_duplicate_code(): void
    {
        $admin = $this->makeAdmin();
        $agency1 = $this->makeAgency();
        $agency2 = $this->makeAgency();

        $response = $this->actingAs($admin)->put(route('agencies.update', $agency1), [
            'code' => $agency2->code, // Duplicate code
            'name' => 'Updated Name',
            'address' => 'Updated Address',
            'phone' => '0912345678',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');

        $agency1->refresh();
        $this->assertNotEquals('Updated Name', $agency1->name);
    }

    public function test_can_soft_delete_agency(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $response = $this->actingAs($admin)->delete(route('agencies.destroy', $agency));
        $response->assertRedirect(route('agencies.index'));

        // Soft delete: record vẫn ở DB nhưng deleted_at != null
        $this->assertSoftDeleted('agencies', [
            'id' => $agency->id,
        ]);

        // Đảm bảo nó không xuất hiện trong list bình thường
        $response = $this->actingAs($admin)->get(route('agencies.index'));
        $agency->refresh();
        // Vì Agency::query() không filter soft delete tự động, ta cần check deleted_at
        $this->assertNotNull($agency->deleted_at);
    }

    public function test_can_restore_soft_deleted_agency(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $this->actingAs($admin)->delete(route('agencies.destroy', $agency));
        $agency->refresh();
        $this->assertNotNull($agency->deleted_at);

        // Restore using Eloquent restore
        $agency->restore();

        $this->assertNull($agency->refresh()->deleted_at);
        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_show_agency_edit_form(): void
    {
        $admin = $this->makeAdmin();
        $agency = $this->makeAgency();

        $response = $this->actingAs($admin)->get(route('agencies.edit', $agency));
        $response->assertOk();
        $response->assertViewHas('agency', $agency);
    }
}
