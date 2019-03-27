<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'tools' => array(
                'site_translation_tool' => array(
                    'conf' => array(
                        'title' => 'Melis Site Translation',
                        'id' => 'id_melis_site_translation',
                    ),
                    'table' => array(
                        // table ID
                        'target' => '#tableMelisSiteTranslation',
                        'ajaxUrl' => '/melis/MelisCms/SitesTranslation/getTranslation',
                        'dataFunction' => 'initAdditionalTransParam',
                        'ajaxCallback' => 'initSiteTranslationTable()',
                        'filters' => array(
                            'left' => array(
                                'mt-tr-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SitesTranslation',
                                    'action' => 'render-tool-sites-site-translation-content-filters-limit'
                                ),
                                'mt-tr-languages' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SitesTranslation',
                                    'action' => 'render-tool-sites-site-translation-filters-languages'
                                )
                            ),
                            'center' => array(
                                'mt-tr-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SitesTranslation',
                                    'action' => 'render-tool-sites-site-translation-content-filters-search'
                                ),
                            ),
                            'right' => array(
                                'mt-tr-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SitesTranslation',
                                    'action' => 'render-tool-sites-site-translation-content-filters-refresh'
                                ),
                            ),
                        ),
                        'columns' => array(
                            'mst_key' => array(
                                'text' => 'tr_melis_site_translation_key_col',
                                'sortable' => false,
                            ),
                            'module' => array(
                                'text' => 'tr_melis_site_translation_module_col',
                                'sortable' => false,
                            ),
                            'mstt_text' => array(
                                'text' => 'tr_melis_site_translation_text_col',
                                'sortable' => false,
                            )
                        ),

                        // define what columns can be used in searching
                        'searchables' => array('mst_key', 'module', 'mstt_text'),
                        'actionButtons' => array(
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesTranslation',
                                'action' => 'render-tool-sites-site-translation-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesTranslation',
                                'action' => 'render-tool-sites-site-translation-action-delete',
                            ),
                        )
                    ),
                    'modals' => array(

                    ),
                    'forms' => array(
                        'sitestranslation_form' => array(
                            'attributes' => array(
                                'name' => 'sitestranslationform',
                                'id' => 'sites-translation-form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'mst_id',
                                        'type' => 'hidden',
                                        'attributes' => array(
                                            'id' => 'mst_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'mstt_id',
                                        'type' => 'hidden',
                                        'attributes' => array(
                                            'id' => 'mstt_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'mst_site_id',
                                        'type' => 'hidden',
                                        'attributes' => array(
                                            'id' => 'mst_site_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'mstt_lang_id',
                                        'type' => 'hidden',
                                        'attributes' => array(
                                            'id' => 'mstt_lang_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'mst_key',
                                        'type' => 'hidden',
                                        'attributes' => array(
                                            'id' => 'mst_key',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'mstt_text',
                                        'type' => 'textarea',
                                        'options' => array(
                                            'label' => 'tr_melis_site_translation_text',
                                            'tooltip' => 'tr_melis_site_translation_text_tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'mstt_text',
                                            'value' => '',
                                            'required' => 'required',
                                            'class' => 'form-control tiny-mce-init',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                'mstt_text' => array(
                                    'name'     => 'mstt_text',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_melis_site_translation_empty_text',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
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