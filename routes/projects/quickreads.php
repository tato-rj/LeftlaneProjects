<?php

Route::prefix('/quickreads')->name('quickreads.admin.')->group(function() {
	Route::get('/login', 'Auth\Quickreads\LoginController@showLoginForm')->name('login');
	Route::post('/login', 'Auth\Quickreads\LoginController@login')->name('login.submit');

	Route::prefix('/users')->group(function() {
		Route::post('/register', 'Projects\Quickreads\UsersController@register');
		Route::post('/facebook', 'Projects\Quickreads\UsersController@store');
	});
});

Route::middleware('auth:quickreads-admin')->prefix('/quickreads')->name('quickreads.')->group(function() {
	Route::get('', 'Projects\Quickreads\AdminController@index');
	
	Route::get('/statistics', 'Projects\Quickreads\AdminController@statistics');

	Route::prefix('/users')->group(function() {
		Route::get('', 'Projects\Quickreads\UsersController@index');
		Route::delete('/{user}', 'Projects\Quickreads\UsersController@destroy');
	});

	Route::prefix('/stories')->group(function() {
		Route::get('/add', 'Projects\Quickreads\StoriesController@create');
		Route::get('/edit', 'Projects\Quickreads\StoriesController@select');
		Route::get('/edit/{story}', 'Projects\Quickreads\StoriesController@edit');
		Route::get('/delete', 'Projects\Quickreads\StoriesController@delete');

		Route::post('', 'Projects\Quickreads\StoriesController@store');
		Route::patch('/{story}', 'Projects\Quickreads\StoriesController@update');
		Route::delete('/{story}', 'Projects\Quickreads\StoriesController@destroy');
	});

	Route::prefix('/authors')->group(function() {
		Route::get('/add', 'Projects\Quickreads\AuthorsController@create');
		Route::get('/edit', 'Projects\Quickreads\AuthorsController@select');
		Route::get('/edit/{author}', 'Projects\Quickreads\AuthorsController@edit');
		Route::get('/delete', 'Projects\Quickreads\AuthorsController@delete');

		Route::post('', 'Projects\Quickreads\AuthorsController@store');
		Route::patch('/{author}', 'Projects\Quickreads\AuthorsController@update');
		Route::delete('/{author}', 'Projects\Quickreads\AuthorsController@destroy');
	});

	Route::prefix('/categories')->group(function() {
		Route::get('/add', 'Projects\Quickreads\CategoriesController@create');
		Route::get('/edit', 'Projects\Quickreads\CategoriesController@select');
		Route::get('/edit/{category}', 'Projects\Quickreads\CategoriesController@edit');
		Route::get('/delete', 'Projects\Quickreads\CategoriesController@delete');

		Route::post('', 'Projects\Quickreads\CategoriesController@store');
		Route::patch('/{category}', 'Projects\Quickreads\CategoriesController@update');
		Route::delete('/{category}', 'Projects\Quickreads\CategoriesController@destroy');
	});
});

// App Routes
Route::get('/quickreads/app/explore', 'Projects\Quickreads\StoriesController@explore');
Route::get('/quickreads/app/stories', 'Projects\Quickreads\StoriesController@app');
Route::get('/quickreads/app/stories/text', 'Projects\Quickreads\StoriesController@text');
Route::get('/quickreads/app/stories/{storyTitle}/rating', 'Projects\Quickreads\RatingsController@show');
Route::get('/quickreads/app/categories', 'Projects\Quickreads\CategoriesController@app');
Route::get('/quickreads/app/authors', 'Projects\Quickreads\AuthorsController@app');
Route::get('/quickreads/app/users', 'Projects\Quickreads\UsersController@app');
Route::get('/quickreads/app/login/{email}/{password}', 'Projects\Quickreads\UsersController@appLogin');

Route::get('/quickreads/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('/quickreads/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('/quickreads/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('/quickreads/password/reset', 'Auth\ResetPasswordController@reset');

Route::post('/quickreads/app/records/purchase', 'Projects\Quickreads\UserPurchaseRecordController@store');
Route::post('/quickreads/app/stories/views', 'Projects\Quickreads\StoriesController@incrementViews');
