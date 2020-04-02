<?php
return [
    'plugins' => [
        'meliscms' => [
            'tools' => [
                'meliscms_tool_sites' => [
                    'forms' => [
                        'meliscms_tool_sites_siteconfig_form' => [
                            'attributes' => [
                                'name' => 'meliscms_tool_sites_siteconfig_form',
                                'id' => 'id_meliscms_tool_sites_siteconfig_form',
                                'method' => 'POST',
                                'action' => '',
                            ],
                            'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'sconf_id',
                                        'type' => 'hidden',
                                        'options' => [
                                        ],
                                        'attributes' => [
                                            'id' => 'sconf_id',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'spec' => [
                                        'name' => 'sconf_site_id',
                                        'type' => 'hidden',
                                        'options' => [
                                        ],
                                        'attributes' => [
                                            'id' => 'sconf_site_id',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'spec' => [
                                        'name' => 'sconf_lang_id',
                                        'type' => 'hidden',
                                        'options' => [
                                        ],
                                        'attributes' => [
                                            'id' => 'sconf_lang_id',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                            ],
                            'input_filter' => [

                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];