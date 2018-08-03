<?php

namespace Tests;

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp()
    {
        parent::setUp();

        $this->disableExceptionHandling();
    }

    public function disableExceptionHandling()
    {
        $this->oldExceptionHandler = $this->app->make(ExceptionHandler::class);

        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct() {}
            public function report(\Exception $exception) {}
            public function render($request, \Exception $exception) {
                throw $exception;
            }
        });
    }

    public function withExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, $this->oldExceptionHandler);
        return $this;
    }

    public function register($email = 'doe@email.com')
    {
        return $this->post('/quickreads/users/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => 'secret'
        ]);
    }
}
