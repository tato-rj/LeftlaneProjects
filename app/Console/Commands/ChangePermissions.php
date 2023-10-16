<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChangePermissions extends Command
{
    protected $signature = 'change:permissions {folder}';
    protected $description = 'Change permissions of a folder to 777';

    public function handle()
    {
        $folder = $this->argument('folder');
        $path = public_path($folder); // Adjust the path as needed

        if (is_dir($path)) {
            chmod($path, 0777);
            $this->info("Permissions of the $folder folder have been changed to 777.");
        } else {
            $this->error("The folder $folder does not exist.");
        }
    }
}
