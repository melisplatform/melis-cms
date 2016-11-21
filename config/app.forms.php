<?php

return array(
	'plugins' => array(
		'meliscms' => array(
			'forms' => array(
				'meliscms_page_properties' => array(
					'attributes' => array(
						'name' => 'pageproperties',
						'id' => 'idformpageproperties',
						'method' => 'POST',
						'action' => '/melis/MelisCms/Page/saveProperties',
					),
					'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
					'elements' => array(  
							array(
								'spec' => array(
									'name' => 'page_id',
									'type' => 'MelisText',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Id',
									),
									'attributes' => array(
										'id' => 'id_page_id',
										'value' => '',
										'disabled' => 'hidden',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_name',
									'type' => 'MelisText',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Name',
									),
									'attributes' => array(
										'id' => 'id_page_name',
										'value' => '',
									    'placeholder' => 'tr_meliscms_page_tab_properties_form_Name'
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_type',
									'type' => 'Zend\Form\Element\Select',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Type',
										'empty_option' => 'tr_meliscms_form_common_Choose',
										'value_options' => array(
											'SITE' => 'tr_meliscms_page_tab_properties_form_type_Site',
											'FOLDER' => 'tr_meliscms_page_tab_properties_form_type_Folder',
											'PAGE' => 'tr_meliscms_page_tab_properties_form_type_Page',
										),
									),
									'attributes' => array(
										'id' => 'id_page_type',
										'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'plang_lang_id',
									'type' => 'MelisCoreLanguageSelect',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Language',
										'empty_option' => 'tr_meliscms_form_common_Choose',
									    'disable_inarray_validator' => true,
									),
									'attributes' => array( 
										'id' => 'id_plang_lang_id',
									),
								),
							),
					/*		array(
								'spec' => array(
									'name' => 'page_status',
									'type' => 'Zend\Form\Element\Select',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Status',
										'value_options' => array(
										    '1' => 'tr_meliscms_page_tab_properties_form_status_Online',
											'0' => 'tr_meliscms_page_tab_properties_form_status_Offline',
										),
									),
									'attributes' => array(
											'id' => 'id_page_status',
											'value' => '',
										),
								),
							), */
							array(
								'spec' => array(
									'name' => 'page_menu',
									'type' => 'Zend\Form\Element\Select',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Show Menu',
										'empty_option' => 'tr_meliscms_form_common_Choose',
										'value_options' => array(
											'LINK' => 'tr_meliscms_page_tab_properties_form_showmenu_Link',
											'NOLINK' => 'tr_meliscms_page_tab_properties_form_showmenu_No link',
											'NONE' => 'tr_meliscms_page_tab_properties_form_showmenu_None',
										),
									),
									'attributes' => array(
											'id' => 'id_page_menu',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_tpl_id',
									'type' => 'MelisCmsTemplateSelect',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Template',
										'empty_option' => 'tr_meliscms_form_common_Choose',
									    'disable_inarray_validator' => true,
									),
									'attributes' => array(
										'id' => 'id_page_tpl_id',
									),
								),
							),
					/*		array(
								'spec' => array(
									'name' => 'page_meta_title',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_properties_form_Meta Title',
									),
									'attributes' => array(
											'id' => 'page_meta_title',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_meta_keywords',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_properties_form_Meta Keywords',
									),
									'attributes' => array(
											'id' => 'page_meta_keywords',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_meta_description',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_properties_form_Meta Description',
									),
									'attributes' => array(
											'id' => 'page_meta_description',
											'value' => '',
									),
								),
							),*/
							array(
								'spec' => array(
									'name' => 'page_creation_date',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_properties_form_Creation date',
									),
									'attributes' => array(
											'id' => 'page_creation_date',
											'value' => '',
											'disabled' => 'disabled',
									),
								),
							),
    					    array(
    					        'spec' => array(
    					            'name' => 'page_taxonomy',
    					            'type' => 'MelisMultiValInput',
    					            'options' => array(
    					            ),
    					            'attributes' => array(
    					                'id' => 'id_page_taxonomy',
    					                'value' => '',
    					                'data-label-text' => 'tr_meliscms_page_tab_properties_form_taxonomy',
    					                'placeholder' => 'tr_meliscms_page_tab_properties_form_taxonomy_placeholder',
    					            ),
    					        ),
    					    ),
						),
						'input_filter' => array(      
							'page_id' => array(
								'name'     => 'page_id',
								'required' => false,
					            'validators' => array(
						            array(
										'name'    => 'IsInt',
						            ),
					            ),
					            'filters' => array(
					            ),
							),       
					        'page_name' => array(
								'name'     => 'page_name',
								'required' => true,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											//'min'      => 1,
											'max'      => 255,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_page_form_page_name_long',
										    ),
										),
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_name_empty',
								            ),
								        ),
								    ),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ), 
					        'page_type' => array(
								'name'     => 'page_type',
								'required' => true,
								'validators' => array( 
									array(
										'name'    => 'InArray',
										'options' => array(
											'haystack' => array('SITE', 'FOLDER', 'PAGE'),
										    'messages' => array(
										        \Zend\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_page_form_page_type_invalid',
										    ),
										)
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_type_empty',
								            ),
								        ),
								    ),
								),
								'filters'  => array(
								),
					        ),
							'plang_lang_id' => array(
								'name'     => 'plang_lang_id',
								'required' => true,
					            'validators' => array(
					                array(
					                    'name' => 'NotEmpty',
					                    'options' => array(
					                        'messages' => array(
					                            \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_plang_lang_id_empty',
					                        ),
					                    ),
					                ),
					            ),
					            'filters' => array(
					            ),
							),       
					/*        'page_status' => array(
								'name'     => 'page_status',
								'required' => true,
								'validators' => array( 
									array(
										'name'    => 'InArray',
										'options' => array(
											'haystack' => array(0, 1)
										)
									),
								),
								'filters'  => array(
								),
					        ),*/
					        'page_menu' => array(
								'name'     => 'page_menu',
								'required' => true,
								'validators' => array( 
									array(
										'name'    => 'InArray',
										'options' => array(
											'haystack' => array('LINK', 'NOLINK', 'NONE'),
										    'messages' => array(
										        \Zend\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_page_form_page_menu_invalid',
										    ),
										)
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_menu_empty',
								            ),
								        ),
								    ),
								),
								'filters'  => array(
								),
					        ),
					        'page_tpl_id' => array(
								'name'     => 'page_tpl_id',
								'required' => true,
								'validators' => array( 
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_tpl_id_empty',
								            ),
								        ),
								    ),
								),
								'filters'  => array(
								),
					        ),
						),
					),
				'meliscms_page_seo' => array(
					'attributes' => array(
						'name' => 'pageseo',
						'id' => 'idformpageseo',
						'method' => 'POST',
						'action' => '/melis/MelisCms/PageSeo/saveSeo',
					),
					'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
					'elements' => array(  
							array(
								'spec' => array(
									'name' => 'pseo_meta_title',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_seo_form_Meta Title',
									),
									'attributes' => array(
											'id' => 'pseo_meta_title',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'pseo_meta_description',
									'type' => 'Textarea',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_seo_form_Meta Description',
									),
									'attributes' => array(
											'id' => 'pseo_meta_description',
											'value' => '',
    									    'rows' => 5,
    									    'class' => 'melis-seo-desc form-control'
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'pseo_url',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_seo_form_Url',
									),
									'attributes' => array(
											'id' => 'pseo_url',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'pseo_url_redirect',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_seo_form_Url Redirect',
    									    'label_options' => array(
    									        'disable_html_escape' => true,
    									    ),
									),
									'attributes' => array(
											'id' => 'pseo_url_redirect',
											'value' => '',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'pseo_url_301',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_seo_form_Url 301',
    									    'label_options' => array(
    									        'disable_html_escape' => true,
    									    ),
									),
									'attributes' => array(
											'id' => 'pseo_url_301',
											'value' => '',
									),
								),
							),
						),
						'input_filter' => array( 
					        'pseo_meta_title' => array(
								'name'     => 'pseo_meta_title',
								'required' => false,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											'max'      => 65,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_title_long',
										    ),
										),
									),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ),
					        'pseo_meta_description' => array(
								'name'     => 'pseo_meta_description',
								'required' => false,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											'max'      => 255,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_desc_long',
										    ),
										),
									),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ),
					        'pseo_url' => array(
								'name'     => 'pseo_url',
								'required' => false,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											'max'      => 255,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
										    ),
										),
									),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ),
					        'pseo_url_redirect' => array(
								'name'     => 'pseo_url_redirect',
								'required' => false,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											'max'      => 255,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
										    ),
										),
									),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ),
					        'pseo_url_301' => array(
								'name'     => 'pseo_url_301',
								'required' => false,
								'validators' => array(
									array(
										'name'    => 'StringLength',
										'options' => array(
											'encoding' => 'UTF-8',
											'max'      => 255,
										    'messages' => array(
										        \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
										    ),
										),
									),
								),
								'filters'  => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim'),
								),
					        ),
						),
					
				),
			),
		),
	),
);