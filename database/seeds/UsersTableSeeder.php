<?php

use Illuminate\Database\Seeder;
use App\Projects\PianoLit\User;

class UsersTableSeeder extends Seeder
{
	public function run()
	{
		for ($i = 0; $i < 40; $i++) {
			$date = now()->subDays($this->randomNumber());
			factory(User::class)->create([
				'created_at' => $date,
				'updated_at' => $date,
				'trial_ends_at' => $date->copy()->addWeek()
			]);	
		}
	}

	public function randomNumber()
	{
		if (mt_rand(0, 10) > 4)
			return mt_rand(8, 400);
		
		return mt_rand(2, 12);
	}
}
