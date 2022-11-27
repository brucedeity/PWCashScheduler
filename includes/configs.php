<?php

$configs = [
    'api' => [
        'host'  => '127.0.0.1',
        'version'     =>  101,
        'gamedbd'    =>  29400,
        'gdeliveryd' =>  29100,
        'gacd'       =>  29300,
        'client'     =>  29000,
    ],
    'cron' => '1 * * * * *', // Cron time to send cash. This may be usefull: https://crontab.guru/

    
    'rewards' => [ // Cultivation id ['cash' => quantity]
        0 => ['cash' => 0], // Spiritual Initiate (0)
        1 => ['cash' => 9], // Spiritual Adept (9)
        2 => ['cash' => 19], // Aware of Principle (19)
        3 => ['cash' => 29], // Aware of Harmony (29)
        4 => ['cash' => 39], // Aware of Discord (39)
        5 => ['cash' => 49], // Aware of Coealescense (49)
        6 => ['cash' => 59], // Transcendant (59)
        7 => ['cash' => 69], // Elightened One (69)
        8 => ['cash' => 79], // Aware of Vacuity (79)
        20 => ['cash' => 89], // Aware of Myriad (GOD 1)
        21 => ['cash' => 90], // Master of Harmony (GOD 2)
        22 => ['cash' => 91], // Celestial Sage (GOD 3)
        30 => ['cash' => 99], // Aware of the Void (EVIL 1)
        31 => ['cash' => 100], // Master of Discord (EVIL 2)
        32 => ['cash' => 101] // Celestial Demon (EVIL 3)
    ]
];