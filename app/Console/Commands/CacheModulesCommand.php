<?php

namespace App\Console\Commands;

use App\Services\ModuleCacheService;
use Illuminate\Console\Command;

class CacheModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Precarga y calienta la caché de todos los módulos de la plataforma (sidebar, módulos, items y problemas)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando precarga de caché para todos los módulos...');

        $startTime = microtime(true);
        $warmed = ModuleCacheService::warmAll();
        $elapsed = round((microtime(true) - $startTime) * 1000, 2);

        $this->table(
            ['ID', 'Título', 'Slug', 'Lecturas', 'Problemas'],
            array_map(fn ($m) => [
                $m['id'],
                $m['title'],
                $m['slug'],
                $m['items_count'],
                $m['problems_count'],
            ], $warmed)
        );

        $this->info('✓ Caché de '.count($warmed)." módulos precalentada exitosamente en {$elapsed}ms.");

        return Command::SUCCESS;
    }
}
