<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRole extends Command
{
    protected $signature = 'user:assign-role {email} {role}';

    protected $description = 'Assigne un rôle à un utilisateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Utilisateur avec l'email {$email} non trouvé.");
            return Command::FAILURE;
        }

        if (!in_array($role, ['admin', 'viewer'])) {
            $this->error("Rôle invalide. Utilisez 'admin' ou 'viewer'.");
            return Command::FAILURE;
        }

        // Retire les anciens rôles et assigne le nouveau
        $user->syncRoles([$role]);

        $this->info("✅ Rôle '{$role}' assigné à {$user->name} ({$email}).");

        return Command::SUCCESS;
    }
}
