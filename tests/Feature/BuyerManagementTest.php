<?php

namespace Tests\Feature;

use App\Models\PlantSpecies;
use App\Models\User;
use App\Services\NurserySaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_buyer(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('buyers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('buyers/create'));

        $this->post(route('buyers.store'), [
            'name' => 'Alice Buyer',
            'email' => 'alice@example.test',
            'contact_no' => '09171234567',
            'password' => 'temporary-secret',
        ])->assertRedirect(route('buyers.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Alice Buyer',
            'email' => 'alice@example.test',
            'contact_no' => '09171234567',
            'role' => 'buyer',
        ]);
    }

    public function test_admin_can_edit_buyer_information(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'name' => 'Old Name',
            'email' => 'old@example.test',
            'contact_no' => '+639171234560',
        ]);

        $this->actingAs($admin)
            ->get(route('buyers.edit', $buyer->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('buyers/edit')
                ->where('buyer.id', $buyer->id)
                ->where('buyer.contact_no', '09171234560'));

        $this->put(route('buyers.update', $buyer->id), [
            'name' => 'New Name',
            'email' => 'new@example.test',
            'contact_no' => '09171234567',
        ])->assertRedirect(route('buyers.index'));

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'name' => 'New Name',
            'email' => 'new@example.test',
            'contact_no' => '09171234567',
            'role' => 'buyer',
        ]);

        $this->get(route('buyers.index'))
            ->assertInertia(fn ($page) => $page
                ->where('buyers.data.0.contact_no', '09171234567'));
    }

    public function test_admin_can_delete_a_buyer_without_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($admin)
            ->delete(route('buyers.destroy', $buyer->id))
            ->assertRedirect(route('buyers.index'));

        $this->assertDatabaseMissing('users', ['id' => $buyer->id]);
    }

    public function test_admin_can_delete_a_buyer_with_sales_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 100,
        ]);
        app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
            ]],
        ]);

        $this->actingAs($admin)
            ->delete(route('buyers.destroy', $buyer->id))
            ->assertRedirect(route('buyers.index'));

        $this->assertDatabaseMissing('users', ['id' => $buyer->id]);
        $this->assertDatabaseMissing('nursery_sales', ['user_id' => $buyer->id]);
    }
}
