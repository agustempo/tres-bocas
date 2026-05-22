<?php

return [
    /*
     * Thresholds used for auto-alarm generation from the INA forecast series.
     * Each entry: type (string), operator ('<' or '>'), value (float in metres).
     * Types are processed in order — first match wins for a given forecast point.
     */
    'alarm_thresholds' => [
        ['type' => 'extreme_low', 'operator' => '<', 'value' => 0.40],
        ['type' => 'low',         'operator' => '<', 'value' => 0.70],
        ['type' => 'alert',       'operator' => '>', 'value' => 3.00],
        ['type' => 'very_high',   'operator' => '>', 'value' => 2.20],
        ['type' => 'high',        'operator' => '>', 'value' => 2.00],
    ],

    /*
     * Maximum minutes difference between a SHN extreme and an INA extreme
     * for them to be considered the "same event" (comparison card / events grid).
     */
    'event_match_window_minutes' => 30,

    /*
     * SE wind detection range (degrees) and minimum speed (km/h).
     * Used for the sudestada card and the wind overlay on the chart.
     */
    'se_wind_min_degrees'  => 100,
    'se_wind_max_degrees'  => 170,
    'se_wind_threshold_kmh' => 15,

    /*
     * Minimum number of consecutive SE wind slots to be considered "sustained".
     */
    'se_wind_sustained_slots' => 3,
];
