<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get('/dashboard')->assertOk();
    }
}
