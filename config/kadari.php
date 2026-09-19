<?php

return [
    /*
     * Remaining stock at or below this quantity is flagged "Low" on the
     * Stock Balance page (matching the shop's own spreadsheet alert level).
     */
    'low_stock_threshold' => (float) env('LOW_STOCK_THRESHOLD', 5),
];
