<?php

// config for N3XT0R/LaravelWebdavServer
return [
    'route_prefix' => 'webdav',
    'base_uri' => '/webdav/',


    'auth' => [
        'model' => null,

        'username_column' => 'username',
        'password_column' => 'password_encrypted',
        'enabled_column' => 'enabled',

        'user_id_column' => 'user_id',
        'display_name_column' => 'username',
    ],
];
