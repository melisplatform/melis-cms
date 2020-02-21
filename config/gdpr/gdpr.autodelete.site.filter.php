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
                                        'name' => 'site_filter',
                                        'type' => "MelisCoreSiteSelect",
                                        'options' => [
                                            'empty_option' => 'Choose site',
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