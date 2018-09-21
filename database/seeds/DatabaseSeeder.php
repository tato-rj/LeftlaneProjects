<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
	protected $toTruncate = ['users', 'subscriptions', 'favorites'];

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
    	foreach ($this->toTruncate as $table) {
    		DB::table($table)->truncate();
    	}

        $this->call(UsersTableSeeder::class);
        $this->call(SubscriptionsTableSeeder::class);
    }
}
