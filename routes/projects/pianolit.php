<?php

Route::prefix('/piano-lit')->name('pianolit.admin.')->group(function() {
	Route::get('/login', 'Auth\PianoLit\LoginController@showLoginForm')->name('login');
	Route::post('/login', 'Auth\PianoLit\LoginController@login')->name('login.submit');
});

Route::middleware('auth:pianolit-admin')->prefix('/piano-lit')->name('piano-lit.')->group(function() {
	Route::get('', 'Projects\PianoLit\AdminController@index');
	
	Route::get('/statistics', 'Projects\PianoLit\AdminController@statistics');

	Route::prefix('/users')->group(function() {
		Route::get('', 'Projects\PianoLit\UsersController@index');
		Route::post('/register', 'Projects\PianoLit\UsersController@register');
		Route::post('/facebook', 'Projects\PianoLit\UsersController@store');
	});

	Route::prefix('/editors')->name('editors.')->group(function() {
		Route::get('', 'Projects\PianoLit\EditorsController@index')->name('index');
		Route::get('/{editor}', 'Projects\PianoLit\EditorsController@edit')->name('edit');

		Route::post('', 'Projects\PianoLit\EditorsController@store')->name('store');
		Route::patch('/{editor}', 'Projects\PianoLit\EditorsController@update')->name('update');
		Route::delete('/{editor}', 'Projects\PianoLit\EditorsController@destroy')->name('destroy');
	});

	Route::prefix('/composers')->group(function() {
		Route::get('/', 'Projects\PianoLit\ComposersController@index');
		Route::get('/{composer}', 'Projects\PianoLit\ComposersController@edit');

		Route::post('', 'Projects\PianoLit\ComposersController@store');
		Route::patch('/{composer}', 'Projects\PianoLit\ComposersController@update');
		Route::delete('/{composer}', 'Projects\PianoLit\ComposersController@destroy');
	});

	Route::prefix('/pieces')->name('pieces.')->group(function() {
		Route::get('/', 'Projects\PianoLit\PiecesController@index')->name('index');
		Route::get('/search', 'Projects\PianoLit\PiecesController@search')->name('search');
		Route::get('/tour', 'Projects\PianoLit\PiecesController@tour')->name('tour');
		Route::get('/add', 'Projects\PianoLit\PiecesController@create');
		Route::get('/{piece}', 'Projects\PianoLit\PiecesController@edit')->name('edit');

		Route::post('/single-lookup', 'Projects\PianoLit\PiecesController@singleLookup')->name('single-lookup');
		Route::post('/multi-lookup', 'Projects\PianoLit\PiecesController@multiLookup')->name('multi-lookup');
		Route::post('/validate-name', 'Projects\PianoLit\PiecesController@validateName')->name('validate-name');

		Route::post('', 'Projects\PianoLit\PiecesController@store');
		Route::patch('/{piece}', 'Projects\PianoLit\PiecesController@update');
		Route::delete('/{piece}', 'Projects\PianoLit\PiecesController@destroy');
	});

	Route::prefix('/tags')->name('tags.')->group(function() {
		Route::get('/', 'Projects\PianoLit\TagsController@index')->name('index');
		Route::get('/add', 'Projects\PianoLit\TagsController@create');
		Route::get('/{tag}', 'Projects\PianoLit\TagsController@edit')->name('edit');

		Route::post('', 'Projects\PianoLit\TagsController@store');
		Route::patch('/{tag}', 'Projects\PianoLit\TagsController@update');
		Route::delete('/{tag}', 'Projects\PianoLit\TagsController@destroy');
	});
});

// App Routes
Route::prefix('/piano-lit/api')->name('piano-lit.api.')->group(function() {
	Route::post('/search', 'Projects\PianoLit\ApiController@search')->name('search');
	Route::post('/tour', 'Projects\PianoLit\ApiController@tour')->name('tour');
	Route::get('/discover', 'Projects\PianoLit\ApiController@discover')->name('discover');
	Route::get('/pieces', 'Projects\PianoLit\ApiController@pieces')->name('pieces');
	Route::post('/pieces/find', 'Projects\PianoLit\ApiController@piece')->name('piece');
	Route::get('/composers', 'Projects\PianoLit\ApiController@composers')->name('composers');
	Route::get('/tags', 'Projects\PianoLit\ApiController@tags')->name('tags');
});
