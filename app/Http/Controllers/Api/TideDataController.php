<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InaService;
use Illuminate\Http\JsonResponse;

class TideDataController extends Controller
{
    public function __invoke(InaService $service): JsonResponse
    {
        $tideData = $service->getCachedTideData();

        if ($tideData === null) {
            return response()->json(['error' => 'data_unavailable'], 503);
        }

        $tz  = 'America/Argentina/Buenos_Aires';
        $now = now()->setTimezone($tz);

        // Times are already ISO 8601 strings (serialized before caching)
        $data = array_map(function (array $point) {
            $item = [
                'time'  => $point['time'],
                'value' => $point['value'],
                'type'  => $point['type'],
            ];
            if ($point['error_hi'] !== null) {
                $item['error_hi'] = $point['error_hi'];
                $item['error_lo'] = $point['error_lo'];
            }
            return $item;
        }, $tideData['data']);

        return response()->json([
            'fetched_at'           => $tideData['fetched_at'],
            'now'                  => $now->format('c'),
            'source'               => 'ina',
            'data'                 => $data,
            'thresholds'           => [
                'alert'      => 3.0,
                'evacuation' => 3.5,
            ],
            'observed_unavailable' => $tideData['observed_unavailable'] ?? false,
            'forecast_unavailable' => $tideData['forecast_unavailable'] ?? false,
        ]);
    }
}
