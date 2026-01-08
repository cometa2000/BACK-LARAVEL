<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\tasks\Grupos;
use App\Models\tasks\Workspace;
use Illuminate\Console\Command;

class CreateGeneralWorkspaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workspaces:create-general';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear workspaces generales para todos los usuarios y asignar grupos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando creación de workspaces generales...');
        
        $users = User::all();
        $created = 0;
        $existing = 0;
        $migrated = 0;

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

                $created++;
                $this->line("  ✅ Creado para: {$user->name} ({$user->email})");

                // Contar y asignar grupos sin workspace
                $gruposSinWorkspace = Grupos::where('user_id', $user->id)
                    ->whereNull('workspace_id')
                    ->count();

                if ($gruposSinWorkspace > 0) {
                    Grupos::where('user_id', $user->id)
                        ->whereNull('workspace_id')
                        ->update(['workspace_id' => $generalWorkspace->id]);
                    
                    $migrated += $gruposSinWorkspace;
                    $this->line("     📁 {$gruposSinWorkspace} grupo(s) migrado(s)");
                }
            } else {
                $existing++;
                $this->line("  ⚠️  Ya existe para: {$user->name}");
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Workspaces creados', $created],
                ['Workspaces existentes', $existing],
                ['Grupos migrados', $migrated],
                ['Total usuarios', $users->count()],
            ]
        );

        $this->newLine();
        $this->info('🎉 ¡Proceso completado exitosamente!');

        return 0;
    }
}