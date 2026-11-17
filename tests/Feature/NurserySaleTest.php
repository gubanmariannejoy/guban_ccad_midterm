<?php

namespace Tests\Feature;

use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\User;
use App\Services\NurserySaleService;
use App\Services\PlantSpeciesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NurserySaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_total_amount_is_persisted(): void
    {
        $buyer = $this->createBuyer();

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150.00,
        ]);

        PlantInventory::create([
            'plant_species_id' => $plant->id,
            'quantity_available' => 20,
        ]);

        $sale = NurserySale::create([
            'user_id' => $buyer->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0.10,
            'dispatch_date' => '2026-10-30',
            'status' => 'pending',
            'total_amount' => 135.00,
        ]);

        $this->assertSame('135.00', (string) $sale->fresh()->total_amount);
    }

    public function test_nursery_sales_index_handles_sales_without_a_linked_buyer_account(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $sale = NurserySale::create([
            'user_id' => null,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        $this->get(route('nursery-sales.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('nursery-sales/index')
                ->where('sales.data.0.id', $sale->id));
    }

    public function test_legacy_buyers_and_sales_migrate_to_matching_users(): void
    {
        $migration = require database_path('migrations/2026_11_12_091000_use_users_as_buyers.php');
        $migration->down();

        $user = User::factory()->create([
            'name' => 'Legacy Buyer',
            'contact_no' => null,
        ]);
        $buyerId = DB::table('buyers')->insertGetId([
            'buyer_name' => ' legacy buyer ',
            'contact_no' => '09171234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $saleId = DB::table('nursery_sales')->insertGetId([
            'buyer_id' => $buyerId,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'total_amount' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasTable('buyers'));
        $this->assertDatabaseHas('nursery_sales', [
            'id' => $saleId,
            'user_id' => $user->id,
        ]);
        $this->assertSame('+639171234567', $user->fresh()->contact_no);
    }

    public function test_unmatched_legacy_buyers_are_preserved_as_user_accounts(): void
    {
        $migration = require database_path('migrations/2026_11_12_091000_use_users_as_buyers.php');
        $migration->down();

        $buyerId = DB::table('buyers')->insertGetId([
            'buyer_name' => 'No Matching User',
            'contact_no' => '09171234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasTable('buyers'));
        $this->assertDatabaseHas('users', [
            'name' => 'No Matching User',
            'email' => "legacy-buyer-$buyerId@example.invalid",
            'contact_no' => '+639171234567',
        ]);
    }

    public function test_admins_can_create_and_update_plant_species(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('plant-species.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('plant-species/create'));

        $this->post(route('plant-species.store'), [
            'plant_name' => 'rose',
            'botanical_name' => 'rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
            'quantity_available' => 12,
        ])->assertRedirect(route('plant-species.index'));

        $plant = PlantSpecies::query()->firstOrFail();
        $this->assertSame('Rosa Rubiginosa', $plant->botanical_name);
        $this->assertSame(12, $plant->inventory->quantity_available);
        $this->get(route('plant-species.edit', $plant->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('plant-species/create')
                ->where('plant.id', $plant->id)
                ->where('plant.inventory.quantity_available', 12));

        $this->put(route('plant-species.update', $plant->id), [
            'plant_name' => 'garden rose',
            'botanical_name' => 'rosa rubiginosa',
            'pot_size' => 'XL-20cm',
            'unit_price' => 175,
            'quantity_available' => 20,
        ])->assertRedirect(route('plant-species.index'));

        $this->assertSame('Garden Rose', $plant->fresh()->plant_name);
        $this->assertSame(20, $plant->inventory()->firstOrFail()->quantity_available);

        $this->delete(route('plant-species.destroy', $plant->id))
            ->assertRedirect(route('plant-species.index'));

        $this->assertDatabaseMissing('plant_species', ['id' => $plant->id]);
    }

    public function test_plant_species_accepts_only_the_available_pot_sizes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $plantData = [
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => '6 Inch',
            'unit_price' => 150,
            'quantity_available' => 10,
        ];

        $this->from(route('plant-species.create'))
            ->post(route('plant-species.store'), $plantData)
            ->assertSessionHasErrors('pot_size');

        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            ...$plantData,
            'pot_size' => 'XS-8cm',
        ]);

        $this->from(route('plant-species.edit', $plant->id))
            ->put(route('plant-species.update', $plant->id), [
                ...$plantData,
                'pot_size' => '6 Inch',
            ])
            ->assertSessionHasErrors('pot_size');

        try {
            app(PlantSpeciesService::class)->updatePlantSpecies($plant->id, [
                ...$plantData,
                'pot_size' => '6 Inch',
            ]);
            $this->fail('Unsupported pot sizes should be rejected by the service.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pot_size', $exception->errors());
        }

        $this->assertSame('XS-8cm', $plant->fresh()->pot_size);
    }

    public function test_plant_stock_cannot_exceed_100_on_create_or_update(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $plantData = [
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ];

        $this->from(route('plant-species.create'))
            ->post(route('plant-species.store'), [
                ...$plantData,
                'quantity_available' => 101,
            ])
            ->assertSessionHasErrors('quantity_available');

        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            ...$plantData,
            'quantity_available' => 100,
        ]);

        $this->from(route('plant-species.edit', $plant->id))
            ->put(route('plant-species.update', $plant->id), [
                ...$plantData,
                'quantity_available' => 101,
            ])
            ->assertSessionHasErrors('quantity_available');

        $this->assertSame(100, $plant->inventory->fresh()->quantity_available);

        try {
            app(PlantSpeciesService::class)->updatePlantSpecies($plant->id, [
                ...$plantData,
                'quantity_available' => 101,
            ]);
            $this->fail('Plant stock above 100 should be rejected by the service.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'quantity_available',
                $exception->errors()
            );
        }
    }

    public function test_pending_sales_can_be_edited_and_deleted(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $buyer = $this->createBuyer();
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ]);
        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
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
            'user_id' => $buyer->id,
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
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ]);

        $this->post(route('nursery-sales.store'), [
            'user_id' => null,
            'new_buyer' => [
                'name' => "<b>alice\t  buyer</b>",
                'email' => 'alice.new@example.test',
                'contact_no' => '09171234567',
                'password' => 'temporary-secret',
            ],
            'payment_method' => 'Cash',
            'discount_rate' => 0.1,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 2,
            ]],
        ])->assertRedirect(route('nursery-sales.index'));

        $buyer = User::where('email', 'alice.new@example.test')->firstOrFail();
        $this->assertSame('Alice Buyer', $buyer->name);
        $this->assertSame('+639171234567', $buyer->contact_no);
        $this->assertTrue(Hash::check('temporary-secret', $buyer->password));

        $sale = NurserySale::query()->firstOrFail();
        $this->assertSame($buyer->id, $sale->user_id);
        $this->assertSame('270.00', (string) $sale->total_amount);
    }

    public function test_buyer_contact_number_must_contain_exactly_eleven_digits(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ]);

        foreach (['0917123456', '091712345678', '0917-1234567', '0917123456a'] as $contactNumber) {
            $this->post(route('nursery-sales.store'), [
                'user_id' => null,
                'new_buyer' => [
                    'name' => 'Alice Buyer',
                    'email' => 'alice.invalid.'.strlen($contactNumber).'@example.test',
                    'contact_no' => $contactNumber,
                    'password' => 'temporary-secret',
                ],
                'payment_method' => 'Cash',
                'discount_rate' => 0.1,
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ])->assertSessionHasErrors('new_buyer.contact_no');
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_sale_confirmation_deducts_inventory_once(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'name' => 'Zoe Administrator',
        ]));

        $buyer = $this->createBuyer();
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ]);
        $plant->inventory()->update(['quantity_available' => 5]);

        $this->get(route('nursery-sales.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('nursery-sales/create')
                ->where('buyers.0.contact_no', '+639171234567'));

        $this->post(route('nursery-sales.store'), [
            'user_id' => $buyer->id,
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
        $this->get(route('nursery-sales.index'))
            ->assertInertia(fn ($page) => $page
                ->where('sales.data.0.sale_items.0.plant_species.plant_name', 'Rose')
                ->where('sales.data.0.sale_items.0.quantity_bought', 2));

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

    public function test_sale_confirmation_rejects_quantities_above_available_stock(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $buyer = $this->createBuyer();
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
            'quantity_available' => 1,
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

        $this->from(route('nursery-sales.index'))
            ->post(route('nursery-sales.confirm', $sale->id))
            ->assertRedirect(route('nursery-sales.index'))
            ->assertSessionHasErrors('inventory');

        $this->assertSame(1, $plant->inventory->fresh()->quantity_available);
        $this->assertSame('pending', $sale->fresh()->status);
    }

    public function test_service_sanitizes_plant_fields_and_validates_values(): void
    {
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => "<b> fern\t  plant </b>",
            'botanical_name' => '<i>nephrolepis exaltata</i>',
            'pot_size' => ' S-10cm ',
            'unit_price' => 24.50,
            'unexpected' => 'ignored',
        ]);

        $this->assertSame('Fern Plant', $plant->plant_name);
        $this->assertSame('Nephrolepis Exaltata', $plant->botanical_name);
        $this->assertSame('S-10cm', $plant->pot_size);

        try {
            app(PlantSpeciesService::class)->createPlantSpecies([
                'plant_name' => 123,
                'botanical_name' => 'Invalid Plant',
                'pot_size' => 'S-10cm',
                'unit_price' => -1,
            ]);
            $this->fail('Invalid plant data should be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('plant_species', 1);
        }
    }

    public function test_service_sanitizes_sale_data(): void
    {
        $buyer = $this->createBuyer();

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150.00,
        ]);

        $sale = app(NurserySaleService::class)->createSale([
            'user_id' => $buyer->id,
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

    public function test_sale_service_rejects_invalid_data_without_a_form_request(): void
    {
        $buyer = $this->createBuyer();
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
        ]);

        foreach ([
            [
                'user_id' => $buyer->id,
                'payment_method' => 'Cash',
                'dispatch_date' => '2030-01-01',
                'discount_rate' => 0.1,
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ],
            [
                'user_id' => $buyer->id,
                'payment_method' => 'Cash',
                'discount_rate' => 1.5,
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ],
            [
                'new_buyer' => [
                    'name' => 'Alice Buyer',
                    'email' => 'invalid-buyer@example.test',
                    'contact_no' => '12345',
                    'password' => 'temporary-secret',
                ],
                'payment_method' => 'Cash',
                'discount_rate' => 0.1,
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ],
        ] as $invalidSale) {
            try {
                app(NurserySaleService::class)->createSale($invalidSale);
                $this->fail('Invalid sale data should be rejected.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('nursery_sales', 0);
            }
        }

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_store_request_validates_sale_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $buyer = $this->createBuyer();

        $plant = PlantSpecies::create([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150.00,
        ]);

        $this->from(route('nursery-sales.create'))
            ->post(route('nursery-sales.store'), [
                'user_id' => $buyer->id,
                'payment_method' => 'Cash',
                'discount_rate' => 1.5,
                'dispatch_date' => '2030-01-01',
                'items' => [[
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => 1,
                ]],
            ])
            ->assertSessionHasErrors(['discount_rate', 'dispatch_date']);

        $this->assertDatabaseCount('nursery_sales', 0);
    }

    private function createBuyer(): User
    {
        return User::factory()->create([
            'name' => 'Alice Buyer',
            'contact_no' => '09171234567',
        ]);
    }
}
