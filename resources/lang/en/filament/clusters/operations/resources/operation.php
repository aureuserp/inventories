<?php

return [
    'navigation' => [
        'title' => 'Products',
        'group' => 'Inventory',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'receive-from'         => 'Receive From',
                    'contact'              => 'Contact',
                    'delivery-address'     => 'Delivery Address',
                    'operation-type'       => 'Operation Type',
                    'source-location'      => 'Source Location',
                    'destination-location' => 'Destination Location',
                ],
            ],
        ],

        'tabs' => [
            'operations' => [
                'title' => 'Operations',

                'fields' => [
                    'product'        => 'Product',
                    'final-location' => 'Final Location',
                    'description'    => 'Description',
                    'scheduled-at'   => 'Scheduled At',
                    'deadline'       => 'Deadline',
                    'packaging'      => 'Packaging',
                    'demand'         => 'Demand',
                    'quantity'       => 'Quantity',
                    'unit'           => 'Unit',
                    'picked'         => 'Picked',

                    'lines' => [
                        'modal-heading' => 'Manage Stock Moves',
                        'add-line'      => 'Add Line',

                        'fields' => [
                            'lot'       => 'Lot/Serial Number',
                            'pick-from' => 'Pick From',
                            'location'  => 'Store To',
                            'package'   => 'Destination Package',
                            'quantity'  => 'Quantity',
                            'uom'       => 'Unit of Measure',
                        ],
                    ],
                ],
            ],

            'additional' => [
                'title' => 'Additional',

                'fields' => [
                    'responsible'                  => 'Responsible',
                    'shipping-policy'              => 'Shipping Policy',
                    'shipping-policy-hint-tooltip' => 'It specifies goods to be deliver partially or all at once.',
                    'scheduled-at'                 => 'Scheduled At',
                    'scheduled-at-hint-tooltip'    => 'Scheduled time for the first part of the shipment to be processed. Setting manually a value here would set it as expected date for all the stock moves.',
                    'source-document'              => 'Source Document',
                    'source-document-hint-tooltip' => 'Reference of the document',
                ],
            ],

            'note' => [
                'title' => 'Note',

                'fields' => [

                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'reference'       => 'Reference',
            'from'            => 'From',
            'to'              => 'To',
            'contact'         => 'Contact',
            'responsible'     => 'Responsible',
            'scheduled-at'    => 'Scheduled At',
            'deadline'        => 'Deadline',
            'closed-at'       => 'Closed At',
            'source-document' => 'Source Document',
            'operation-type'  => 'Operation Type',
            'company'         => 'Company',
            'state'           => 'State',
            'deleted-at'      => 'Deleted At',
            'created-at'      => 'Created At',
            'updated-at'      => 'Updated At',
        ],

        'groups' => [
            'state'           => 'State',
            'source-document' => 'Source Document',
            'operation-type'  => 'Operation Type',
            'schedule-at'     => 'Schedule At',
            'created-at'      => 'Created At',
        ],

        'filters' => [
            'name'                 => 'Name',
            'state'                => 'State',
            'partner'              => 'Partner',
            'responsible'          => 'Responsible',
            'owner'                => 'Owner',
            'source-location'      => 'Source Location',
            'destination-location' => 'Destination Location',
            'deadline'             => 'Deadline',
            'scheduled-at'         => 'Scheduled At',
            'closed-at'            => 'Closed At',
            'created-at'           => 'Created At',
            'updated-at'           => 'Updated At',
            'company'              => 'Company',
            'creator'              => 'Creator',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Product restored',
                    'body'  => 'The product has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Product deleted',
                    'body'  => 'The product has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Product force deleted',
                    'body'  => 'The product has been force deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Products restored',
                    'body'  => 'The products has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Products deleted',
                    'body'  => 'The products has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Products force deleted',
                    'body'  => 'The products has been force deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'General Information',
                'entries' => [
                    'contact'              => 'Contact',
                    'operation-type'       => 'Operation Type',
                    'source-location'      => 'Source Location',
                    'destination-location' => 'Destination Location',
                ],
            ],
        ],

        'tabs' => [
            'operations' => [
                'title'   => 'Operations',
                'entries' => [
                    'product'        => 'Product',
                    'final-location' => 'Final Location',
                    'description'    => 'Description',
                    'scheduled-at'   => 'Scheduled At',
                    'deadline'       => 'Deadline',
                    'packaging'      => 'Packaging',
                    'demand'         => 'Demand',
                    'quantity'       => 'Quantity',
                    'unit'           => 'Unit',
                    'picked'         => 'Picked',
                ],
            ],
            'additional' => [
                'title'   => 'Additional Information',
                'entries' => [
                    'responsible'     => 'Responsible',
                    'shipping-policy' => 'Shipping Policy',
                    'scheduled-at'    => 'Scheduled At',
                    'source-document' => 'Source Document',
                ],
            ],
            'note' => [
                'title' => 'Note',
            ],
        ],
    ],

    'tabs' => [
        'todo'     => 'To Do',
        'my'       => 'My Transfers',
        'starred'  => 'Starred',
        'draft'    => 'Draft',
        'waiting'  => 'Waiting',
        'ready'    => 'Ready',
        'done'     => 'Done',
        'canceled' => 'Canceled',
    ],

    'header-actions' => [
        'todo' => [
            'notification' => [
                'warning' => [
                    'title' => 'Receipt has no moves',
                    'body'  => 'The receipt has no moves to mark as todo.',
                ],

                'success' => [
                    'title' => 'Receipt marked as todo',
                    'body'  => 'The receipt has been marked as todo successfully.',
                ],
            ],
        ],

        'validate' => [
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
        ],
    ],
];
