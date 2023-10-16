<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearTemporaryUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:clear';

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
        \Storage::disk('public')->deleteDirectory('temporary');
        \Storage::disk('public')->makeDirectory('temporary');

        $this->info('Temporary uploads directory deleted');
    }
}
