<?php

return [
    'navigation' => [
        'title' => 'Rules',
        'group' => 'Warehouse Management',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'name'                        => 'Name',
                    'action'                      => 'Action',
                    'operation-type'              => 'Operation Type',
                    'source-location'             => 'Source Location',
                    'destination-location'        => 'Destination Location',
                    'supply-method'               => 'Supply Method',
                    'supply-method-hint-tooltip'  => 'Take From Stock: the products will be taken from the available stock of the source location.<br/>Trigger Another Rule: the system will try to find a stock rule to bring the products in the source location. The available stock will be ignored.<br/>Take From Stock, if Unavailable, Trigger Another Rule: the products will be taken from the available stock of the source location.If there is no stock available, the system will try to find a  rule to bring the products in the source location.',
                    'automatic-move'              => 'Automatic Move',
                    'automatic-move-hint-tooltip' => 'The \'Manual Operation\' value will create a stock move after the current one. With \'Automatic No Step Added\', the location is replaced in the original move.',

                    'action-information' => [
                        'pull' => 'When products are needed in <b>:sourceLocation</b>,</br><b>:operation</b> are created from <b>:destinationLocation</b> to fulfill the need.',
                        'push' => 'When products arrive in <b>:sourceLocation</b>,</br><b>:operation</b> are created to send them to <b>:destinationLocation</b>.',
                    ],
                ],
            ],

            'settings' => [
                'title'  => 'Settings',

                'fields' => [
                    'partner-address'              => 'Partner Address',
                    'partner-address-hint-tooltip' => 'Address where goods should be delivered. Optional.',
                    'lead-time'                    => 'Lead Time (Days)',
                    'lead-time-hint-tooltip'       => 'The expected date of the created transfer will be computed based on this lead time.',
                ],

                'fieldsets' => [
                    'applicability' => [
                        'title'  => 'Applicability',

                        'fields' => [
                            'route'   => 'Route',
                            'company' => 'Company',
                        ],
                    ],

                    'propagation' => [
                        'title'  => 'Propagation',

                        'fields' => [
                            'propagation-procurement-group'              => 'Propagation of Procurement Group',
                            'propagation-procurement-group-hint-tooltip' => 'When ticked, if the move created by this rule is cancelled, the next move will be cancelled too.',
                            'cancel-next-move'                           => 'Cancel Next Move',
                            'warehouse-to-propagate'                     => 'Warehouse to Propagate',
                            'warehouse-to-propagate-hint-tooltip'        => 'The warehouse to propagate on the created move/procurement, which can be different of the warehouse this rule is for (e.g for resupplying rules from another warehouse)',
                        ],
                    ],
                ],

            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'                 => 'Name',
            'action'               => 'Action',
            'source-location'      => 'Source Location',
            'destination-location' => 'Destination Location',
            'route'                => 'Route',
            'deleted-at'           => 'Deleted At',
            'created-at'           => 'Created At',
            'updated-at'           => 'Updated At',
        ],

        'groups' => [
            'action'               => 'Action',
            'source-location'      => 'Source Location',
            'destination-location' => 'Destination Location',
            'route'                => 'Route',
            'created-at'           => 'Created At',
            'updated-at'           => 'Updated At',
        ],

        'filters' => [
            'action'               => 'Action',
            'source-location'      => 'Source Location',
            'destination-location' => 'Destination Location',
            'route'                => 'Route',
            'company'              => 'Company',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Rule updated',
                    'body'  => 'The rule has been updated successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Rule restored',
                    'body'  => 'The rule has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rule deleted',
                    'body'  => 'The rule has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rule force deleted',
                    'body'  => 'The rule has been force deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rules restored',
                    'body'  => 'The rules has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rules deleted',
                    'body'  => 'The rules has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rules force deleted',
                    'body'  => 'The rules has been force deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Rule Details',

                'description' => [
                    'pull' => 'When products are needed in <b>:sourceLocation</b>, <b>:operation</b> are created from <b>:destinationLocation</b> to fulfill the need.',
                    'push' => 'When products arrive in <b>:sourceLocation</b>, <b>:operation</b> are created to send them to <b>:destinationLocation</b>.',
                ],

                'entries' => [
                    'name'                 => 'Rule Name',
                    'action'               => 'Action',
                    'operation-type'       => 'Operation Type',
                    'source-location'      => 'Source Location',
                    'destination-location' => 'Destination Location',
                    'route'                => 'Route',
                    'company'              => 'Company',
                    'partner-address'      => 'Partner Address',
                    'lead-time'            => 'Lead Time',
                    'action-information'   => 'Action Information',
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
