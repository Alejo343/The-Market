<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    'namespaces' => [
        'volt-livewire' => resource_path('views/livewire'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

    'cache' => env('VIEW_CACHE_ENABLED', false),

];
