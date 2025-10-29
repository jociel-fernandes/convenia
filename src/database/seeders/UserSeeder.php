<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar 2 managers conforme especificação
        $manager1 = User::firstOrCreate(
            ['email' => 'gestor@convenia.com'],
            [
                'name' => 'Gestor Principal',
                'password' => Hash::make('password'),
            ]
        );
        if (!$manager1->hasRole('manager')) {
            $manager1->assignRole('manager');
        }

        $manager2 = User::firstOrCreate(
            ['email' => 'gestor2@convenia.com'],
            [
                'name' => 'Gestor Secundário',
                'password' => Hash::make('password'),
            ]
        );
        if (!$manager2->hasRole('manager')) {
            $manager2->assignRole('manager');
        }

        // Criar 1 colaborador para teste (não pode fazer login)
        $collaborator = User::firstOrCreate(
            ['email' => 'colaborador@convenia.com'],
            [
                'name' => 'Colaborador Teste',
                'password' => Hash::make('password'),
            ]
        );
        if (!$collaborator->hasRole('collaborator')) {
            $collaborator->assignRole('collaborator');
        }
    }
}
