<?php
return array(
	'plugins' => array(
		'microservice' => array(
			'MelisCms' => array(
				//MelisCmsPageGetterService.php
				'MelisCmsPageGetterService' => array(
					/**
					 * @param $pageId
					 * @method getPageContent()
					 */
					'getPageContent' => array(
						'attributes' => array(
							'name'	=> 'microservice_form',
							'id'	=> 'microservice_form',
							'method'=> 'POST',
							'action'=> $_SERVER['REQUEST_URI'],
						),
						'hydrator' => 'Zend\Stdlib\Hydrator\ArraySerializable',
						'elements' => array(
							array(
								'spec' => array(
									'name' => 'pageId',
									'type' => 'Text',
									'options' => array(
										'label' => 'Page Id',
									),
									'attributes' => array(
										'id' => 'pageId',
										'value' => '',
										'class' => '',
										'placeholder' => 'Enter Page Id',
									),
								),
							),
						),
						'input_filter' => array(
							'pageId' => array(
								'name' => 'pageId',
								'required' => true,
								'validators' => array(
									array(
										'name' => 'IsInt',
										'options' => array(
											'message' => array(
												\Zend\I18n\Validator\IsInt::INVALID => 'PageId must be an integer'
											),
										),
									),
								),
								'filters' => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim')
								),
							),
						),
					),
				),
				'MelisCmsSiteService' => array(
					/**
					 * @param $siteId
					 * 
					 * @method getSitePages()
					 */
					'getSitePages' => array(
						'attributes' => array(
							'name'	=> 'microservice_form',
							'id'	=> 'microservice_form',
							'method'=> 'POST',
							'action'=> $_SERVER['REQUEST_URI'],
						),
						'hydrator' => 'Zend\Stdlib\Hydrator\ArraySerializable',
						'elements' => array(
							array(
								'spec' => array(
									'name' => 'siteId',
									'type' => 'Text',
									'options' => array(
										'label' => 'siteId',
									),
									'attributes' => array(
										'id' => 'siteId',
										'value' => '',
										'class' => '',
										'placeholder' => 'Enter Site Id',
									),
								),
							),
						),
						'input_filter' => array(
							'siteId' => array(
								'name' => 'siteId',
								'required' => true,
								'validators' => array(
									array(
										'name' => 'IsInt',
										'option' => array(
											'message' => array(
												\Zend\I18n\Validator\IsInt::INVALID => 'siteId must be an integer'
											),
										),
									),
								),
								'filters' => array(
									array('name' => 'StripTags'),
									array('name' => 'StringTrim')
								),
							),
						),
					),
				),
			),
		),
	),
);