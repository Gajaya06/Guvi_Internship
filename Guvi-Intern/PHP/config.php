<?php
return [
    'mysql' => [
        'host' => '127.0.0.1',
        'dbname' => 'guvi_intern',
        'user' => 'root',
        'pass' => 'Harish@2005',
        'charset' => 'utf8mb4'
    ],
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 1.5
    ],
    'mongo' => [
        'uri' => 'mongodb://127.0.0.1:27017',
        'db'  => 'guvi_intern'
    ],
    'session_ttl' => 86400 // seconds (24 hours)
];