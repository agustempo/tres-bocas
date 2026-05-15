<?php

namespace App\Console\Commands;

use App\Models\Patron;
use Illuminate\Console\Command;

class PatronesReviewReport extends Command
{
    protected $signature = 'movilidad:review-report
                            {--format=table : Output format: table|csv|count}
                            {--fuente= : Filter by fuente: oficial|comunidad|estimado}';

    protected $description = 'Report patrones that need admin review (stale validado_at)';

    public function handle(): int
    {
        $query = Patron::with(['muelle', 'servicio'])
            ->where('activo', true)
            ->where(function ($q) {
                $q->whereNull('validado_at')
                  ->orWhere('validado_at', '<', now()->subDays(14));
            });

        if ($fuente = $this->option('fuente')) {
            $query->where('fuente', $fuente);
        }

        // Group and deduplicate by (muelle, servicio, tipoDia, sentido, hora)
        $raw = $query->orderBy('muelle_id')->orderBy('servicio_id')->orderBy('hora_referencia')->get();

        $grupos = [];
        foreach ($raw as $p) {
            $td  = $p->tipoDiaCalculado();
            $key = $p->muelle_id . '|' . $p->servicio_id . '|' . $td . '|' . $p->sentido . '|' . substr($p->hora_referencia, 0, 5);
            if (! isset($grupos[$key])) {
                $grupos[$key] = [
                    'muelle'       => $p->muelle->nombre,
                    'servicio'     => $p->servicio->nombre,
                    'tipo_dia'     => $td,
                    'sentido'      => $p->sentido,
                    'hora'         => substr($p->hora_referencia, 0, 5),
                    'fuente'       => $p->fuente ?? 'estimado',
                    'validado_at'  => $p->validado_at?->format('Y-m-d') ?? '—',
                    'dias_sin_rev' => $p->validado_at ? (int) $p->validado_at->diffInDays(now()) : 999,
                ];
            }
        }

        $grupos = array_values($grupos);
        $count  = count($grupos);

        if ($this->option('format') === 'count') {
            $this->line((string) $count);
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('All patrones are up to date.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} patron group(s) needing review:");

        if ($this->option('format') === 'csv') {
            $this->line('muelle,servicio,tipo_dia,sentido,hora,fuente,validado_at,dias_sin_revision');
            foreach ($grupos as $g) {
                $this->line(implode(',', [
                    "\"{$g['muelle']}\"", "\"{$g['servicio']}\"",
                    $g['tipo_dia'], $g['sentido'], $g['hora'],
                    $g['fuente'], $g['validado_at'], $g['dias_sin_rev'],
                ]));
            }
            return self::SUCCESS;
        }

        $this->table(
            ['Muelle', 'Servicio', 'Día', 'Sentido', 'Hora', 'Fuente', 'Validado', 'Días sin rev.'],
            array_map(fn($g) => [
                $g['muelle'], $g['servicio'], $g['tipo_dia'], $g['sentido'],
                $g['hora'], $g['fuente'], $g['validado_at'], $g['dias_sin_rev'],
            ], $grupos)
        );

        return self::SUCCESS;
    }
}
