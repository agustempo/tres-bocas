<?php

namespace App\Console\Commands;

use App\Services\InaService;
use App\Services\TideService;
use App\Services\TideSummaryService;
use Illuminate\Console\Command;

class UpdateTideData extends Command
{
    protected $signature   = 'update:tide-data';
    protected $description = 'Fetch fresh tide data from official sources and update the cache';

    public function handle(
        TideService        $tideService,
        InaService         $inaService,
        TideSummaryService $summaryService,
    ): int {
        $this->info('Fetching tide data for San Fernando...');

        $data = $tideService->refresh();

        if ($data['has_error']) {
            $this->warn('Could not retrieve data from one or more sources. Check logs for details.');
            return self::FAILURE;
        }

        $forecastCount = count($data['forecast'] ?? []);
        $hourlyCount   = count($data['hourly'] ?? []);
        $wind          = $data['wind'] ?? [];

        $this->info("Forecast entries: {$forecastCount}");
        $this->info("Hourly entries:   {$hourlyCount}");

        if ($wind['available'] ?? false) {
            $this->info("Wind:             {$wind['direction']} {$wind['speed']} km/h");
        } else {
            $this->warn('Wind:             unavailable');
        }

        $this->info("Updated at:       {$data['updated_at']}");
        $this->info('Cache refreshed successfully.');

        // ── Operational summary via LLM ───────────────────────────────────────
        $this->info('Generating LLM tide summary...');

        $inaRaw  = $inaService->getCachedTideData();
        $summary = $summaryService->generate($data, $inaRaw);

        if ($summary) {
            $this->info('LLM summary: ' . $summary);
        } else {
            $this->warn('LLM summary: unavailable (no API key or request failed — fallback to templates)');
        }

        return self::SUCCESS;
    }
}
