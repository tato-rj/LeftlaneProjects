<?php

Route::prefix('videouploader')->name('videouploader.')->group(function() {
	Route::get('login', 'Auth\VideoUploader\LoginController@showLoginForm')->name('login');

	Route::post('login', 'Auth\VideoUploader\LoginController@login')->name('login.submit');

	Route::middleware('api.token')->post('upload', 'Projects\VideoUploader\VideosController@upload')->name('upload');

	Route::middleware('api.token')->delete('delete', 'Projects\VideoUploader\VideosController@destroy')->name('delete');

	Route::middleware('auth:videouploader-admin')->namespace('Projects\VideoUploader')->group(function() {
		Route::get('', 'AdminController@index')->name('home');

		Route::prefix('tokens')->name('tokens.')->group(function() {

			Route::get('', 'ApiTokensController@index')->name('index');

			Route::post('', 'ApiTokensController@store')->name('store');

			Route::delete('revoke', 'ApiTokensController@revoke')->name('revoke');

		});

		Route::prefix('webhook')->name('webhook.')->group(function() {

			Route::post('{video}', 'WebhookController@resend')->name('resend');

		});
	});
});