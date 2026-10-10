<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\Api\V2\User;
use Illuminate\Support\Facades\{DB, Hash};
use Tests\TestCase;

abstract class QuickreadsTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $db = DB::connection('quickreads');
        $now = now();
        $db->table('authors')->insert([
            ['id' => 1, 'slug' => 'writer-one', 'name' => 'Writer One', 'life' => 'Long biography', 'born_in' => '1900', 'died_in' => '1980', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'slug' => 'writer-two', 'name' => 'Writer Two', 'life' => '', 'born_in' => null, 'died_in' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $db->table('categories')->insert([
            ['id' => 1, 'slug' => 'drama', 'category' => 'Drama', 'sorting_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'slug' => 'classics', 'category' => 'Classics', 'sorting_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        foreach ([1 => '9', 2 => '10', 3 => '19', 4 => 'unknown', 5 => '20', 6 => '10.5'] as $id => $time) {
            $db->table('stories')->insert(['id' => $id, 'slug' => 'story-'.$id, 'title' => 'Story '.$id,
                'summary' => 'A short summary', 'text' => "First paragraph 🐈\r\n\r\nCafé e\u{0301} 𐐀\nLast paragraph.",
                'time' => $time, 'cost' => '0', 'is_classic' => $id % 2 === 0,
                'author_id' => $id <= 4 ? 1 : 2, 'category_id' => $id % 2 === 0 ? 2 : 1,
                'views' => 0, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    protected function user(string $email = 'reader@example.test'): User
    {
        return User::create(['slug' => 'reader', 'first_name' => 'Reader', 'last_name' => 'Person',
            'email' => $email, 'password' => Hash::make('password123'), 'facebook_id' => 'alias-'.$email]);
    }

    protected function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test', ['quickreads:v2'], now()->addDay())->plainTextToken];
    }
}
