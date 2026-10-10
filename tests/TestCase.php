<?php

namespace Tests;

use Tests\Utilities\ExceptionHandling;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseMigrations, ExceptionHandling;

    // Each test gets fresh isolated databases, discarded after the test.
    // Do not run unrelated historical rollback methods (one has a typo).
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());
        $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
    }

    public function setUp() : void
    {
        parent::setUp();

        $this->disableExceptionHandling();
    }
}
