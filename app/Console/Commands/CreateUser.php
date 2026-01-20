<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'user:create
                            {--name= : Nom de l\'utilisateur}
                            {--email= : Email de l\'utilisateur}
                            {--password= : Mot de passe}
                            {--role=viewer : Rôle (admin ou viewer)}';

    protected $description = 'Crée un nouvel utilisateur avec un rôle';

    public function handle(): int
    {
        // Récupère les options ou demande interactivement
        $name = $this->option('name') ?? $this->ask('Nom de l\'utilisateur');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Mot de passe');
        $role = $this->option('role');

        // Validation
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,viewer',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        // Création de l'utilisateur
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Assigne le rôle
        $user->assignRole($role);

        $this->info("✅ Utilisateur créé avec succès !");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Nom', $user->name],
                ['Email', $user->email],
                ['Rôle', $role],
            ]
        );

        return Command::SUCCESS;
    }
}
