<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'tools' => array(
                'meliscms_tool_sites' => array(
                    'forms' => array(
                        'meliscms_tool_sites_domain_form' => array(
                            'attributes' => array(
                                'name' => 'meliscms_tool_sites_domain_form',
                                'id' => 'meliscms_tool_sites_domain_form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_domain_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_domain_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_site_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_domain_site_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_site_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_domain_site_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_env',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_domain_env',
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_env',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_domain_env',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_scheme',
                                        'type' => 'Laminas\Form\Element\Select',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_domain_scheme',
                                            'tooltip' => 'tr_melis_cms_sites_domain_scheme tooltip',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'value_options' => array(
                                                'https' => 'https',
                                                'http' => 'http',
                                            ),
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_scheme',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_domain_scheme',
                                            'required' => 'required'
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_domain',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_domain',
                                            'tooltip' => 'tr_melis_cms_sites_domain tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_domain',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_domain',
                                            'required' => 'required'
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                'sdom_id' => array(
                                    'name' => 'sdom_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_site_id' => array(
                                    'name' => 'sdom_site_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_env' => array(
                                    'name' => 'sdom_env',
                                    'required' => false,
                                    'validators' => array(),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_scheme' => array(
                                    'name' => 'sdom_scheme',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_melis_cms_sites_domain_field_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_domain' => array(
                                    'name' => 'sdom_domain',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_melis_cms_sites_domain_field_empty',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^(www\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?[a-zA-Z0-9\-]{1,}(\.([a-zA-Z]{2,}))$/',
                                                'messages' => array(
                                                    \Laminas\Validator\Regex::NOT_MATCH => 'tr_melis_cms_sites_tool_add_step3_invalid_domain_name',
                                                ),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),

                    ),

                ), // end Melis CMS Site Tool
            ),
        ),
    ),
);