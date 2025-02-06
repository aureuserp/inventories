<?php

return [
    'header-actions' => [
        'print-labels' => [
            'label' => 'Print Labels',

            'form' => [
                'fields' => [
                    'quantity' => 'Number of Labels',
                    'format'   => 'Format',
                ],
            ],

        ],

        'delete' => [
            'notification' => [
                'title' => 'Product Deleted',
                'body'  => 'The product has been deleted successfully.',
            ],
        ],
    ],
];
