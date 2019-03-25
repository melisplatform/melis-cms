<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'tools' => array(
                'meliscms_tool_sites' => array(
                    'forms' => array(
                        'meliscms_tool_sites_properties_form' => array(
                            'attributes' => array(
                                'name' => 'meliscms_tool_sites_properties_form',
                                'id' => 'meliscms_tool_sites_properties_form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'site_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_site_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_site_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_id_disp',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_site_id',
                                            'tooltip' => 'tr_melis_cms_sites_site_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_id_disp',
                                            'value' => '',
                                            'disabled' => 'true',
                                            'read-only' => 'true',
                                            'placeholder' => 'tr_melis_cms_sites_site_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_label',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_site_label',
                                            'tooltip' => 'tr_melis_cms_sites_site_label tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_label',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_site_label',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_site_name',
                                            'tooltip' => 'tr_melis_cms_sites_site_name tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_name',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_site_name',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's404_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_s404_page_id',
                                            'tooltip' => 'tr_melis_cms_sites_s404_page_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 's404_page_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_s404_page_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_main_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_site_main_page_id',
                                            'tooltip' => 'tr_melis_cms_sites_site_main_page_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_main_page_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_site_main_page_id',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                'site_id' => array(
                                    'name' => 'site_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_id_disp' => array(
                                    'name' => 'site_id_disp',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_label' => array(
                                    'name' => 'site_label',
                                    'required' => false,
                                    'validators' => array(),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_name' => array(
                                    'name' => 'site_name',
                                    'required' => false,
                                    'validators' => array(),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                's404_page_id' => array(
                                    'name' => 's404_page_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_main_page_id' => array(
                                    'name' => 'site_main_page_id',
                                    'required' => false,
                                    'validators' => array(),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),

                        'meliscms_tool_sites_properties_homepage_form' => array(
                            'attributes' => array(
                                'name' => 'meliscms_tool_sites_properties_homepage_form',
                                'id' => 'meliscms_tool_sites_properties_homepage_form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'shome_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_shome_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'shome_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_shome_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'shome_lang_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_shome_lang_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'shome_lang_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_shome_lang_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'shome_site_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_shome_site_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'shome_site_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_sshome_site_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'shome_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_shome_page_id',
                                            'tooltip' => 'tr_melis_cms_sites_shome_page_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'shome_page_id',
                                            'value' => '',
                                            'placeholder' => 'tr_melis_cms_sites_shome_page_id',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                'shome_id' => array(
                                    'name' => 'shome_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'shome_site_id' => array(
                                    'name' => 'shome_site_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'shome_lang_id' => array(
                                    'name' => 'shome_lang_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'shome_page_id' => array(
                                    'name' => 'shome_page_id',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'invalid id',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'invalid id',
                                                )
                                            )
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