<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V2\StoryActivityController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};

class ForgetGuestActivity extends Command
{
    protected $signature = 'quickreads:activity-forget-guest {guest_id : The installation reporting UUID supplied by its owner}';
    protected $description = 'Delete a guest installation\'s activity reports without accessing its Library';

    public function handle(): int
    {
        $input = ['guest_id' => $this->argument('guest_id')];
        if (Validator::make($input, ['guest_id' => 'required|string|uuid'])->fails()) {
            $this->error('A valid reporting UUID is required.');
            return self::FAILURE;
        }
        $key = StoryActivityController::readerKey('guest', strtolower($input['guest_id']));
        $db = DB::connection('quickreads');
        $db->transaction(function () use ($db, $key) {
            $db->table('story_activity')->where('reader_key', $key)->delete();
            $db->table('story_activity_mutations')->where('reader_key', $key)->delete();
        });
        $this->info('Guest activity reports deleted.');
        return self::SUCCESS;
    }
}
