<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NurserySaleService;
use App\Services\PlantSpeciesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_welcome_page()
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('welcome'));
    }

    public function test_authenticated_users_visiting_the_home_page_are_redirected_to_the_dashboard()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('buyer-dashboard')
                ->has('sales', 0)
            );
    }

    public function test_admins_see_the_admin_dashboard()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin-dashboard')
                ->where('stats.plants', 0)
                ->where('stats.buyers', 0)
                ->where('stats.sales', 0)
                ->where('stats.pendingSales', 0)
                ->where('stats.availableStock', 0)
                ->where('stats.confirmedRevenue', 0)
            );
    }

    public function test_buyers_cannot_access_admin_pages()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/plant-species')->assertForbidden();
    }

    public function test_admins_can_view_inventory(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 150,
            'quantity_available' => 7,
        ]);

        $this->get(route('plant-inventory.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('plant-inventory/index')
                ->where('plants.data.0.plant_name', 'Rose')
                ->where('plants.data.0.inventory.quantity_available', 7));
    }

    public function test_admins_can_view_buyer_information_but_buyers_cannot_open_the_directory(): void
    {
        $this->withoutVite();
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'name' => 'Alice Buyer',
            'email' => 'alice@example.test',
            'contact_no' => '+639171234567',
        ]);
        User::factory()->create(['role' => 'admin', 'name' => 'Nursery Admin']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('buyers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('buyers/index')
                ->has('buyers.data', 1)
                ->where('buyers.data.0.id', $buyer->id)
                ->where('buyers.data.0.name', 'Alice Buyer')
                ->where('buyers.data.0.email', 'alice@example.test')
                ->where('buyers.data.0.contact_no', '+639171234567')
                ->where('buyers.data.0.sales_count', 0));

        $this->actingAs($buyer)
            ->get(route('buyers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_be_promoted_from_the_console_for_a_registered_account(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);

        $this->assertSame(0, Artisan::call('nursery:make-admin', ['email' => $user->email]));
        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_dashboard_counts_only_confirmed_sales_as_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plant = app(PlantSpeciesService::class)->createPlantSpecies([
            'plant_name' => 'Rose',
            'botanical_name' => 'Rosa rubiginosa',
            'pot_size' => 'S-10cm',
            'unit_price' => 100,
            'quantity_available' => 8,
        ]);
        $saleData = [
            'user_id' => $admin->id,
            'payment_method' => 'Cash',
            'discount_rate' => 0,
            'items' => [[
                'plant_species_id' => $plant->id,
                'quantity_bought' => 1,
            ]],
        ];
        app(NurserySaleService::class)->createSale($saleData);
        $confirmedSale = app(NurserySaleService::class)->createSale($saleData);
        app(NurserySaleService::class)->confirmSale($confirmedSale);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.sales', 2)
                ->where('stats.pendingSales', 1)
                ->where('stats.availableStock', 7)
                ->where('stats.confirmedRevenue', 100));
    }
}
