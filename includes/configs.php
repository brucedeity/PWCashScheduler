<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('default_charset', 'utf-8');
setlocale(LC_ALL,"pt_BR" , "portuguese");
date_default_timezone_set("America/Sao_Paulo");

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
        0 => ['cash' => 1], // Spiritual Initiate 0
        1 => ['cash' => 2], // Aware of Principle 9
        2 => ['cash' => 3], // Aware of Harmony 19
        3 => ['cash' => 4], // Aware of Discord 29
        4 => ['cash' => 5], // Aware of Coealescense 39
        5 => ['cash' => 6], // Transcendant 49
        6 => ['cash' => 6], // Elightened One 59
        7 => ['cash' => 7], // Aware of Vacuity 69
        8 => ['cash' => 8], // Aware of Myriad 79
        20 => ['cash' => 20], // Aware of Myriad GOD 1
        21 => ['cash' => 21], // Master of Harmony GOD 2
        22 => ['cash' => 22], // Celestial Sage GOD 3
        30 => ['cash' => 30], // Aware of the Void EVIL 1
        31 => ['cash' => 31], // Master of Discord EVIL 2
        32 => ['cash' => 32] // Celestial Demon EVIL 3
    ]
];