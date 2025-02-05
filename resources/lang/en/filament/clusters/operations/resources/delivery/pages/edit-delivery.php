<?php

return [
    'notification' => [
        'title' => 'Delivery updated',
        'body'  => 'The delivery has been updated successfully.',
    ],

    'header-actions' => [
        'todo' => [
            'label' => 'Mark as Todo',
        ],

        'validate' => [
            'label' => 'Validate',
            'modal-heading' => 'Create Back Order?',
            'modal-description' => 'Create a back order if you expect to process the remaining products later. Do not create a back order if you will not process the remaining products.',

            'extra-modal-footer-actions' => [
                'no-backorder' => [
                    'label' => 'No Backorder',
                ],
            ],
        ],

        'cancel' => [
            'label' => 'Cancel',
        ],

        'return' => [
            'label' => 'Return',
        ],

        'delete' => [
            'notification' => [
                'title' => 'Delivery deleted',
                'body'  => 'The delivery has been deleted successfully.',
            ],
        ],
    ],
];
