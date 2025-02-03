<?php

return [
    'navigation' => [
        'title' => 'Packagings',
        'group' => 'Products',
    ],

    'form' => [
        'name'         => 'Name',
        'barcode'      => 'Barcode',
        'product'      => 'Product',
        'routes'       => 'Routes',
        'qty'          => 'Qty',
        'package-type' => 'Package Type',
        'company'      => 'Company',
    ],

    'table' => [
        'columns' => [
            'name'         => 'Name',
            'product'      => 'Product',
            'package-type' => 'Package Type',
            'routes'       => 'Routes',
            'qty'          => 'Qty',
            'company'      => 'Company',
            'barcode'      => 'Barcode',
            'created-at'   => 'Created At',
            'updated-at'   => 'Updated At',
        ],

        'groups' => [
            'product'      => 'Product',
            'package-type' => 'Package Type',
            'created-at'   => 'Created At',
            'updated-at'   => 'Updated At',
        ],

        'filters' => [
            'product'      => 'Product',
            'package-type' => 'Package Type',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Packaging update',
                    'body'  => 'The packaging has been update successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Packaging deleted',
                    'body'  => 'The packaging has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Packagings deleted',
                    'body'  => 'The packagings has been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'label' => 'New Packaging',

                'notification' => [
                    'title' => 'Packaging created',
                    'body'  => 'The packaging has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name' => 'Package Name',
                    'barcode' => 'Barcode',
                    'product' => 'Product',
                    'qty' => 'Quantity',
                    'package_type' => 'Package Type',
                ],
            ],

            'routing' => [
                'title' => 'Routing Information',

                'entries' => [
                    'routes' => 'Warehouse Routes',
                    'route_name' => 'Route Name',
                ],
            ],

            'organization' => [
                'title' => 'Organization Details',
                
                'entries' => [
                    'company' => 'Company',
                    'creator' => 'Created By',
                    'created_at' => 'Created At',
                    'updated_at' => 'Last Updated At',
                ],
            ],
        ],
    ],
];
