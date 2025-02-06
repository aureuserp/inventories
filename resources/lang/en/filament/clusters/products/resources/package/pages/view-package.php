<?php

return [
    'header-actions' => [
        'print' => [
            'label' => 'Print',

            'actions' => [
                'without-content' => [
                    'label' => 'Print Barcode',
                ],

                'with-content' => [
                    'label' => 'Print Barcode With Content',
                ],
            ],
        ],

        'delete' => [
            'notification' => [
                'title' => 'Package Deleted',
                'body'  => 'The package has been deleted successfully.',
            ],
        ],
    ],
];
