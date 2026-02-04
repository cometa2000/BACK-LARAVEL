<?php

namespace Database\Seeders;

use App\Models\tasks\Grupos;
use App\Models\User;
use App\Models\tasks\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Este seeder crea workspaces generales para todos los usuarios existentes
     * y asigna sus grupos sin workspace al workspace General
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Verificar si ya tiene workspace general
            $generalWorkspace = Workspace::where('user_id', $user->id)
                ->where('is_general', true)
                ->first();

            if (!$generalWorkspace) {
                // Crear workspace general
                $generalWorkspace = Workspace::create([
                    'name' => 'General',
                    'description' => 'Espacio de trabajo principal',
                    'color' => '#3b82f6',
                    'user_id' => $user->id,
                    'is_general' => true,
                    'is_shared' => false,
                ]);

                // Asignar todos los grupos del usuario que no tengan workspace
                Grupos::where('user_id', $user->id)
                    ->whereNull('workspace_id')
                    ->update(['workspace_id' => $generalWorkspace->id]);

                $this->command->info("✅ Workspace General creado para usuario: {$user->name}");
            } else {
                $this->command->info("⚠️ Usuario {$user->name} ya tiene workspace General");
            }
        }

        $this->command->info("🎉 Proceso completado!");
    }
}