<?php

namespace App\Console\Commands;

use App\Services\TideService;
use Illuminate\Console\Command;

class UpdateTideData extends Command
{
    protected $signature   = 'update:tide-data';
    protected $description = 'Fetch fresh tide data from official sources and update the cache';

    public function handle(TideService $tideService): int
    {
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

        return self::SUCCESS;
    }
}
