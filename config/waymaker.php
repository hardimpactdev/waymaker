<?php

// config for HardImpact/Waymaker
return [
    'method_defaults' => [
        'GET' => ['index', 'show'],
        'POST' => ['store'],
        'PUT' => ['update'],
        'DELETE' => ['destroy'],
        'PATCH' => ['update'],
    ],
];
