<?php

return [
    'plugins' => [
        'meliscore' => [
            'interface' => [
                'meliscore_leftmenu' => [
                    'interface' => [
                        'meliscore_toolstree_section' => [
                            'interface' => [
                                'meliscore_tool_admin_section' => [
                                    'interface' => [
                                        'melis_core_gdpr' => [
                                            'interface' => [
                                                'melis_core_gdpr_tabs' => [
                                                    'interface' => [
                                                        'melis_cms_gdpr_banner' => [
                                                            'conf' => [
                                                                'id' => 'id_melis_cms_gdpr_banner',
                                                                'name' => 'tr_melis_cms_gdpr_banner_tabname',
                                                                'melisKey' => 'melis_cms_gdpr_banner',
                                                                'icon' => 'bullhorn',
                                                            ],
                                                            'forward' => [
                                                                'module' => 'MelisCms',
                                                                'controller' => 'GdprBanner',
                                                                'action' => 'gdpr-banner-tab',
                                                            ],
                                                            'interface' => [
                                                                'melis_cms_gdpr_banner_header' => [
                                                                    'conf' => [
                                                                        'id' => 'id_melis_cms_gdpr_banner_header',
                                                                        'name' => 'tr_melis_cms_gdpr_banner_header',
                                                                        'melisKey' => 'melis_cms_gdpr_banner_header',
                                                                    ],
                                                                    'forward' => [
                                                                        'module' => 'MelisCms',
                                                                        'controller' => 'GdprBanner',
                                                                        'action' => 'header',
                                                                    ],
                                                                ],
                                                                'melis_cms_gdpr_banner_details' => [
                                                                    'conf' => [
                                                                        'id' => 'id_melis_cms_gdpr_banner_details',
                                                                        'name' => 'tr_melis_cms_gdpr_banner_details',
                                                                        'melisKey' => 'melis_cms_gdpr_banner_details',
                                                                    ],
                                                                    'forward' => [
                                                                        'module' => 'MelisCms',
                                                                        'controller' => 'GdprBanner',
                                                                        'action' => 'banner-details',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        /** Forms */
        'MelisCmsGdprBanner' => [
            'forms' => [
                /** Site filter */
                'site_filter_form' => [
                    'attributes' => [
                        'name' => 'cms_gdpr_banner_site_filter_form',
                        'id' => 'cms_gdpr_banner_site_filter_form',
                        'class' => 'cms_gdpr_banner_site_filter_form',
                        'method' => 'POST',
                        'action' => '',
                    ],
                    'hydrator' => 'Zend\Stdlib\Hydrator\ArraySerializable',
                    'elements' => [
                        [
                            'spec' => [
                                'name' => 'mcgdprbanner_site_id',
                                'type' => 'MelisCoreSiteSelect',
                                'options' => [
                                    'label' => 'tr_meliscms_page_tab_properties_form_type_Site',
                                    //'tooltip' => 'tr_meliscms_comments_form_site_tooltip',
                                    'empty_option' => 'tr_meliscms_comments_form_site',
                                    'disable_inarray_validator' => true,
                                ],
                                'attributes' => [
                                    'class' => 'mcgdprbanner_site_id',
                                    'id' => 'id_mcgdprbanner_site_id',
                                    'value' => '',
                                    'style' => "width: 30%",
                                ],
                            ],
                        ],
                    ],
                    'input_filter' => [
                        'mcgdprbanner_site_id' => [
                            'required' => true,
                            'validators' => [
                                [
                                    'name' => 'NotEmpty',
                                    'options' => [
                                        'messages' => [
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_cms_gdpr_banner_empty_field',
                                        ],
                                    ],
                                ],
                            ],
                            'filters' => [
                                ['name' => 'StripTags'],
                                ['name' => 'StringTrim'],
                            ],
                        ],
                    ],
                ],
                /** Banner Contents */
                'banner_content_form' => [
                    'attributes' => [
                        'name' => 'cms-gdpr-banner-content-form',
                        'id' => 'id-cms-gdpr-banner-content-form',
                        'method' => 'POST',
                        'action' => '',
                    ],
                    'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                    'elements' => [
                        [
                            'spec' => [
                                'name' => 'mcgdpr_text_id',
                                'type' => 'hidden',
                                'options' => [],
                                'attributes' => [
                                    'id' => 'mcgdpr_text_id',
                                    'value' => '',
                                ],
                            ],
                        ],
                        [
                            'spec' => [
                                'name' => 'mcgdpr_text_value',
                                'type' => 'TextArea',
                                'options' => [
                                    'label' => 'tr_melis_cms_gdpr_banner_content',
                                    'tooltip' => 'tr_melis_cms_gdpr_banner_content_tooltip',
                                ],

                                'attributes' => [
                                    'id' => 'id_mcgdpr_text_value',
                                    'value' => '',
                                    'class' => 'form-control mcgdpr_text_value',
                                    'style' => 'max-width:100%',
                                    'rows' => '12',
                                ],
                            ],
                        ],
                    ],
                    'input_filter' => [
//                        'mcgdpr_text_value' => [
//                            'required' => true,
//                            'validators' => [
//                                [
//                                    'name' => 'NotEmpty',
//                                    'options' => [
//                                        'messages' => [
//                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_cms_gdpr_banner_empty_field',
//                                        ],
//                                    ],
//                                ],
//                            ],
//                            'filters' => [
//                                ['name' => 'StripTags'],
//                                ['name' => 'StringTrim'],
//                            ],
//                        ],
                    ],
                ],
            ],
        ],
    ],
];
