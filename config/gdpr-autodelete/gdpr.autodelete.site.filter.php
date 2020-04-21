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
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ]
];