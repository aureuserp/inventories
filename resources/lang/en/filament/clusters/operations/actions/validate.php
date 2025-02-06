<?php

return [
    'label'             => 'Validate',
    'modal-heading'     => 'Create Back Order?',
    'modal-description' => 'Create a back order if you expect to process the remaining products later. Do not create a back order if you will not process the remaining products.',

    'extra-modal-footer-actions' => [
        'no-backorder' => [
            'label' => 'No Backorder',
        ],
    ],

    'notification' => [
        'warning' => [
            'lines-missing' => [
                'title' => 'No quantities are reserved',
                'body'  => 'No quantities are reserved for the transfer.',
            ],

            'lot-missing' => [
                'title' => 'Supply Lot/Serial Number',
                'body'  => 'You need to supply a Lot/Serial Number for products',
            ],

            'partial-package' => [
                'title' => 'Can not move same package content',
                'body'  => 'You cannot move the same package content more than once in the same transfer or split the same package into two location.',
            ],
        ],
    ],
];
