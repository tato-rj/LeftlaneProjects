<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Projects\VideoUploader\Admin;

class VideoUploaderApiTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::first()->createToken('$2y$10$H97n81fzf/2TTcFQWrS19OYOEwS2vfMso4YOjB8sHkICxFp1/wO.6');
    }
}
