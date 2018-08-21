<?php

namespace Tests;

use Tests\Utilities\ExceptionHandling;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, ExceptionHandling, DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        $this->disableExceptionHandling();
    }

    protected function logout()
    {
        Auth::logout();
    }
}
