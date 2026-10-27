<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MissingRoutesTest extends TestCase
{
    public function test_navigation_and_document_routes_are_registered(): void
    {
        $routeNames = [
            'notifications.index',
            'payments.receipt',
            'documents.lodgement',
            'discrepancies.index',
            'discrepancies.show',
            'treatment.index',
            'treatment.create',
            'treatment.show',
            'admin.users',
            'admin.branches',
            'admin.reference',
            'admin.workflows',
            'admin.roles',
            'admin.settings',
            'audit.index',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route [{$routeName}] is not registered.");
        }
    }

    public function test_protected_missing_pages_redirect_guests_to_login(): void
    {
        $paths = [
            '/notifications',
            '/payments/create',
            '/discrepancies',
            '/treatment',
            '/treatment/create',
            '/admin/users',
            '/audit',
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }
}
