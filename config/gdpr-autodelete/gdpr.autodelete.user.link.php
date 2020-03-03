<?php

return [
    'plugins' => [
        'MelisCoreGdprAutoDelete' => [
            'tools' => [
                'melis_core_gdpr_auto_delete' => [
                    'forms' => [
                        'melisgdprautodelete_add_edit_alert_email' => [
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'mgdpre_link',
                                        'type' => "MelisText",
                                        'options' => [
                                            'label' => 'User will validate status on page:',
                                            'tooltip' => 'Link for revalidating user',
                                        ],
                                        'attributes' => [
                                            'id' => 'id_mgdpre_link',
                                            'class' => 'melis-input-group-button',
                                            'data-button-icon' => 'fa fa-sitemap',
                                            'data-button-id' => 'meliscms-site-selector',
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'melisgdprautodelete_add_edit_alert_email_delete' => [
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'mgdpre_link',
                                        'type' => "MelisText",
                                        'options' => [
                                            'label' => 'User will validate status on page:',
                                            'tooltip' => 'Link for revalidating user',
                                        ],
                                        'attributes' => [
                                            'id' => 'id_mgdpre_link',
                                            'class' => 'melis-input-group-button',
                                            'data-button-icon' => 'fa fa-sitemap',
                                            'data-button-id' => 'meliscms-site-selector',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ]
];