<?php

namespace Tests\Feature\Api;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset le cache des permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);
    }
}
