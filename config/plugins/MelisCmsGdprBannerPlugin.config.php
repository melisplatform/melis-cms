<?php

return [
    'plugins' => [
        'MelisCmsGdprBanner' => [
            'plugins' => [
                'MelisCmsGdprBannerPlugin' => [
                    'front' => [
                        'template_path' => ['melis-cms-gdpr/banner'],
                        'id' => 'MelisCmsGdprBanner',

                        // List the files to be automatically included for the correct display of the plugin
                        // To overide a key, just add it again in your site module
                        // To delete an entry, use the keyword "disable" instead of the file path for the same key
                        'files' => [
                            'css' => [
                                '/MelisCms/plugins/css/gdprBannerPlugin.css',
                            ],
                            'js' => [
                                '/MelisCms/plugins/js/gdprBannerPlugin.js',
                            ],
                        ],
                    ],
                    'melis' => [
                        'name' => 'tr_melis_cms_gdpr_banner_plugin',
                        'thumbnail' => '/MelisCms/plugins/images/MelisCmsGdprBannerPlugin.jpg',
                        'description' => 'tr_melis_cms_gdpr_banner_plugin_desc',
                        'files' => [
                            'css' => [
                                '/MelisCms/plugins/css/gdprBannerPlugin.css'
                            ],
                            'js' => [
                                '/MelisCms/plugins/js/gdprBannerPlugin.js',
                            ],
                        ],
                        'js_initialization' => [],
                        'modal_form' => [
                            'melis_cms_gdpr_banner_plugin_settings_form' => [
                                'tab_title' => 'tr_melis_cms_gdpr_banner_plugin_properties',
                                'tab_icon' => 'fa fa-cog',
                                'tab_form_layout' => 'melis-cms-gdpr/modal-template-form',
                                'attributes' => [
                                    'name' => 'melis_cms_gdpr_banner_plugin_settings_form',
                                    'id' => 'id_melis_cms_gdpr_banner_plugin_settings_form',
                                    'method' => 'POST',
                                    'action' => '',
                                ],
                                'hydrator' => 'Zend\Stdlib\Hydrator\ArraySerializable',
                                'elements' => [
                                    [
                                        'spec' => [
                                            'name' => 'template_path',
                                            'type' => 'MelisEnginePluginTemplateSelect',
                                            'options' => [
                                                'label' => 'tr_melis_Plugins_Template',
                                                'tooltip' => 'tr_melis_Plugins_Template tooltip',
                                                'empty_option' => 'tr_melis_Plugins_Choose',
                                                'disable_inarray_validator' => true,
                                            ],
                                            'attributes' => [
                                                'id' => 'id_page_tpl_id',
                                                'class' => 'form-control',
                                                'required' => 'required',
                                            ],
                                        ],
                                    ],
                                ],
                                'input_filter' => [
                                    'template_path' => [
                                        'name' => 'template_path',
                                        'required' => true,
                                        'validators' => [
                                            [
                                                'name' => 'NotEmpty',
                                                'options' => [
                                                    'messages' => [
                                                        \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_front_template_path_empty',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'filters' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
