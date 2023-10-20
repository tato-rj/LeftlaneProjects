<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearTemporaryUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temporary:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the temporary uploads directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $files = \Storage::disk('public')->files('temporary');

        foreach ($files as $file) {
            $time = \Storage::disk('public')->lastModified($file);

            if (now()->gt(carbon($time)->addHours(2)))
                \Storage::disk('public')->delete($file);
        }

        $this->info('Temporary directory deleted');
    }
}
