<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\Projects\PianoLit\Admin::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Projects\PianoLit\Piece::class, function (Faker $faker) {
	$catalogues = catalogues();
	$keys = keys();

    return [
            'name' => 'test',
            'nickname' => $faker->name,
            'catalogue_name' => $catalogues[rand(0, count($catalogues) - 1)],
            'catalogue_number' => rand(1,100),
            'collection_name' => $faker->name,
            'collection_number' => rand(1,10),
            'movement_number' => rand(1,4),
            'key' => $keys[rand(0, count($keys) - 1)],
            'tips' => '',
            'curiosity' => '',
            'audio_path' => '',
            'audio_path_rh' => '',
            'audio_path_lh' => '',
            'itunes' => '',
            'youtube' => '',
            'score_path' => '',
            'score_editor' => '',
            'score_publisher' => '',
            'score_copyright' => '',
            'composer_id' => 1,
            'creator_id' => function() {
            	return factory('App\Projects\PianoLit\Admin')->create()->id;
            },
    ];
});

$factory->define(App\Projects\PianoLit\Country::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'nationality' => $faker->word
    ];
});

$factory->define(App\Projects\PianoLit\Composer::class, function (Faker $faker) {
    $periods = periods();

    return [
            'name' => $faker->name,
            'biography' => $faker->paragraph,
            'curiosity' => $faker->paragraph,
            'period' => $periods[rand(0, count($periods) - 1)],
            'country_id' => function() {
                return factory('App\Projects\PianoLit\Country')->create()->id;
            },
            'date_of_birth' => $faker->date(),
            'date_of_death' => $faker->date(),
            'creator_id' => function() {
                return factory('App\Projects\PianoLit\Admin')->create()->id;
            },
    ];
});

$factory->define(App\Projects\PianoLit\Tag::class, function (Faker $faker) {
    return [
        'type' => $faker->word,
        'name' => $faker->word,
        'creator_id' => function() {
            return factory('App\Projects\PianoLit\Admin')->create()->id;
        },
    ];
});
