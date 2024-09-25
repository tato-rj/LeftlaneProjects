<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Projects\PianoLit\Tag;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        \Blade::include('components.filters.layout', 'filters');
        \Blade::include('components.fontawesome', 'fa');
        \Blade::include('components.table.layout', 'table');
        \Blade::include('components.table.searchbar');
        \Blade::aliasComponent('components.core.form', 'form');
        \Blade::include('components.core.forms.input');
        \Blade::include('components.core.forms.toggle');
        \Blade::include('components.core.forms.textarea');
        \Blade::aliasComponent('components.core.forms.select');
        \Blade::aliasComponent('components.core.forms.checkbox');
        \Blade::include('components.core.forms.radio');
        \Blade::include('components.core.forms.option');
        \Blade::include('components.core.forms.submit');
        \Blade::include('components.core.forms.label');
        \Blade::include('components.core.forms.feedback');
        \Blade::include('components.core.forms.password');
        
        Blade::if('manager', function () {
            return auth()->guard('pianolit-admin')->user()->role == 'manager';
        });

        Blade::if('editor', function () {
            return auth()->guard('pianolit-admin')->user()->role == 'editor';
        });

        Blade::if('created', function ($model) {
            return auth()->guard('pianolit-admin')->user()->id == $model->creator_id || auth()->guard('pianolit-admin')->user()->role == 'manager';
        });

        Blade::if('env', function ($environment) {
            return app()->environment($environment);
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
