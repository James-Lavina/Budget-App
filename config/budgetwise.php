<?php

/*
 | Read through config() rather than env() so the values still work after
 | `php artisan config:cache`, when env() returns null outside config files.
 */
return [

    'admin' => [
        'name'     => env('ADMIN_NAME', 'Super Admin'),
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];