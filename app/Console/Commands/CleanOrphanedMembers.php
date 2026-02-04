<?php

namespace App\Console\Commands;

use App\Models\UserPanelMiembro;
use Illuminate\Console\Command;

class CleanOrphanedMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:orphaned-members {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove member records that don\'t have valid user references';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Searching for orphaned member records...');

        // Buscar miembros sin usuarios válidos
        $orphanedMembers = UserPanelMiembro::whereDoesntHave('user')->get();

        if ($orphanedMembers->isEmpty()) {
            $this->info('✅ No orphaned member records found. Database is clean!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found {$orphanedMembers->count()} orphaned member record(s):");

        foreach ($orphanedMembers as $member) {
            $this->line("   - Member ID: {$member->id}, Role: {$member->role}, User ID: {$member->user_id}");
        }

        if ($dryRun) {
            $this->info('🧪 Dry run mode - no changes made.');
            $this->info('💡 Run without --dry-run to actually clean the records.');
            return Command::SUCCESS;
        }

        if ($this->confirm('Do you want to delete these orphaned records?')) {
            $deleted = UserPanelMiembro::whereDoesntHave('user')->delete();
            $this->info("✅ Deleted {$deleted} orphaned member record(s).");
        } else {
            $this->info('❌ Cleanup cancelled.');
        }

        return Command::SUCCESS;
    }
}
