<?php

return [
    'tile_url' => env('TRACKING_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'tile_attribution' => env('TRACKING_TILE_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
    'osrm_url' => env('TRACKING_OSRM_URL', 'https://router.project-osrm.org'),
];
