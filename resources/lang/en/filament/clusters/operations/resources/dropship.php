<?php

return [
    'navigation' => [
        'title' => 'Dropships',
        'group' => 'Transfers',
    ],

    'table' => [
        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Dropship deleted',
                    'body'  => 'The dropship ras been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Dropships deleted',
                    'body'  => 'The dropships has been deleted successfully.',
                ],
            ],
        ],
    ],
];
