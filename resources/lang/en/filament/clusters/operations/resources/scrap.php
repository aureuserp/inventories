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
                    'destination-location' => 'Scrap Location',
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
            'state'           => 'State',
        ],

        'groups' => [
            'product'              => 'Product',
            'source-location'      => 'Source Location',
            'destination-location' => 'Scrap Location',
        ],

        'filters' => [
            'source-location'      => 'Source Location',
            'destination-location' => 'Scrap Location',
            'product'              => 'Product',
            'state'                => 'State',
            'product-category'     => 'Product Category',
            'lot'                  => 'Lot/Serial',
            'package'              => 'Package',
            'tags'                 => 'Tags',
            'company'              => 'Company',
            'quantity'             => 'Quantity',
            'creator'              => 'Creator',
            'closed-at'            => 'Closed At',
            'created-at'           => 'Created At',
            'updated-at'           => 'Updated At',
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

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Scrap Details',

                'entries' => [
                    'product' => 'Product',
                    'quantity' => 'Quantity',
                    'lot' => 'Lot',
                    'tags' => 'Tags',
                    'package' => 'Package',
                    'owner' => 'Owner',
                    'source-location' => 'Source Location',
                    'destination-location' => 'Destination Location',
                    'source-document' => 'Source Document',
                    'company' => 'Company',
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'created-by' => 'Created By',
                    'created-at' => 'Created At',
                    'last-updated' => 'Last Updated',
                ],
            ],
        ],
    ],
];
