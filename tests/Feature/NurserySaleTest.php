<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\User;
use App\Services\NurserySaleService;
use App\Services\PlantSpeciesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NurserySaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_total_amount_is_persisted(): void
    {
        $buyer = Buyer::create([
            'buyer_name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150.00,
        ]);

        PlantInventory::create([
            'plant_species_id' => $plant->id,
            'quantity_available' => 20,
        ]);

        $sale = NurserySale::create([
            'buyer_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0.10,
            'dispatch_date' => '2026-10-30',
            'status' => 'pending',
            'total_amount' => 135.00,
        ]);

        $this->assertSame('135.00', (string) $sale->fresh()->total_amount);
    }

    public function test_authenticated_users_can_create_and_update_plant_species(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        $this->get(route('plant-species.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('plant-species/create'));

        $this->post(route('plant-species.store'), [
            'plant_name' => 'rose',
            'botanical_name' => 'rosa rubiginosa',
            'pot_size' => '6 inch',
            'unit_price' => 150,
        ])->assertRedirect(route('plant-species.index'));

        $plant = PlantSpecies::query()->firstOrFail();
        $this->assertSame('Rosa Rubiginosa', $plant->botanical_name);
        $this->get(route('plant-species.edit', $plant->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('plant-species/create')
                ->where('plant.id', $plant->id));

        $this->put(route('plant-species.update', $plant->id), [
            'plant_name' => 'garden rose',
            'botanical_name' => 'rosa rubiginosa',
            'pot_size' => '8 inch',
            'unit_price' => 175,
        ])->assertRedirect(route('plant-species.index'));

        $this->assertSame('Garden Rose', $plant->fresh()->plant_name);

        $this->delete(route('plant-species.destroy', $plant->id))
            ->assertRedirect(route('plant-species.index'));

        $this->assertDatabaseMissing('plant_species', ['id' => $plant->id]);
    }

    public function test_pending_sales_can_be_edited_and_deleted(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        $buyer = Buyer::create([
            'buyer_name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'buyer_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0.1,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 2,
            ]],
        ]);

        $this->get(route('nursery-sales.edit', $sale->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('nursery-sales/create')
                ->where('sale.id', $sale->id));

        $this->put(route('nursery-sales.update', $sale->id), [
            'buyer_id' => $buyer->id,
            'payment_method' => 'GCash',
            'discount_rate' => 0.2,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 3,
            ]],
        ])->assertRedirect(route('nursery-sales.index'));

        $sale->refresh();
        $this->assertSame('GCash', $sale->payment_method);
        $this->assertSame('360.00', (string) $sale->total_amount);

        $this->delete(route('nursery-sales.destroy', $sale->id))
            ->assertRedirect(route('nursery-sales.index'));

        $this->assertDatabaseMissing('nursery_sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['nursery_sale_id' => $sale->id]);
    }

    public function test_a_buyer_can_be_added_while_creating_a_nursery_sale(): void
    {
        $this->actingAs(User::factory()->create());

        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150,
        ]);

        $this->post(route('nursery-sales.store'), [
            'buyer_id' => null,
            'new_buyer' => [
                'buyer_name' => "<b>alice\t  buyer</b>",
                'contact_no' => '<i>0917-1234567</i>',
            ],
            'payment_method' => 'Cash',
            'discount_rate' => 0.1,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 2,
            ]],
        ])->assertRedirect(route('nursery-sales.index'));

        $buyer = Buyer::query()->firstOrFail();
        $this->assertSame('Alice Buyer', $buyer->buyer_name);
        $this->assertSame('+639171234567', $buyer->contact_no);

        $sale = NurserySale::query()->firstOrFail();
        $this->assertSame($buyer->id, $sale->buyer_id);
        $this->assertSame('270.00', (string) $sale->total_amount);
    }

    public function test_sale_confirmation_deducts_inventory_once(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        $buyer = Buyer::create([
            'buyer_name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150,
        ]);
        $plant->inventory()->update(['quantity_available' => 5]);

        $this->get(route('nursery-sales.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('nursery-sales/create')
                ->where('buyers.0.contact_no', '+639171234567'));

        $this->post(route('nursery-sales.store'), [
            'buyer_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0.1,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 2,
            ]],
        ])->assertRedirect(route('nursery-sales.index'));

        $sale = NurserySale::query()->firstOrFail();
        $this->assertSame('270.00', (string) $sale->total_amount);
        $this->assertSame(5, $plant->inventory->fresh()->quantity_available);

        $this->post(route('nursery-sales.confirm', $sale->id))
            ->assertRedirect(route('nursery-sales.index'));

        $this->assertSame(3, $plant->inventory->fresh()->quantity_available);
        $this->assertSame('confirmed', $sale->fresh()->status);

        try {
            app(NurserySaleService::class)->deleteSale($sale->id);
            $this->fail('Confirmed sales should not be deletable.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('nursery_sales', ['id' => $sale->id]);
        }

        try {
            app(PlantSpeciesService::class)->deletePlantSpecies($plant->id);
            $this->fail('Plants referenced by sales should not be deletable.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('plant_species', ['id' => $plant->id]);
        }
    }

    public function test_service_sanitizes_plant_fields_and_validates_values(): void
    {
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => "<b> fern\t  plant </b>",
            'botanical_name' => '<i>nephrolepis exaltata</i>',
            'pot_size' => ' 6   Inch ',
            'unit_price' => 24.50,
            'unexpected' => 'ignored',
        ]);

        $this->assertSame('Fern Plant', $plant->plant_name);
        $this->assertSame('Nephrolepis Exaltata', $plant->botanical_name);
        $this->assertSame('6 Inch', $plant->pot_size);

        try {
            app(PlantSpeciesService::class)->createPlantSpecies([
                'plant_name' => 123,
                'botanical_name' => 'Invalid Plant',
                'pot_size' => '6 Inch',
                'unit_price' => -1,
            ]);
            $this->fail('Invalid plant data should be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('plant_species', 1);
        }
    }

    public function test_service_sanitizes_sale_data(): void
    {
        $buyer = Buyer::create([
            'buyer_name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150.00,
        ]);

        $sale = app(NurserySaleService::class)->createSale([
            'buyer_id' => $buyer->id,
            'payment_method' => '<b>Cash</b>',
            'discount_rate' => 0.10,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
                'unexpected' => 'ignored',
            ]],
            'unexpected' => 'ignored',
        ]);

        $this->assertSame('Cash', $sale->payment_method);
        $this->assertSame('135.00', (string) $sale->total_amount);
    }

    public function test_store_request_validates_sale_data(): void
    {
        $this->actingAs(User::factory()->create());

        $buyer = Buyer::create([
            'buyer_name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150.00,
        ]);

        $this->from(route('nursery-sales.create'))
            ->post(route('nursery-sales.store'), [
                'buyer_id' => $buyer->id,
                'payment_method' => 'Cash',
                'discount_rate' => 1.5,
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ])
            ->assertSessionHasErrors('discount_rate');

        $this->assertDatabaseCount('nursery_sales', 0);
    }
}
