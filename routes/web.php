<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::post('/{guard}/logout', 'Auth\LoginController@logoutFrom')->name('logoutFrom');

Route::get('/', function () {
	// \Artisan::call('cache:clear');
    return view('leftlane');
});

require base_path('routes/projects/quickreads.php');
require base_path('routes/projects/pianolit.php');
