<?php

return [
    'runscope' => [
        'enabled' => env('RUNSCOPE_ENABLED', null),
        'bucket'  => env('RUNSCOPE_KEY'),
        'gateway' => env('RUNSCOPE_HOST', 'runscope.net'),
    ],
];
