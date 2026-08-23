<?php

return [
    'pbAuth' => [
        'file' => 'pbauth',
        'description' => 'Раздаёт системные события MODX классам Boshnik\PbAuth\Events\*.',
        'events' => [
            'OnManagerPageBeforeRender' => [],
            // Раньше PageBlocks (у него приоритет 0): закрытую сессию надо
            // увидеть до того, как страница отрисуется под пользователем.
            'OnHandleRequest' => ['priority' => -10],
        ],
    ],
];
