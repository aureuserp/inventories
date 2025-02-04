<?php

return [
    'navigation' => [
        'title' => 'Categories',
        'group' => 'Products',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'name'             => 'Name',
                    'name-placeholder' => 'eg. Lamps',
                    'parent'           => 'Parent',
                ],
            ],

            'settings' => [
                'title'  => 'Settings',

                'fieldsets' => [
                    'logistics' => [
                        'title' => 'Logistics',

                        'fields' => [
                            'routes' => 'Routes',
                        ],
                    ],

                    'inventory-valuation' => [
                        'title' => 'Inventory Valuation',
                    ],
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'        => 'Name',
            'full-name'   => 'Full Name',
            'parent-path' => 'Parent Path',
            'parent'      => 'Parent',
            'creator'     => 'Creator',
            'created-at'  => 'Created At',
            'created-at'  => 'Created At',
            'updated-at'  => 'Updated At',
        ],

        'groups' => [
            'parent'     => 'Parent',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'filters' => [
            'parent' => 'Parent',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Category deleted',
                    'body'  => 'The Category has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Categories deleted',
                    'body'  => 'The categories has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'        => 'Name',
                    'parent'      => 'Parent Category',
                    'full_name'   => 'Full Category Name',
                    'parent_path' => 'Category Path',
                ],
            ],

            'settings' => [
                'title' => 'Settings',

                'subsections' => [
                    'logistics' => [
                        'title' => 'Logistics',

                        'entries' => [
                            'routes'     => 'Warehouse Routes',
                            'route_name' => 'Route Name',
                        ],
                    ],
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'creator'    => 'Created By',
                    'created_at' => 'Created At',
                    'updated_at' => 'Last Updated At',
                ],
            ],
        ],
    ],
];
