<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoutingTest extends TestCase
{
    public function test_welcome_page_introduces_the_revenue_workspace_and_links_to_sign_in(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Every collection.')
            ->assertSee('Accounted for.')
            ->assertSee(route('login'));
    }

    public function test_guest_authentication_pages_render_and_dashboard_redirects_to_login(): void
    {
        $this->withoutVite();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in');

        $this->get(route('password.request'))
            ->assertOk();

        $this->get(route('password.reset', ['token' => 'reset-token']))
            ->assertOk();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
