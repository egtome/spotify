<?php
require '../env.php'; 
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'spotify',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database connection settings
        "db" => [
            "host" => $env['db']['host'],
            "dbname" => $env['db']['dbname'],
            "user" => $env['db']['user'],
            "pass" => $env['db']['pass']
        ],        
    ],
];
