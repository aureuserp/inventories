<?php

return [
    'navigation' => [
        'title' => 'Packages',
        'group' => 'Inventory',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'name'             => 'Name',
                    'name-placeholder' => 'eg. PACK007',
                    'package-type'     => 'Package Type',
                    'pack-date'        => 'Pack Date',
                    'location'         => 'Location',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'         => 'Name',
            'package-type' => 'Package Type',
            'location'     => 'Location',
            'company'      => 'Company',
            'created-at'   => 'Created At',
            'updated-at'   => 'Updated At',
        ],

        'groups' => [
            'package-type'   => 'Package Type',
            'location'       => 'Location',
            'created-at'     => 'Created At',
        ],

        'filters' => [
            'package-type' => 'Package Type',
            'location'     => 'Location',
            'creator'      => 'Creator',
            'company'      => 'Company',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Product deleted',
                    'body'  => 'The product has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Products deleted',
                    'body'  => 'The products has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Package Details',

                'entries' => [
                    'name'         => 'Package Name',
                    'package-type' => 'Package Type',
                    'pack-date'    => 'Pack Date',
                    'location'     => 'Location',
                    'company'      => 'Company',
                    'created-at'   => 'Created At',
                    'updated-at'   => 'Last Updated',
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'created-by'   => 'Created By',
                    'created-at'   => 'Created At',
                    'last-updated' => 'Last Updated',
                ],
            ],
        ],
    ],
];
