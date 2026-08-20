<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business rules from the cahier des charges (§6)
    |--------------------------------------------------------------------------
    |
    | Commission is indicative (20-30% per the cahier des charges) — 25%
    | is the chosen default until a final rate is set. The minimum payout
    | amount (10 000 FCFA) is specified explicitly, not a range.
    |
    */

    'commission_rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.25),

    'minimum_payout_amount' => (int) env('PLATFORM_MINIMUM_PAYOUT_AMOUNT', 10000),

];
