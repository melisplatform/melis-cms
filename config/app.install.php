<?php
return array(
    'plugins' => array(
        'melis_cms_setup' => array(
            'forms' => array(
                'melis_installer_platform_id' => array(
                    'attributes' => array(
                        'name' => 'form_platform_id',
                        'id'   => 'id_form_platform_id',
                        'method' => 'POST',
                        'action' => '',
                    ),
                    'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                    'elements'  => array(
                        array(
                            'spec' => array(
                                'name' => 'pids_id',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_platform_id',
                                    'tooltip' => 'tr_melis_installer_platform_id_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_id',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_platform_id',
                                )
                            )
                        ),
                        array(
                            'spec' => array(
                                'name' => 'pids_page_id_start',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_pids_page_id_start',
                                    'tooltip' => 'tr_melis_installer_pids_page_id_start_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_page_id_start',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_pids_page_id_start',
                                )
                            )
                        ),
                        array(
                            'spec' => array(
                                'name' => 'pids_page_id_current',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_pids_page_id_current',
                                    'tooltip' => 'tr_melis_installer_pids_page_id_current_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_page_id_current',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_pids_page_id_current',
                                    'class' => 'form-control',
                                ),
                            ),
                        ),
                        array(
                            'spec' => array(
                                'name' => 'pids_tpl_id_start',
                                'type' => 'Password',
                                'options' => array(
                                    'label' => 'tr_melis_installer_pids_tpl_id_start',
                                    'tooltip' => 'tr_melis_installer_pids_tpl_id_start_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_tpl_id_start',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_pids_tpl_id_start',
                                    'class' => 'form-control',
                                ),
                            ),
                        ),
                        array(
                            'spec' => array(
                                'name' => 'pids_tpl_id_current',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_pids_tpl_id_current',
                                    'tooltip' => 'tr_melis_installer_pids_tpl_id_current_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_tpl_id_current',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_pids_tpl_id_current',
                                )
                            )
                        ),
                        array(
                            'spec' => array(
                                'name' => 'pids_tpl_id_end',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_pids_tpl_id_end',
                                    'tooltip' => 'tr_melis_installer_pids_tpl_id_end_info',
                                ),
                                'attributes' => array(
                                    'id' => 'pids_tpl_id_end',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_pids_tpl_id_end',
                                )
                            )
                        ),
                    ), // end elements
                    'input_filter' => array(
                        'login' => array(
                            'name'     => 'login',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_login_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_login_empty',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'regex', false,
                                    'options' => array(
                                        'pattern' => '/^[A-Za-z][A-Za-z0-9]*$/',
                                        'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_melis_installer_new_user_login_invalid'),
                                        'encoding' => 'UTF-8',
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'email' => array(
                            'name'     => 'email',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => 'EmailAddress',
                                    'options' => array(
                                        'domain'   => 'true',
                                        'hostname' => 'true',
                                        'mx'       => 'true',
                                        'deep'     => 'true',
                                        'message'  => 'tr_melis_installer_new_user_email_invalid',
                                    )
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_email_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'password' => array(
                            'name'     => 'password',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => '\MelisInstaller\Validator\MelisPasswordValidator',
                                    'options' => array(
                                        'min' => 8,
                                        'messages' => array(
                                            \MelisInstaller\Validator\MelisPasswordValidator::TOO_SHORT => 'tr_melis_installer_new_user_pass_short',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_DIGIT => 'tr_melis_installer_new_user_pass_invalid',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_LOWER => 'tr_melis_installer_new_user_pass_invalid',
                                        ),
                                    ),
                                ),
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_pass_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_pass_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'confirmPassword' => array(
                            'name'     => 'confirmPassword',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => '\MelisInstaller\Validator\MelisPasswordValidator',
                                    'options' => array(
                                        'min' => 8,
                                        'messages' => array(
                                            \MelisInstaller\Validator\MelisPasswordValidator::TOO_SHORT => 'tr_melis_installer_new_user_pass_short',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_DIGIT => 'tr_melis_installer_new_user_pass_invalid',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_LOWER => 'tr_melis_installer_new_user_pass_invalid',
                                        ),
                                    ),
                                ),
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_pass_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_pass_empty',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'Identical',
                                    'options' => array(
                                        'token' => 'password',
                                        'messages' => array(
                                            \Zend\Validator\Identical::NOT_SAME => 'tr_melis_installer_new_user_pass_no_match',
                                        ),
                                    ),
                                )
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'firstname' => array(
                            'name'     => 'firstname',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        //'min'      => 1,
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_first_name_long',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_first_name_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'lastname' => array(
                            'name'     => 'lastname',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        //'min'      => 1,
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_last_name_long',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_last_name_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                    ), // end input_filter
                ), // end melis_installer_platform_id
                'melis_installer_site_' => array(
                    'attributes' => array(
                        'name' => 'form_melis_installer_site',
                        'id'   => 'id_form_melis_installer_site',
                        'method' => 'POST',
                        'action' => '',
                    ),
                    'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                    'elements'  => array(
                        array(
                            'spec' => array(
                                'name' => 'sitename',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_site_name',
                                    'tooltip' => 'tr_melis_installer_site_name_info',
                                ),
                                'attributes' => array(
                                    'id' => 'login',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_site_name',
                                )
                            )
                        ),
                    ), // end elements
                    'input_filter' => array(
                        'sitename' => array(
                            'name'     => 'sitename',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_login_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_login_empty',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'regex', false,
                                    'options' => array(
                                        'pattern' => '/^[A-Za-z][A-Za-z0-9]*$/',
                                        'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_melis_installer_new_user_login_invalid'),
                                        'encoding' => 'UTF-8',
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                    ), // end input_filter
                ), // end melis_installer_site_
                'melis_installer_domain' => array(
                    'attributes' => array(
                        'name' => 'form_melis_installer_domain',
                        'id'   => 'id_form_melis_installer_domain',
                        'method' => 'POST',
                        'action' => '',
                    ),
                    'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                    'elements'  => array(
                        array(
                            'spec' => array(
                                'name' => 'sdom_env',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_sdom_env',
                                    'tooltip' => 'tr_melis_installer_sdom_env_info',
                                ),
                                'attributes' => array(
                                    'id' => 'sdom_env',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_sdom_env',
                                )
                            )
                        ),
                        array(
                            'spec' => array(
                                'name' => 'sdom_scheme',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_sdom_scheme',
                                    'tooltip' => 'tr_melis_installer_sdom_scheme_info',
                                ),
                                'attributes' => array(
                                    'id' => 'sdom_scheme',
                                    'value' => '',
                                    'placeholder' => 'http / https',
                                )
                            )
                        ),
                        array(
                            'spec' => array(
                                'name' => 'sdom_domain',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_melis_installer_sdom_domain',
                                    'tooltip' => 'tr_melis_installer_sdom_domain_info',
                                ),
                                'attributes' => array(
                                    'id' => 'sdom_domain',
                                    'value' => '',
                                    'placeholder' => 'tr_melis_installer_sdom_domain',
                                )
                            )
                        ),
                    ), // end elements
                    'input_filter' => array(
                        'login' => array(
                            'name'     => 'login',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_login_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_login_empty',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'regex', false,
                                    'options' => array(
                                        'pattern' => '/^[A-Za-z][A-Za-z0-9]*$/',
                                        'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_melis_installer_new_user_login_invalid'),
                                        'encoding' => 'UTF-8',
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'email' => array(
                            'name'     => 'email',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => 'EmailAddress',
                                    'options' => array(
                                        'domain'   => 'true',
                                        'hostname' => 'true',
                                        'mx'       => 'true',
                                        'deep'     => 'true',
                                        'message'  => 'tr_melis_installer_new_user_email_invalid',
                                    )
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_email_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'password' => array(
                            'name'     => 'password',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => '\MelisInstaller\Validator\MelisPasswordValidator',
                                    'options' => array(
                                        'min' => 8,
                                        'messages' => array(
                                            \MelisInstaller\Validator\MelisPasswordValidator::TOO_SHORT => 'tr_melis_installer_new_user_pass_short',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_DIGIT => 'tr_melis_installer_new_user_pass_invalid',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_LOWER => 'tr_melis_installer_new_user_pass_invalid',
                                        ),
                                    ),
                                ),
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_pass_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_pass_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'confirmPassword' => array(
                            'name'     => 'confirmPassword',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name' => '\MelisInstaller\Validator\MelisPasswordValidator',
                                    'options' => array(
                                        'min' => 8,
                                        'messages' => array(
                                            \MelisInstaller\Validator\MelisPasswordValidator::TOO_SHORT => 'tr_melis_installer_new_user_pass_short',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_DIGIT => 'tr_melis_installer_new_user_pass_invalid',
                                            \MelisInstaller\Validator\MelisPasswordValidator::NO_LOWER => 'tr_melis_installer_new_user_pass_invalid',
                                        ),
                                    ),
                                ),
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_pass_max',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_pass_empty',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'Identical',
                                    'options' => array(
                                        'token' => 'password',
                                        'messages' => array(
                                            \Zend\Validator\Identical::NOT_SAME => 'tr_melis_installer_new_user_pass_no_match',
                                        ),
                                    ),
                                )
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'firstname' => array(
                            'name'     => 'firstname',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        //'min'      => 1,
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_first_name_long',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_first_name_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                        'lastname' => array(
                            'name'     => 'lastname',
                            'required' => true,
                            'validators' => array(
                                array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                        'encoding' => 'UTF-8',
                                        //'min'      => 1,
                                        'max'      => 255,
                                        'messages' => array(
                                            \Zend\Validator\StringLength::TOO_LONG => 'tr_melis_installer_new_user_last_name_long',
                                        ),
                                    ),
                                ),
                                array(
                                    'name' => 'NotEmpty',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_installer_new_user_last_name_empty',
                                        ),
                                    ),
                                ),
                            ),
                            'filters'  => array(
                                array('name' => 'StripTags'),
                                array('name' => 'StringTrim'),
                            ),
                        ),
                    ), // end input_filter
                ), // end melis_installer_domain
            ),
        ),
    ),
);