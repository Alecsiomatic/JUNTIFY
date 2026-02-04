<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\IntegrantesEmpresa;
use App\Models\Empresa;

class AddDduMember extends Command
{
    protected $signature = 'ddu:add-member {email} {--role=administrador}';
    protected $description = 'Add a user to DDU empresa with specified role';

    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');

        $this->info("Adding {$email} to DDU with role: {$role}");

        try {
            // Step 1: Check if user exists in juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                $this->error("User {$email} not found in juntify database");
                return 1;
            }

            $this->info("✓ User found in juntify: {$juntifyUser->full_name} (ID: {$juntifyUser->id})");

            // Step 2: Get DDU empresa
            $dduEmpresa = Empresa::where('nombre_empresa', 'DDU')->first();

            if (!$dduEmpresa) {
                $this->error("DDU empresa not found in juntify_panels database");
                return 1;
            }

            $this->info("✓ DDU empresa found (ID: {$dduEmpresa->id})");

            // Step 3: Check if user is already a member
            $existingMember = IntegrantesEmpresa::where('iduser', $juntifyUser->id)
                ->where('empresa_id', $dduEmpresa->id)
                ->first();

            if ($existingMember) {
                $this->warn("User is already a DDU member with role: {$existingMember->rol}");

                if ($this->confirm('Update existing membership?')) {
                    $existingMember->update([
                        'rol' => $role,
                        'permisos' => json_encode(['admin', 'read', 'write', 'delete']),
                    ]);
                    $this->info("✓ Membership updated successfully");
                } else {
                    $this->info("No changes made");
                }

                return 0;
            }

            // Step 4: Add user to DDU
            $membership = IntegrantesEmpresa::create([
                'iduser' => $juntifyUser->id,
                'empresa_id' => $dduEmpresa->id,
                'rol' => $role,
                'permisos' => json_encode(['admin', 'read', 'write', 'delete']),
            ]);

            $this->info("✓ User successfully added to DDU");
            $this->table(['Field', 'Value'], [
                ['Email', $email],
                ['User ID', $juntifyUser->id],
                ['Full Name', $juntifyUser->full_name],
                ['DDU Role', $role],
                ['Permissions', 'admin, read, write, delete'],
                ['Membership ID', $membership->id],
            ]);

        } catch (\Exception $e) {
            $this->error("Error adding user to DDU: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
