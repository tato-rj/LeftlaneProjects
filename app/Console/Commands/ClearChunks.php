<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunks:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the chunks directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new Filesystem)->cleanDirectory('storage/app/public/chunks');

        $this->info('Chunks directory deleted');
    }
}
