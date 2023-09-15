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
	// return phpversion();
	// \Artisan::call('cache:clear');
    return view('leftlane/index');
});

require base_path('routes/projects/quickreads.php');
