<?php

return [
    'plugins' => [
        'MelisCoreGdprAutoDelete' => [
            'tools' => [
                'melis_core_gdpr_auto_delete' => [
                    'forms' => [
                        'melisgdprautodelete_add_edit_config_filters' => [
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'mgdprc_site_id',
                                        'type' => "MelisCoreSiteSelect",
                                        'options' => [
                                            'label' => 'tr_melis_core_gdpr_auto_delete_site',
                                            'tooltip' => 'tr_melis_core_gdpr_auto_delete_site tooltip',
                                            'empty_option' => 'tr_melis_core_gdpr_auto_delete_site tooltip',
                                            'disable_inarray_validator' => true,
                                        ],
                                        'attributes' => [
                                            'id' => 'mgdprc_site_id',
                                            'required' => 'required'
                                        ]
                                    ]
                                ]
                            ],
                            'input_filter' => [
                                'mgdprc_site_id' => [
                                    'name' => 'mgdprc_site_id',
                                    'required' => true,
                                    'validators' => [
                                        [
                                            'name' => 'NotEmpty',
                                            'break_chain_on_failure' => true,
                                            'options' => [
                                                'messages' => [
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscore_emails_mngt_tool_general_properties_form_empty',
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
                        ]
                    ]
                ],
            ]
        ]
    ]
];