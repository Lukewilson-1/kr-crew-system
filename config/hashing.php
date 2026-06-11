<?php

return [
    'driver' => env('HASH_DRIVER', 'bcrypt'),

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    'argon' => [
        'memory' => env('ARGON_MEMORY', 1024),
        'threads' => env('ARGON_THREADS', 2),
        'time' => env('ARGON_TIME', 2),
    ],
];
