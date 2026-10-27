<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportsRoutingTest extends TestCase
{
    public function test_report_routes_redirect_guests_to_login(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));

        $this->get(route('reports.exports'))
            ->assertRedirect(route('login'));
    }
}
