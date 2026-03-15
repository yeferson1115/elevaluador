<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ReprocesarAvaluosJob;

class ReprocesarAvaluosCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'avaluos:reprocesar 
                            {--limit=50 : Número de avalúos a procesar} 
                            {--queue : Ejecutar en cola asíncrona}
                            {--sync : Ejecutar sincrónicamente (por defecto)}
                            {--all : Procesar todos los avalúos}';

    /**
     * The console command description.
     */
    protected $description = 'Reprocesa los avalúos que tienen archivos PDF para regenerarlos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = $this->option('all') ? 1000000 : (int)$this->option('limit');
        $useQueue = $this->option('queue');
        
        $this->info("🚀 Iniciando reprocesamiento de avalúos...");
        $this->line("📊 Límite: " . ($limit === 1000000 ? 'Todos' : $limit));
        
        // Contar total de avalúos a procesar
        $total = \App\Models\Avaluo::whereNotNull('file')
            ->where('file', '!=', '')
            ->count();
            
        $this->line("📁 Total de avalúos con archivos: {$total}");
        
        if ($this->confirm('¿Continuar con el reprocesamiento?', true)) {
            if ($useQueue) {
                $this->info('⏳ Enviando job a la cola...');
                ReprocesarAvaluosJob::dispatch($limit);
                $this->info('✅ Job enviado exitosamente a la cola.');
            } else {
                $this->info('⚡ Ejecutando sincrónicamente...');
                
                $progressBar = $this->output->createProgressBar($total > $limit ? $limit : $total);
                $progressBar->start();
                
                // Ejecutar en lotes para mostrar progreso
                $batchSize = min(50, $limit);
                $processed = 0;
                
                \App\Models\Avaluo::whereNotNull('file')
                    ->where('file', '!=', '')
                    ->chunkById($batchSize, function ($avaluos) use (&$processed, $limit, $progressBar) {
                        foreach ($avaluos as $avaluo) {
                            if ($processed >= $limit) {
                                break;
                            }
                            
                            try {
                                $job = new ReprocesarAvaluosJob(1);
                                $job->handle(); // Ejecutar directamente
                                $processed++;
                                $progressBar->advance();
                            } catch (\Exception $e) {
                                $this->error("Error con avalúo {$avaluo->id}: " . $e->getMessage());
                            }
                        }
                    });
                
                $progressBar->finish();
                $this->newLine();
                $this->info("✅ Reprocesamiento completado. Procesados: {$processed}");
            }
        } else {
            $this->info('❌ Operación cancelada.');
        }
        
        return Command::SUCCESS;
    }
}