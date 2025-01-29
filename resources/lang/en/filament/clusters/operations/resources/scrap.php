<?php

return [
    'navigation' => [
        'title' => 'Scraps',
        'group' => 'Adjustments',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'product'              => 'Product',
                    'package'              => 'Package',
                    'quantity'             => 'Quantity',
                    'unit'                 => 'Unit of Measure',
                    'lot'                  => 'Lot/Serial',
                    'tags'                 => 'Tags',
                    'name'                 => 'Name',
                    'color'                => 'Color',
                    'owner'                => 'Owner',
                    'source-location'      => 'Source Location',
                    'destination-location' => 'Destination Location',
                    'source-document'      => 'Source Document',
                    'company'              => 'Company',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'date'            => 'Date',
            'reference'       => 'Reference',
            'product'         => 'Product',
            'package'         => 'Package',
            'quantity'        => 'Quantity',
            'uom'             => 'Unit of Measure',
            'source-location' => 'Source Location',
            'scrap-location'  => 'Scrap Location',
            'unit'            => 'Unit of Measure',
            'lot'             => 'Lot/Serial',
            'tags'            => 'Tags',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Scrap deleted',
                    'body'  => 'The scrap has been deleted successfully.',
                ],
            ],
        ],
    ],
];
