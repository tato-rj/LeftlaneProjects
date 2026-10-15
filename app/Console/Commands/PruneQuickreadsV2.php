<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneQuickreadsV2 extends Command
{
    protected $signature = 'quickreads:v2-prune';
    protected $description = 'Expire protected Apple recovery material and old activity reporting data';

    public function handle(): int
    {
        $db = DB::connection('quickreads');
        $db->table('apple_auth_challenges')->where('expires_at', '<=', now()->utc())->update(['verified_grant' => null]);
        $db->table('apple_auth_challenges')->where('recovery_until', '<=', now()->utc())->update(['session_response' => null]);
        $db->table('apple_auth_challenges')->where('expires_at', '<', now()->utc()->subDay())->delete();
        $db->table('apple_notifications')->where('created_at', '<', now()->utc()->subDays(90))->delete();
        $before = now()->utc()->subDays(config('quickreads_v2.activity_retention_days'));
        $db->table('story_activity')->where('updated_at', '<', $before)->delete();
        $db->table('story_activity_mutations')->where('created_at', '<', $before)->delete();
        $this->info('Expired QuickReads v2 data pruned.');
        return self::SUCCESS;
    }
}
