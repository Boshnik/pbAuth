<?php

return [
    'pbAuth' => [
        'file' => 'pbauth',
        'description' => 'Раздаёт системные события MODX классам Boshnik\PbAuth\Events\*.',
        'events' => [
            'OnManagerPageBeforeRender' => [],
        ],
    ],
];
