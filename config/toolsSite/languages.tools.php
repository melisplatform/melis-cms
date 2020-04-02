<?php
    return [
        'plugins' => [
            'meliscms' => [
                'tools' => [
                    'meliscms_tool_sites' => [
                        'forms' => [
                            'meliscms_tool_sites_languages_form' => [
                                'attributes' => [
                                    'name' => 'meliscms_tool_sites_languages_form',
                                    'id' => 'id_meliscms_tool_sites_languages_form',
                                    'method' => 'POST',
                                    'action' => '',
                                ],
                                'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
                                'elements' => [
                                    [
                                        'spec' => [
                                            'type' => 'Laminas\Form\Element\Radio',
                                            'name' => 'site_opt_lang_url',
                                            'options' => [
                                                'label' => 'tr_melis_cms_sites_tool_languages_question2',
                                                'label_options' => [
                                                    'disable_html_escape' => true,
                                                ],
                                                'label_attributes' => [
                                                    'class' => 'melis-radio-box'
                                                ],
                                                'value_options' => [
                                                    '2' => 'tr_melis_cms_sites_tool_languages_option2',
                                                    '1' => 'tr_melis_cms_sites_tool_languages_option1',
                                                ],
                                            ],
                                            'attributes' => [
                                                'value' => 'modal',
                                                'class' => 'moudle-name'
                                            ],
                                        ]
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