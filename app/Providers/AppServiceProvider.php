<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Projects\PianoLit\Tag;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::if('manager', function () {
            return auth()->guard('pianolit-admin')->user()->role == 'manager';
        });

        Blade::if('editor', function () {
            return auth()->guard('pianolit-admin')->user()->role == 'editor';
        });

        Blade::if('created', function ($model) {
            return auth()->guard('pianolit-admin')->user()->id == $model->creator_id || auth()->guard('pianolit-admin')->user()->role == 'manager';
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
