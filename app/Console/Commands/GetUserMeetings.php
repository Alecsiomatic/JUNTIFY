<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GetUserMeetings extends Command
{
    protected $signature = 'juntify:get-meetings {email}';
    protected $description = 'Get meetings for a user by email from juntify';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            // Primero obtener el usuario
            $user = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$user) {
                $this->error("❌ Usuario no encontrado: {$email}");
                return;
            }

            $this->info("👤 Usuario: {$user->full_name} ({$user->username})");
            $this->info("📧 Email: {$user->email}");
            $this->info("🆔 ID: {$user->id}");

            // Obtener las reuniones del usuario
            $meetings = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('username', $user->username)
                ->orderByDesc('created_at')
                ->get();

            $this->info("\n📅 Reuniones encontradas: " . $meetings->count());
            $this->info('═══════════════════════════════════════════════════════════════════');

            if ($meetings->count() > 0) {
                foreach ($meetings->take(10) as $meeting) {
                    $this->line("🔸 ID: {$meeting->id}");
                    $this->line("  📝 Nombre: {$meeting->meeting_name}");
                    $this->line("  📅 Fecha: {$meeting->created_at}");
                    $this->line("  🎵 Audio: " . ($meeting->audio_drive_id ? '✅' : '❌'));
                    $this->line("  📄 Transcripción: " . ($meeting->transcript_drive_id ? '✅' : '❌'));
                    $this->line("---");
                }

                if ($meetings->count() > 10) {
                    $this->line("... y " . ($meetings->count() - 10) . " reuniones más.");
                }
            } else {
                $this->line("No se encontraron reuniones para este usuario.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
