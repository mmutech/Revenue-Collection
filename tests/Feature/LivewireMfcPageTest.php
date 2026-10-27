<?php

namespace Tests\Feature;

use Livewire\Livewire;
use Tests\TestCase;

class LivewireMfcPageTest extends TestCase
{
    public function test_all_full_page_components_resolve_as_multi_file_components(): void
    {
        $components = [
            'pages::admin.index',
            'pages::audit.index',
            'pages::auth.forgot-password',
            'pages::auth.login',
            'pages::auth.reset-password',
            'pages::collections.index',
            'pages::collections.queue',
            'pages::collections.show',
            'pages::dashboard.index',
            'pages::discrepancies.index',
            'pages::discrepancies.show',
            'pages::lodgements.create',
            'pages::lodgements.index',
            'pages::lodgements.show',
            'pages::notifications.index',
            'pages::payments.create',
            'pages::payments.index',
            'pages::payments.show',
            'pages::profile',
            'pages::reconciliation.index',
            'pages::reconciliation.show',
            'pages::reports.exports',
            'pages::reports.index',
            'pages::shifts.index',
            'pages::treasury.index',
            'pages::treasury.show',
            'pages::treatment.create',
            'pages::treatment.index',
            'pages::treatment.show',
        ];

        foreach ($components as $component) {
            $this->assertTrue(Livewire::exists($component), "Livewire component [{$component}] could not be resolved.");
        }
    }
}
