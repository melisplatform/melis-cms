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
                                            'label' => 'Site',
                                            'tooltip' => 'Site',
                                            'empty_option' => 'Choose site',
                                            'disable_inarray_validator' => true,
                                        ],
                                        'attributes' => [
                                            'required' => 'required',
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
                                            'options' => [
                                                'messages' => [
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_core_gdpr_autodelete_choose_site',
                                                ]
                                            ]
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