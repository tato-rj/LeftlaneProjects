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
    return view('leftlane/index');
});

require base_path('routes/projects/quickreads.php');

// A separate reset landing page for the v2 broker; existing reset URLs are untouched.
Route::get('/quickreads/v2/password/reset', function () {
    return response()->view('projects.quickreads.v2.password-reset')->header('Cache-Control', 'no-store');
})->name('quickreads.v2.password.form');

Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');

    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
});

Route::get('/docs/SWIFT_V2_MIGRATION.md', function () {
    $path = base_path('docs/SWIFT_V2_MIGRATION.md');

    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
});
