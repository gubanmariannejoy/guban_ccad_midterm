<?php

namespace Tests\Feature;

use App\Models\NurserySale;
use App\Models\PlantSpecies;
use App\Models\User;
use App\Services\NurserySaleService;
use App\Services\PlantSpeciesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_browse_and_search_available_plants(): void
    {
        $this->withoutVite();
        $buyer = User::factory()->create(['role' => 'buyer']);
        app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 50,
            'quantity_available' => 10,
        ]);
        app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Sunflower',
            'botanical_name' => 'Helianthus annuus',
            'pot_size' => 'M-12cm',
            'unit_price' => 35,
            'quantity_available' => 5,
        ]);

        $this->actingAs($buyer)
            ->get(route('buyer.plants.index', ['search' => 'Rosa']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('buyer/plants')
                ->where('search', 'Rosa')
                ->has('plants.data', 1)
                ->where('plants.data.0.plant_name', 'Rose'));
    }

    public function test_inventory_changes_only_when_admin_confirms_a_buyer_order(): void
    {
        $this->withoutVite();
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'contact_no' => '+639171234567',
        ]);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 50,
            'quantity_available' => 10,
        ]);

        $this->actingAs($buyer)
            ->post(route('buyer.cart.add', $plant), ['quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 10,
        ]);

        $dispatchDate = now()->addDay()->toDateString();

        $this->post(route('buyer.checkout'), [
            'name' => 'Buyer Example',
            'contact_no' => '+639171234567',
            'address' => 'Quezon City',
            'payment_method' => 'Cash',
            'dispatch_date' => $dispatchDate,
        ])->assertRedirect(route('nursery-sales.index'));

        $sale = NurserySale::with('saleItems')->sole();
        $this->assertSame('pending', $sale->status);
        $this->assertSame('Buyer Example', $sale->buyer_name);
        $this->assertSame('Quezon City', $sale->buyer_address);
        $this->assertSame($dispatchDate, $sale->dispatch_date->toDateString());
        $this->assertEquals(100, $sale->total_amount);
        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 10,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('nursery-sales.confirm', $sale))
            ->assertRedirect();

        $this->assertSame('confirmed', $sale->fresh()->status);
        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 8,
        ]);

        $this->actingAs($buyer)
            ->get(route('nursery-sales.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('nursery-sales/index')
                ->where('sales.data.0.status', 'confirmed')
                ->where('sales.data.0.buyer_name', 'Buyer Example'));
    }

    public function test_buyers_cannot_add_more_plants_than_available_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Aloe Vera',
            'botanical_name' => 'Aloe vera',
            'pot_size' => 'S-10cm',
            'unit_price' => 80,
            'quantity_available' => 2,
        ]);

        $this->actingAs($buyer)
            ->post(route('buyer.cart.add', $plant), ['quantity' => 3])
            ->assertSessionHasErrors('quantity');
    }

    public function test_buyer_checkout_rejects_dispatch_dates_after_2027(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'contact_no' => '+639171234567',
        ]);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 50,
            'quantity_available' => 5,
        ]);

        $this->actingAs($buyer)
            ->post(route('buyer.cart.add', $plant), ['quantity' => 1])
            ->assertRedirect();

        $this->post(route('buyer.checkout'), [
            'name' => $buyer->name,
            'contact_no' => '+639171234567',
            'payment_method' => 'Cash',
            'dispatch_date' => '2028-01-01',
        ])->assertSessionHasErrors('dispatch_date');

        $this->assertDatabaseCount('nursery_sales', 0);
    }

    public function test_cancelling_a_confirmed_order_restores_inventory(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Snake Plant',
            'botanical_name' => 'Dracaena trifasciata',
            'pot_size' => 'M-12cm',
            'unit_price' => 120,
            'quantity_available' => 5,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 2,
            ]],
        ]);
        app(NurserySaleService::class)->confirmSale($sale);

        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 3,
        ]);

        app(NurserySaleService::class)->cancelSaleById($sale->id);

        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 5,
        ]);
    }

    public function test_buyers_can_cancel_only_their_own_pending_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Aloe Vera',
            'botanical_name' => 'Aloe vera',
            'pot_size' => 'S-10cm',
            'unit_price' => 80,
            'quantity_available' => 5,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
            ]],
        ]);

        $this->actingAs($otherBuyer)
            ->post(route('buyer.orders.cancel', $sale))
            ->assertSessionHasErrors('sale');

        $this->actingAs($buyer)
            ->post(route('buyer.orders.cancel', $sale))
            ->assertRedirect(route('nursery-sales.index'));

        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertDatabaseHas('plant_inventories', [
            'plant_species_id' => $plant->id,
            'quantity_available' => 5,
        ]);
    }

    public function test_buyers_can_delete_only_their_own_completed_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Aloe Vera',
            'botanical_name' => 'Aloe vera',
            'pot_size' => 'S-10cm',
            'unit_price' => 80,
            'quantity_available' => 5,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
            ]],
        ]);
        app(NurserySaleService::class)->confirmSaleById($sale->id);
        app(NurserySaleService::class)->completeSaleById($sale->id);

        $this->actingAs($otherBuyer)
            ->delete(route('buyer.orders.destroy', $sale))
            ->assertSessionHasErrors('sale');

        $this->actingAs($buyer)
            ->delete(route('buyer.orders.destroy', $sale))
            ->assertRedirect(route('nursery-sales.index'));

        $this->assertDatabaseMissing('nursery_sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['nursery_sale_id' => $sale->id]);
    }

    public function test_buyers_cannot_delete_orders_that_are_not_completed(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Aloe Vera',
            'botanical_name' => 'Aloe vera',
            'pot_size' => 'S-10cm',
            'unit_price' => 80,
            'quantity_available' => 5,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
            ]],
        ]);

        $this->actingAs($buyer)
            ->delete(route('buyer.orders.destroy', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseHas('nursery_sales', ['id' => $sale->id]);
    }
}
