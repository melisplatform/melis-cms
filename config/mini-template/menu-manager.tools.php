<?php
return [
    'plugins' => [
        'meliscms' => [
            'tools' => [
                'meliscms_mini_template_menu_manager_tool' => [
                    'conf' => [
                        'title' => 'Menu manager',
                        'id' => 'menumanager',
                    ],
                    'forms' => [
                        'menu_manager_tool_site' => [
                            'attributes' => [
                                'name' => 'menu_manager_tool_site',
                                'id' => 'id_menu_manager_tool_site',
                                'method' => 'POST',
                                'action' => ''
                            ],
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'menuManagerSite',
                                        'type' => 'MelisCoreSiteSelect',
                                        'options' => [
                                            'label' => 'Site',
                                            'tooltip' => 'Site where the minitemplate will be created',
                                            'empty_option' => 'No site',
                                            'disable_inarray_validator' => true,
                                        ],
                                        'attributes' => [
                                            'id' => 'menuManagerSite',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                            ],
                            'input_filter' => [
                                'menuManagerSite' => [
                                    'name' => 'menuManagerSite',
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
                    ],
                ],
            ],
        ],
    ],
];