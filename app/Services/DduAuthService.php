<?php

namespace App\Services;

use App\Models\User;
use App\Models\Empresa;
use App\Models\IntegrantesEmpresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DduAuthService
{
    /**
     * Validate user login against both juntify and juntify_panels databases
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function validateDduUser(string $email, string $password): ?array
    {
        try {
            // Step 1: Check if user exists in juntify database
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                Log::info("User not found in juntify database: {$email}");
                return null;
            }

            // Step 2: Validate password
            if (!Hash::check($password, $juntifyUser->password)) {
                Log::info("Invalid password for user: {$email}");
                return null;
            }

            // Step 3: Check if user is a DDU member in juntify_panels
            $dduMembership = IntegrantesEmpresa::getDduMembership($juntifyUser->id);

            if (!$dduMembership) {
                Log::info("User is not a DDU member: {$email}");
                return null;
            }

            // Step 4: Get complete user info including DDU role and permissions
            $userData = [
                'id' => $juntifyUser->id,
                'username' => $juntifyUser->username,
                'full_name' => $juntifyUser->full_name,
                'email' => $juntifyUser->email,
                'current_organization_id' => $juntifyUser->current_organization_id,
                'ddu_role' => $dduMembership->rol,
                'ddu_permissions' => $dduMembership->permisos,
                'empresa_id' => $dduMembership->empresa_id,
                'membership_id' => $dduMembership->id,
            ];

            Log::info("Successfully validated DDU user: {$email} with role: {$dduMembership->rol}");

            return $userData;

        } catch (\Exception $e) {
            Log::error("Error validating DDU user {$email}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all DDU members with their juntify user information
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllDduMembers()
    {
        try {
            return IntegrantesEmpresa::getAllDduMembersWithUsers();
        } catch (\Exception $e) {
            Log::error("Error getting DDU members: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Check if a user exists in juntify and is a DDU member
     *
     * @param string $email
     * @return bool
     */
    public function isValidDduUser(string $email): bool
    {
        try {
            // Check if user exists in juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                return false;
            }

            // Check if user is a DDU member
            return IntegrantesEmpresa::isDduMember($juntifyUser->id);

        } catch (\Exception $e) {
            Log::error("Error checking DDU user {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user info from juntify database by email
     *
     * @param string $email
     * @return object|null
     */
    public function getJuntifyUserByEmail(string $email): ?object
    {
        try {
            return DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();
        } catch (\Exception $e) {
            Log::error("Error getting juntify user by email {$email}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get DDU membership info for a user
     *
     * @param string $userId
     * @return IntegrantesEmpresa|null
     */
    public function getDduMembershipInfo(string $userId): ?IntegrantesEmpresa
    {
        try {
            return IntegrantesEmpresa::getDduMembership($userId);
        } catch (\Exception $e) {
            Log::error("Error getting DDU membership for user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create or update a local user record based on juntify user data
     *
     * @param array $userData
     * @return User
     */
    public function createOrUpdateLocalUser(array $userData): User
    {
        return User::updateOrCreate(
            ['id' => $userData['id']],
            [
                'username' => $userData['username'],
                'full_name' => $userData['full_name'],
                'email' => $userData['email'],
                'current_organization_id' => $userData['current_organization_id'],
                'roles' => json_encode(['ddu_member']),
                // Note: We don't store password in local DB as auth is handled by juntify
            ]
        );
    }

    /**
     * Sync DDU user data between databases
     *
     * @param string $email
     * @return User|null
     */
    public function syncDduUser(string $email): ?User
    {
        $userData = $this->validateDduUser($email, ''); // We'll handle password separately

        if (!$userData) {
            return null;
        }

        return $this->createOrUpdateLocalUser($userData);
    }
}
