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
					'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
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
										'tooltip' => 'tr_meliscms_page_tab_properties_form_Name tooltip',
									),
									'attributes' => array(
										'id' => 'id_page_name',
										'value' => '',
									    'placeholder' => 'tr_meliscms_page_tab_properties_form_Name',
									    'required' => 'required',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_type',
									'type' => 'Laminas\Form\Element\Select',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Type',
										'tooltip' => 'tr_meliscms_page_tab_properties_form_Type tooltip',
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
									    'required' => 'required',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'plang_lang_id',
									'type' => 'MelisCmsLanguageSelect',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Language',
										'tooltip' => 'tr_meliscms_page_tab_properties_form_Language2 tooltip',
										'empty_option' => 'tr_meliscms_form_common_Choose',
									    'disable_inarray_validator' => true,
									),
									'attributes' => array( 
										'id' => 'id_plang_lang_id',
									    'required' => 'required',
									),
								),
							),
					/*		array(
								'spec' => array(
									'name' => 'page_status',
									'type' => 'Laminas\Form\Element\Select',
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
									'type' => 'Laminas\Form\Element\Select',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Show Menu',
										'tooltip' => 'tr_meliscms_page_tab_properties_form_Show Menu tooltip',
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
									    'required' => 'required',
									),
								),
							),
							array(
								'spec' => array(
									'name' => 'page_tpl_id',
									'type' => 'MelisCmsTemplateSelect',
									'options' => array(
										'label' => 'tr_meliscms_page_tab_properties_form_Template',
										'tooltip' => 'tr_meliscms_page_tab_properties_form_Template tooltip',
										'empty_option' => 'tr_meliscms_form_common_Choose',
									    'disable_inarray_validator' => true,
									),
									'attributes' => array(
										'id' => 'id_page_tpl_id',
									    'required' => 'required',
									),
								),
							),
                            array(
                                'spec' => array(
                                    'name' => 'style_id',
                                    'type' => 'MelisCmsStyleSelect',
                                    'options' => array(
                                        'label' => 'tr_meliscms_tool_style_name_properties',
                                        'tooltip' => 'tr_meliscms_tool_style_name_properties tooltip',
                                        'empty_option' => 'tr_meliscms_form_common_Choose',
                                        'disable_inarray_validator' => true,
                                    ),
                                    'attributes' => array(
                                        'id' => 'id_page_style_id',
                                    ),
                                ),
                            ),
							array(
								'spec' => array(
									'name' => 'page_creation_date',
									'type' => 'MelisText',
									'options' => array(
											'label' => 'tr_meliscms_page_tab_properties_form_Creation date',
											'tooltip' => 'tr_meliscms_page_tab_properties_form_Creation date tooltip',
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
    					            	'tooltip' => 'tr_meliscms_page_tab_properties_form_taxonomy_tooltip',
    					            ),
    					            'attributes' => array(
    					                'id' => 'id_page_taxonomy',
    					                'value' => '',
    					                'data-label-text' => 'tr_meliscms_page_tab_properties_form_taxonomy',
    					                'placeholder' => 'tr_meliscms_page_tab_properties_form_taxonomy_placeholder',
    					            ),
    					        ),
    					    ),
                            array(
                                'spec' => array(
                                    'name' => 'page_search_type',
//                                    'type' => 'Laminas\Form\Element\Select',
                                    'type' => 'MelisCmsStyleSelect',
                                    'options' => array(
                                        'label' => 'tr_meliscms_page_tab_properties_search_type',
                                        'tooltip' => 'tr_meliscms_page_tab_properties_search_type tooltip',
                                        'value_options' => array(
                                            'tr_meliscms_page_tab_properties_search_type_option1' => 'tr_meliscms_page_tab_properties_search_type_option1',
                                            'tr_meliscms_page_tab_properties_search_type_option2' => 'tr_meliscms_page_tab_properties_search_type_option2',
                                            'tr_meliscms_page_tab_properties_search_type_option3' => 'tr_meliscms_page_tab_properties_search_type_option3',
                                        ),
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
										        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_page_form_page_name_long',
										    ),
										),
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_name_empty',
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
										        \Laminas\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_page_form_page_type_invalid',
										    ),
										)
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_type_empty',
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
					                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_plang_lang_id_empty',
					                        ),
					                    ),
					                ),
					            ),
					            'filters' => array(
					            ),
							),
					        'page_menu' => array(
								'name'     => 'page_menu',
								'required' => true,
								'validators' => array( 
									array(
										'name'    => 'InArray',
										'options' => array(
											'haystack' => array('LINK', 'NOLINK', 'NONE'),
										    'messages' => array(
										        \Laminas\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_page_form_page_menu_invalid',
										    ),
										)
									),
								    array(
								        'name' => 'NotEmpty',
								        'options' => array(
								            'messages' => array(
								                \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_menu_empty',
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
								                \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_page_tpl_id_empty',
								            ),
								        ),
								    ),
								),
								'filters'  => array(
								),
					        ),
						    'style_id' => array(
						        'name'     => 'style_id',
						        'required' => false,
						        'validators' => array(),
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
					'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
					'elements' => array(  
						array(
							'spec' => array(
								'name' => 'pseo_meta_title',
								'type' => 'MelisText',
								'options' => array(
										'label' => 'tr_meliscms_page_tab_seo_form_Meta Title',
										'tooltip' => 'tr_meliscms_page_tab_seo_form_Meta Title tooltip',
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
										'tooltip' => 'tr_meliscms_page_tab_seo_form_Meta Description tooltip',
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
										'tooltip' => 'tr_meliscms_page_tab_seo_form_Url tooltip',
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
										'tooltip' => 'tr_meliscms_page_tab_seo_form_Url Redirect tooltip',
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
										'tooltip' => 'tr_meliscms_page_tab_seo_form_Url 301 tooltip',
								),
								'attributes' => array(
										'id' => 'pseo_url_301',
										'value' => '',
								),
							),
						),
                        array(
                            'spec' => array(
                                'name' => 'pseo_canonical',
                                'type' => 'MelisText',
                                'options' => array(
                                    'label' => 'tr_meliscms_page_tab_seo_form_canonical',
                                    'tooltip' => 'tr_meliscms_page_tab_seo_form_canonical tooltip',
                                ),
                                'attributes' => array(
                                    'id' => 'pseo_canonical',
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
									    'messages' => array(
									        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_title_long',
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
									    'messages' => array(
									        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_desc_long',
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
									        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
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
									        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
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
									        \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_pageseo_form_page_url_too_long',
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
			    'meliscms_page_languages' => array(
			        'attributes' => array(
			            'name' => 'pageLangCreateForm',
			            'id' => 'pageLangCreateForm',
			            'method' => 'POST',
			            'action' => '',
			        ),
			        'hydrator'  => 'Laminas\Stdlib\Hydrator\ArraySerializable',
			        'elements' => array(
			            array(
			                'spec' => array(
			                    'name' => 'pageLangPageId',
			                    'type' => 'hidden',
			                ),
			            ),
			            array(
			                'spec' => array(
			                    'name' => 'pageLangLocale',
			                    'type' => 'MelisCmsPageLanguagesSelect',
			                    'options' => array(
			                        'label' => 'tr_meliscms_page_lang_language_field',
			                        'tooltip' => 'tr_meliscms_page_lang_language_field tooltip',
			                        'empty_option' => 'tr_meliscms_page_lang_choose_opt',
			                        'disable_inarray_validator' => true,
			                    ),
			                    'attributes' => array(
			                        'id' => 'pageLangLocale',
			                        'value' => '',
			                    ),
			                ),
			            ),
			        ),
			        'input_filter' => array(
			            'pageLangLocale' => array(
			                'name'     => 'pageLangLocale',
			                'required' => true,
			                'validators' => array(
			                    array(
			                        'name' => 'NotEmpty',
			                        'options' => array(
			                            'messages' => array(
			                                \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_lang_no_lang_selected',
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
