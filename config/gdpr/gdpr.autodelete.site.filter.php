<?php

return [
    'plugins' => [
        'MelisCoreGdprAutoDelete' => [
            'tools' => [
                'melis_core_gdpr_auto_delete' => [
                    'forms' => [
                        'melisgdprautodelete_add_edit_config_filters' => [
                            'attributes' => array(
                                'name' => 'melisgdprautodelete_add_edit_config_filters',
                                'id' => 'id_melisgdprautodelete_add_edit_config_filters',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator' => 'Zend\Stdlib\Hydrator\ArraySerializable',
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