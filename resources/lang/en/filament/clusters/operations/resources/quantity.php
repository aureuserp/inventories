<?php

return [
    'navigation' => [
        'title' => 'Quantities',
        'group' => 'Adjustments',
    ],

    'form' => [
        'fields' => [
            'location'         => 'Location',
            'product'          => 'Product',
            'package'          => 'Package',
            'lot'              => 'Lot / Serial Numbers',
            'counted-qty'      => 'Counted Quantity',
            'scheduled-at'     => 'Scheduled At',
            'storage-category' => 'Storage Category',
        ],
    ],

    'table' => [
        'columns' => [
            'location'         => 'Location',
            'lot'              => 'Lot / Serial Numbers',
            'storage-category' => 'Storage Category',
            'quantity'         => 'Quantity',
            'package'          => 'Package',
            'on-hand'          => 'On Hand Quantity',
            'counted'          => 'Counted Quantity',
            'difference'       => 'Difference',
            'scheduled-at'     => 'Scheduled At',

            'on-hand-before-state-updated' => [
                'notification' => [
                    'title' => 'Quantity updated',
                    'body'  => 'The quantity has been updated successfully.',
                ],
            ],
        ],

        'header-actions' => [
            'create' => [
                'label' => 'Add Quantity',

                'notification' => [
                    'title' => 'Quantity added',
                    'body'  => 'The quantity has been added successfully.',
                ],

                'before' => [
                    'notification' => [
                        'title' => 'Quantity already exists',
                        'body'  => 'Already has a quantity for the same configuration. Please update the quantity instead.',
                    ],
                ],
            ],
        ],

        'actions' => [
            'apply' => [
                'label' => 'Apply',

                'notification' => [
                    'title' => 'Quantity changes applied',
                    'body'  => 'The quantity changes has been applied successfully.',
                ],
            ],

            'clear' => [
                'label' => 'Clear',

                'notification' => [
                    'title' => 'Quantity changes cleared',
                    'body'  => 'The quantity changes have been cleared successfully.',
                ],
            ],
        ],
    ],
];
