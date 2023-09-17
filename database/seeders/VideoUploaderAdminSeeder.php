<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Projects\VideoUploader\Admin;

class VideoUploaderAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::create([
            'name' => 'Arthur Villar',
            'email' => 'arthurvillar@gmail.com',
            'password' => \Hash::make('pass')
        ]);
    }
}
