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
                                            'id' => 'mgdprc_site_id',
                                            'required' => 'required'
                                        ]
                                    ]
                                ]
                            ],
                            'input_filter' => [
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ]
];