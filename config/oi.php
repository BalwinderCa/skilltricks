<?php

return [
    // Achievement rate (actual / target) below which drift is flagged.
    'drift_threshold' => (float) env('OI_DRIFT_THRESHOLD', 0.8),
];
