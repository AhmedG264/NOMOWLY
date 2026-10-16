<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nomowly Supported Regional Currencies
    |--------------------------------------------------------------------------
    | Regional Arabic-first multi-currency platform for Egypt, GCC, and Arab markets.
    | Initial currencies: EGP, SAR, AED, KWD, QAR, BHD, OMR.
    | Explicit currency is recorded on every campaign, wallet, ledger entry, and order.
    | No automatic FX conversion is performed.
    */
    'currencies' => [
        'EGP' => [
            'code' => 'EGP',
            'name' => 'الجنيه المصري',
            'symbol' => 'ج.م',
            'decimals' => 2,
        ],
        'SAR' => [
            'code' => 'SAR',
            'name' => 'الريال السعودي',
            'symbol' => 'ر.س',
            'decimals' => 2,
        ],
        'AED' => [
            'code' => 'AED',
            'name' => 'الدرهم الإماراتي',
            'symbol' => 'د.إ',
            'decimals' => 2,
        ],
        'KWD' => [
            'code' => 'KWD',
            'name' => 'الدينار الكويتي',
            'symbol' => 'د.ك',
            'decimals' => 3,
        ],
        'QAR' => [
            'code' => 'QAR',
            'name' => 'الريال القطري',
            'symbol' => 'ر.ق',
            'decimals' => 2,
        ],
        'BHD' => [
            'code' => 'BHD',
            'name' => 'الدينار البحريني',
            'symbol' => 'د.ب',
            'decimals' => 3,
        ],
        'OMR' => [
            'code' => 'OMR',
            'name' => 'الريال العماني',
            'symbol' => 'ر.ع',
            'decimals' => 3,
        ],
    ],

    'default_currency' => env('DEFAULT_CURRENCY', 'EGP'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Configuration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'ahmedgamal264@outlook.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial & Holding Rules
    |--------------------------------------------------------------------------
    */
    'financials' => [
        'default_holding_days' => (int) env('HOLDING_PERIOD_DAYS', 14),
        'min_withdrawal_amount' => (float) env('MIN_WITHDRAWAL_AMOUNT', 150),
        'platform_fee_percent' => (float) env('PLATFORM_FEE_PERCENT', 10.0),
    ],
];
