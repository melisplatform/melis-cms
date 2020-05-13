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
                                            'label' => 'tr_melis_core_gdpr_autodelete_label_alert_email_link',
                                            'tooltip' => 'tr_melis_core_gdpr_autodelete_label_alert_email_link tooltip',
                                            'label_attribute' => [
                                                'class' => "d-flex flex-row justify-content-between"
                                            ],
                                        ],
                                        'attributes' => [
                                            'id' => 'id_mgdpre_link',
                                            'class' => 'melis-input-group-button',
                                            'data-button-icon' => 'fa fa-sitemap',
                                            'data-button-id' => 'meliscms-site-selector',
                                        ]
                                    ]
                                ],
                            ],
                            'input_filter' => [
                                'mgdpre_link' => [
                                    'name' => 'mgdpre_link',
                                    'required' => false,
                                    'validators' => [
                                        [
                                            'name' => 'IsInt',
                                            'options' => [
                                                'messages' => [
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscore_gdpr_auto_delete_not_int',
                                                ]
                                            ]
                                        ]
                                    ],
                                    'filters' => [
                                        ['name' => 'StripTags'],
                                        ['name' => 'StringTrim']
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
            ]
        ]
    ]
];
