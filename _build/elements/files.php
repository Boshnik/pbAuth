<?php

return [
    'routes' => [
        'source' => $this->config['source'] . 'routes/',
        'target' => "return MODX_CORE_PATH . 'App/';",
    ],
    'controllers' => [
        'source' => $this->config['source'] . 'Controllers/',
        'target' => "return MODX_CORE_PATH . 'App/Http/';",
    ],
    'elements' => [
        'source' => $this->config['source'] . 'elements/',
        'target' => "return MODX_CORE_PATH . 'App/';",
    ],
    'lang' => [
        'source' => $this->config['source'] . 'lang/',
        'target' => "return MODX_CORE_PATH . 'App/';",
    ],
];