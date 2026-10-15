<?php

return [
    'air' => [
        'volumetric_divisor' => (int) env('AIR_VOLUMETRIC_DIVISOR', 6000),
    ],

    'dangerous_goods_regulations' => [
        'AIR' => 'IATA',
        'SEA' => 'IMDG',
        'LAND' => 'ADR',
        'MULTIMODAL' => 'MULTIMODAL',
        'AUTO' => 'MULTIMODAL',
    ],
];
