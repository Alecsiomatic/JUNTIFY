<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserPanelMiembro;
use App\Models\User;

class DiagnoseMemberData extends Command
{
    protected $signature = 'diagnose:members';
    protected $description = 'Diagnose member data integrity issues';

    public function handle()
    {
        $this->info('🔍 Diagnosing member data integrity...');

        // Obtener todos los miembros
        $members = UserPanelMiembro::all();
        $this->info("Total members found: {$members->count()}");

        $problematicMembers = [];

        foreach ($members as $member) {
            $this->line("Checking member ID: {$member->id}");
            $this->line("- User ID: {$member->user_id}");

            try {
                $user = $member->user;
                if (!$user) {
                    $this->error("❌ Member {$member->id} has user_id {$member->user_id} but user doesn't exist!");
                    $problematicMembers[] = $member;
                } else {
                    $this->info("✅ Member {$member->id} -> User: {$user->full_name} ({$user->email})");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error loading user for member {$member->id}: " . $e->getMessage());
                $problematicMembers[] = $member;
            }
        }

        if (count($problematicMembers) > 0) {
            $this->warn("\n🚨 Found " . count($problematicMembers) . " problematic members:");

            foreach ($problematicMembers as $member) {
                $this->line("- Member ID: {$member->id}, User ID: {$member->user_id}");
            }

            if ($this->confirm('Do you want to delete these problematic members?')) {
                foreach ($problematicMembers as $member) {
                    $member->delete();
                    $this->info("Deleted member ID: {$member->id}");
                }
                $this->info('✅ Problematic members cleaned up');
            }
        } else {
            $this->info('✅ All members have valid user references');
        }

        // Verificar usuarios sin miembros
        $this->info("\n🔍 Checking for users without member records...");
        $usersWithoutMembers = User::whereNotIn('id', UserPanelMiembro::pluck('user_id'))->get();

        $this->info("Users without member records: {$usersWithoutMembers->count()}");
        foreach ($usersWithoutMembers as $user) {
            $this->line("- {$user->full_name} ({$user->email})");
        }

        $this->info("\n✅ Diagnosis complete");
    }
}
