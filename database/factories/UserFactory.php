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

$factory->define(App\Projects\PianoLit\User::class, function (Faker $faker) {
    $age = ['under 13', '13 to 18', '18 to 25', '25 to 35', '35 to 45', '45 and up'];
    $experience = ['none', 'little', 'a lot'];
    $occupation = ['student', 'teacher', 'music lover'];
    $favorite = \App\Projects\PianoLit\Piece::inRandomOrder()->first();
    $locale = ['en_US', 'en_GB', 'it_CH', 'it_IT', 'fr_BE', 'fr_CA', 'pt_BR'];

    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'password' => \Hash::make('secret'),
        'locale' => $locale[array_rand($locale)],
        'age_range' => $age[array_rand($age)],
        'experience' => $experience[array_rand($experience)],
        'preferred_piece_id' => $favorite->id,
        'occupation' => $occupation[array_rand($occupation)],
        'trial_ends_at' => \Carbon\Carbon::now()->addWeek()
    ];
});

$factory->define(App\Projects\PianoLit\Admin::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'role' => $faker->word,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Projects\PianoLit\Piece::class, function (Faker $faker) {
	$catalogues = catalogues();
	$keys = keys();

    return [
            'name' => $faker->name,
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
            'composer_id' => function() {
                return factory('App\Projects\PianoLit\Composer')->create()->id;
            },
            'creator_id' => function() {
            	return factory('App\Projects\PianoLit\Admin')->create()->id;
            }
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
