<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | El frontend vive en otro origen (Vite en :5173, por ejemplo), así que la
    | API tiene que permitirlo explícitamente. FRONTEND_URL admite una lista
    | separada por comas y se define en el .env de cada entorno.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => config('app.frontend_origins'),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Con tokens Bearer no hacen falta cookies; se activaría para Sanctum SPA.
    'supports_credentials' => false,

];
