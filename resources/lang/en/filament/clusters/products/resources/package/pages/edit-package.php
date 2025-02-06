<?php

return [
    'notification' => [
        'title' => 'Package updated',
        'body'  => 'The package has been updated successfully.',
    ],

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
                'title' => 'Package deleted',
                'body'  => 'The package has been deleted successfully.',
            ],
        ],
    ],
];
