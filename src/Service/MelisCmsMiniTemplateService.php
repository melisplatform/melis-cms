<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisGeneralService;

class MelisCmsMiniTemplateService extends MelisGeneralService {
    public $file_types = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'];

    /**
     * @param $site_id
     * @param $name
     * @param $html
     * @param null $image_tmp_path
     * @param null $img_ext
     * @param null $cat_id
     * @return mixed
     */
    public function createMiniTemplate(
        $module,
        $name,
        $html,
        $image_tmp_path = null,
        $img_ext = null,
        $cat_id = null,
        $site_id = null
    ) {
        // params to array
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // event start
        $arrayParameters = $this->sendEvent(
            'meliscms_mini_template_service_create_template_start',
            $arrayParameters
        );

        // initialize variables
        $translator = $this->getServiceManager()->get('translator');
        $path = $this->getModuleMiniTemplatePath($module);
        $thumbnail = '';
        $success = 0;
        $errors = [];

        // check if the path has errors
        $this->checkCreateMiniTemplateErrors($path, $arrayParameters['name'], $module, $errors);

        // main process
        if (empty($errors)) {
            $path_without_extension = $path.'/'.$arrayParameters['name'];

            // create the view file
            $this->createPhtmlFile($path_without_extension.'.phtml', $arrayParameters['html']);

            // if there is a thumbnail, we copy it from the tmp path to the site mtpl path
            if (! empty($arrayParameters['image_tmp_path'])) {
                $this->copyThumbnail(
                    $arrayParameters['image_tmp_path'],
                    $path_without_extension.'.'.$arrayParameters['img_ext']
                );

                $thumbnail = $module.'/miniTemplatesTinyMce/'.$arrayParameters['name'].'.'. $arrayParameters['img_ext'];
            }

            // if category id is passed, we will link the template into a category
            if (! empty($cat_id))
                $this->saveMiniTemplateToCategory($cat_id, $site_id, $arrayParameters['name']);

            $success = 1;
        }

        // results
        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors,
            'data' => [
                'module' => $module,
                'template_name' => $arrayParameters['name'],
                'thumbnail' => $thumbnail,
            ]
        ];

        // event end
        $arrayParameters = $this->sendEvent(
            'meliscms_mini_template_service_create_template_end',
            $arrayParameters
        );

        return $arrayParameters['results'];
    }

    /**
     * @param $current_data
     * @param $new_data
     * @param bool $image
     * @return mixed
     */
    public function updateMiniTemplate($current_data, $new_data, $image = false) {
        // params to array
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // event start
        $arrayParameters = $this->sendEvent(
            'meliscms_mini_template_service_update_template_start',
            $arrayParameters
        );

        // initialize variables
        $current_data = $arrayParameters['current_data'];
        $new_data = $arrayParameters['new_data'];
        $image = $arrayParameters['image'] != 'false';

        $translator = $this->getServiceManager()->get('translator');
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');

        $current_module = $current_data['miniTemplateSiteModule'];
        $current_site_path = $this->getModuleMiniTemplatePath($current_module);
        $current_template_name = $current_data['miniTemplateName'];

        $new_site_module = $new_data['miniTemplateSiteModule'];
        $new_site_path = $this->getModuleMiniTemplatePath($new_site_module);

        $success = 1;
        $errors = [];

        // check if path has errors
        if (is_array($new_site_path)) {
            $errors[] = [
                'error' => $new_site_path['error'],
                'label' => $translator->translate('tr_meliscms_mini_template_error')
            ];
        }

        if (!is_writable($current_site_path . '/' . $current_template_name . '.phtml')) {
            $errors[] = [
                'error' => $translator->translate('tr_meliscms_mini_template_error_rights_phtml'),
                'label' => $translator->translate('tr_meliscms_mini_template_error')
            ];
        }

        if (empty($errors)) {
            if ($current_data['miniTemplateSiteModule'] != $new_data['miniTemplateSiteModule']) {
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template_name);
                // Move thumbnail to new site
                if (!empty($thumbnail_file))
                    rename($thumbnail_file['path'], $new_site_path . '/' . $thumbnail_file['file']);
                // Move template to new site
                rename(
                    $current_site_path . '/' . $current_template_name . '.phtml',
                    $new_site_path . '/' . $current_template_name . '.phtml'
                );
                // remove link to category if there is any
                $table->deleteByField('mtplct_template_name', $current_template_name);
                $current_site_path = $new_site_path;
                $current_module = $new_site_module;
            }

            if ($current_data['miniTemplateName'] !== $new_data['miniTemplateName']) {
                // Update the current template
                rename(
                    $current_site_path . '/' . $current_template_name . '.phtml',
                    $current_site_path . '/' . $new_data['miniTemplateName'] . '.phtml'
                );
                // update entry to category template table
                $table->update(
                    [
                        'mtplct_template_name' => $new_data['miniTemplateName']
                    ],
                    'mtplct_template_name',
                    $current_template_name
                );
                // If there is a thumbnail, rename it
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template_name);
                if (!empty($thumbnail_file)) {
                    $extension = explode('.', $thumbnail_file['file'])[1];
                    rename(
                        $thumbnail_file['path'],
                        $current_site_path . '/' . $new_data['miniTemplateName'] . '.' . $extension
                    );
                }
                $current_template_name = $new_data['miniTemplateName'];
            }

            // Update html
            $this->updatePhtmlFileContents($current_site_path . '/' . $current_template_name . '.phtml', $new_data['miniTemplateHtml']);

            // Update thumbnail
            $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template_name);
            if (! empty($new_data['miniTemplateThumbnail']['name'])) {
                if (!empty($thumbnail_file))
                    unlink($thumbnail_file['path']);

                $extension = explode('.', $new_data['miniTemplateThumbnail']['name'])[1];

                copy(
                    $new_data['miniTemplateThumbnail']['tmp_name'],
                    $current_site_path . '/' . $current_template_name . '.' . $extension
                );
            } else {
                if (! empty($image)) {
                    if (!empty($thumbnail_file['path'])) {
                        if (file_exists($thumbnail_file['path']))
                            unlink($thumbnail_file['path']);
                    }
                }
            }
        } else {
            $success = 0;
        }

        $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template_name);
        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors,
            'data' => [
                'module' => $current_module,
                'template_name' => $current_template_name,
                'thumbnail' => (! empty($thumbnail_file))
                    ? $current_module . '/miniTemplatesTinyMce/' . $thumbnail_file['file']
                    : '',
            ]
        ];

        // event end
        $arrayParameters = $this->sendEvent(
            'meliscms_mini_template_service_update_template_end',
            $arrayParameters
        );

        return $arrayParameters['results'];
    }

    /**
     * @param $mini_template_path
     * @param $mini_template_thumbnail_path
     * @param null $mini_template_name
     * @return mixed
     */
    public function deleteMiniTemplate($mini_template_path, $mini_template_thumbnail_path, $mini_template_name = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_delete_template_start', $arrayParameters);
        $errors = [];
        $success = 0;
        $translator = $this->getServiceManager()->get('translator');

        if (is_writable($arrayParameters['mini_template_path'])) {
            unlink($arrayParameters['mini_template_path']);

            if (! empty($arrayParameters['mini_template_thumbnail_path'])) {
                if (is_writable($arrayParameters['mini_template_thumbnail_path'])) {
                    unlink($arrayParameters['mini_template_thumbnail_path']);
                } else {
                    $errors[] = $translator->translate('tr_melis_cms_mini_template_manager_tool_delete_no_permission_image');
                }
            }

            if (! empty($arrayParameters['mini_template_name'])) {
                $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
                $table->deleteByField('mtplct_template_name', $arrayParameters['mini_template_name']);
            }

            $success = 1;
        } else {
            $errors[] = $translator->translate('tr_melis_cms_mini_template_manager_tool_delete_no_permission_mini_template');
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors
        ];
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_delete_template_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $params
     * @param null $cat_id
     * @return mixed
     */
    public function saveCategory($params, $cat_id = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_save_category_start', $arrayParameters);

        $params = $arrayParameters['params'];
        $cat_id = $arrayParameters['cat_id'];

        $saved_cat_id = 0;
        $connection = $this->startDbTransaction();
        $success = 0;
        $errors = [];
        $translator = $this->getServiceManager()->get('translator');
        $cms_langs = $this->getServiceManager()->get('MelisEngineLang')->getAvailableLanguages();
        $langs = [];

        foreach ($cms_langs as $lang) {
            $langs[$lang['lang_cms_id']] = $lang['lang_cms_name'];
        }

        $data = $params;
        unset($data['site_id']);
        unset($data['cat_id']);
        unset($data['status']);
        unset($data['currentLocale']);

        // check for trans data
        $counter = 0;
        foreach ($data as $key => $value) {
            if (! empty($value))
                $counter++;
        }
        // error for no category name provided
        if ($counter == 0) {
            foreach ($data as $key => $value) {
                $errors[$key] = [
                    'error' => $translator->translate('tr_meliscms_mini_template_error_category_atleast_one_provided'),
                    'label' => $translator->translate('tr_meliscms_mini_template_form_category_name')
                ];
            }
        }

        if (empty($errors)) {
            try {
                $category_table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTable');
                $category_site_table = $this->getServiceManager()->get('MelisCmsMiniTplSiteCategoryTable');
                $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTransTable');
                $user_id = $this->getCurrentUser()->usr_id;

                // save category
                $saved_cat_id = $category_table->save([
                    'mtplc_user_id' => $user_id,
                    'mtplc_creation_date' => date('Y-m-d h:i:s'),
                    'mtplc_status' => $params['status']
                ], $cat_id);

                // save category site
                $category_site_data = [
                    'mtplsc_site_id' => $params['site_id'],
                    'mtplsc_category_id' => $saved_cat_id
                ];
                if (empty($cat_id))
                    $category_site_data['mtplc_order'] = 0;
                $category_site_table->save($category_site_data, $cat_id);

                // save category trans
                foreach ($data as $key => $value) {
                    $exploded_text = explode('_', $key);
                    $lang_id = (int)$exploded_text[0];

                    if (! empty($value)) {
                        // we save the translation
                        if (empty($cat_id)) {
                            // update translation
                            $table->save([
                                'mtplc_id' => $saved_cat_id,
                                'mtplct_lang_id' => $lang_id,
                                'mtplct_name' => $value
                            ]);
                        } else {
                            $entry = $table->getTextByLang($cat_id, $lang_id)->toArray();
                            if (! empty($entry)) {
                                $table->save([
                                    'mtplct_name' => $value
                                ], $entry[0]['mtplct_id']);
                            } else {
                                $table->save([
                                    'mtplc_id' => $cat_id,
                                    'mtplct_lang_id' => $lang_id,
                                    'mtplct_name' => $value
                                ]);
                            }
                        }
                    } else {
                        // delete if empty
                        if (! empty($cat_id)) {
                            // check if it's in db
                            $entry = $table->getTextByLang($cat_id, $lang_id)->toArray();
                            if (! empty($entry)) {
                                $table->deleteById($entry[0]['mtplct_id']);
                            }
                        }
                    }
                }

                $success = 1;
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollback();
                $errors[] = $e->getMessage();
            }
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors,
            'id' => $cat_id ?? $saved_cat_id
        ];
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_save_category_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $cat_id
     * @return mixed
     */
    public function deleteCategory($cat_id) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_delete_category_start', $arrayParameters);

        $cat_id = $arrayParameters['cat_id'];
        $category_table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTable');
        $translation_table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTransTable');
        $category_site_table = $this->getServiceManager()->get('MelisCmsMiniTplSiteCategoryTable');
        $connection = $this->startDbTransaction();
        $errors = [];
        $success = 0;

        try {
            $category_table->deleteById($cat_id);
            $translation_table->deleteByField('mtplc_id', $cat_id);
            $category_site_table->deleteByField('mtplsc_category_id', $cat_id);
            $connection->commit();
            $success = 1;
        } catch (\Exception $e) {
            $connection->rollback();
            $errors[] = $e->getMessage();
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors,
            'id' => $cat_id
        ];
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_save_category_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $cat_id
     * @return array
     */
    public function getCategoryMiniTemplates($cat_id) {
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
        $cat_mini_templates = $table->getTemplatesByCategoryIds([$cat_id])->toArray();
        $site_category_table = $this->getServiceManager()->get('MelisCmsMiniTplSiteCategoryTable');
        $category_site = $site_category_table->getEntryByField('mtplsc_category_id', $cat_id)->current();
        $site_table = $this->getServiceManager()->get('MelisEngineTableSite');
        $site = $site_table->getEntryById($category_site->mtplsc_site_id)->current();
        $site_path = $this->getModuleMiniTemplatePath($site->site_name);
        $mini_templates = $this->getMiniTemplates($site->site_name);
        $templates = [];
        $final_mini_templates = [];
        $counter = 1;

        foreach ($cat_mini_templates as $cat_mini_template) {
            if (in_array($cat_mini_template['mtplct_template_name'], $mini_templates)) {
                $template = $this->getMiniTemplateFiles($site_path, $cat_mini_template['mtplct_template_name']);
                $thumbnail_file = '';

                if (!empty($template['image'])) {
                    $thumbnail_file = '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'];
                    $image = '<img data-image="test" src="' . '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'] . '?rand=' . uniqid('', true) . '" width=100 height=100>';
                } else {
                    $image = '<img data-image="test" src="/MelisFront/plugins/images/default.jpg" width=100 height=100>';
                }

                $exploded = explode('.', $template['template']['file']);
                $templateName = $exploded[0];
                $tag = '<p data-module="' . $site->site_name . '" class="mini-template-tool-table-path">' . explode('..', $template['template']['path'])[1] . '</p>';

                $final_mini_templates[] = [
                    'tempId' => (string)$counter,
                    'image' => $image,
                    'html_path' => $tag,
                    'DT_RowId' => $cat_mini_template['mtplct_template_name'],
                    'DT_RowAttr' => [
                        'templateName' => $cat_mini_template['mtplct_template_name'],
                        'thumbnail' => $thumbnail_file,
                        'module' => $site->site_name
                    ],
                    'DT_RowClass' => 'is-draggable'
                ];
                $counter++;
            }
        }

        return $final_mini_templates;
    }

    /**
     * @param $cat_id
     * @return mixed
     */
    public function getCategoryTexts($cat_id) {
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTransTable');
        $texts = $table->getEntryByField('mtplc_id', $cat_id)->toArray();

        return $texts;
    }

    /**
     * @param $siteId
     * @param $locale
     * @return array
     */
    public function getTree($siteId, $locale) {
        $table = $this->getServiceManager()->get('MelisEngineTableSite');
        $site = $table->getEntryById($siteId)->current();
        $module = $site->site_name ?? null;
        $site_path = $this->getModuleMiniTemplatePath($module);
        $tree = [];

        if (! is_array($site_path)) {
            $mini_templates = $this->getMiniTemplates($module);
            $categories = $this->getCategories($siteId, $locale);
            $category_ids = $this->getCategoryIds($categories);
            $category_mini_templates = $this->getDbMinitemplates($category_ids)->toArray();
            $root_mini_templates = $this->getDbMinitemplates([-1], $siteId)->toArray();
            $db_mini_templates = [];
            $categories_with_no_order = [];
            $final_items = [];

           
            // prepare mini templates
            foreach ($root_mini_templates as &$tpl) {
                $tpl['order'] = $tpl['mtplct_order'];
                $tpl['type'] = 'template';
            }
            // prepare categories
            foreach ($categories as $key => &$category) {
                $category['order'] = $category['mtplc_order'];
                $category['type'] = 'category';

                if ($category['order'] == 0) {
                    $categories_with_no_order[] = $category;
                    unset($categories[$key]);
                }
            };
            
            // get db mini template
            foreach ($category_mini_templates as $category_mini_template) {
                $db_mini_templates[] = $category_mini_template['mtplct_template_name'];
            }

            foreach ($root_mini_templates as $root_mini_template) {
                $db_mini_templates[] = $root_mini_template['mtplct_template_name'];
            }
           

            // sort db category and minitemplate with order
            $items = array_merge($root_mini_templates, $categories);
            array_multisort(array_column($items, 'order'), SORT_ASC, $items);

            // add category without order
            $cat_id = array_column($categories_with_no_order, 'mtplc_id');
            array_multisort($cat_id, SORT_ASC, $categories_with_no_order);
            foreach ($categories_with_no_order as $category_with_no_order) {
                $items[] = $category_with_no_order;
            }

            // add mini template not in database
            foreach ($mini_templates as $mini_template) {
                if (! in_array($mini_template, $db_mini_templates)) {
                    $items[] = $mini_template;
                }
            }
            
            // add category mini templates
            foreach ($items as $item) {
                $final_items[] = $item;
                if (is_array($item)) {
                    if ($item['type'] == 'category') {
                        $cat_mini_templates = [];
                        foreach ($category_mini_templates as $mini_template) {
                            if ($mini_template['mtplct_category_id'] == $item['mtplc_id']) {
                                $cat_mini_templates[] = $mini_template;
                            }
                        }

                        foreach ($cat_mini_templates as &$cat_mini_template) {
                            $cat_mini_template['type'] = 'template';
                            $cat_mini_template['category_id'] = $item['mtplc_id'] . '-' . $item['mtplct_name'];
                            $final_items[] = $cat_mini_template;
                        }
                    }
                }
            }

            // create the tree
            foreach ($final_items as $item) {
                if (is_array($item)) {
                    if ($item['type'] == 'category') {
                        $this->insertCategoryToTheTree($item, $tree);
                    } else {
                        if (in_array($item['mtplct_template_name'], $mini_templates)) {
                            $template = $this->getMiniTemplateFiles($site_path, $item['mtplct_template_name']);

                            if (!empty($template['image']['file']))
                                $image = '/' . $module . '/miniTemplatesTinyMce/' . $template['image']['file'];
                            else
                                $image = '/MelisFront/plugins/images/default.jpg';

                            if (!empty($item['category_id'])) {
                                $this->insertDbMiniTemplateToTheCategory($item['category_id'], $item, $module, $image, $tree);
                            } else {
                                $this->insertLocalMiniTemplateToTheTree($item['mtplct_template_name'], $module, $image, $tree, $item['site_label']);
                            }
                        }
                    }
                } else {
                    
                    $template = $this->getMiniTemplateFiles($site_path, $item);
                    

                    if (!empty($template['image']['file']))
                        $image = '/' . $module . '/miniTemplatesTinyMce/' . $template['image']['file'];
                    else
                        $image = '/MelisFront/plugins/images/default.jpg';

                    $this->insertLocalMiniTemplateToTheTree($item, $module, $image, $tree, $site->site_label);
                }
            }
        }

        return $tree;
    }

    /**
     * @param $site_id
     * @param $tree_data
     * @return array
     */
    public function saveTree($site_id, $tree_data) {
        $site_category_table = $this->getServiceManager()->get('MelisCmsMiniTplSiteCategoryTable');
        $category_template_table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
        $category_counter = 1;
        $last_category_flag = '';
        $mtpl_counter = 1;
        $errors = [];
        $success = 1;
        $mtpl_root_counter = 1;
        $root_counter = 1;

        $connection = $this->startDbTransaction();

        try {
            foreach ($tree_data as $node) {
                $type = $node['type'];

                if ($type == 'category') {
                    $cat_id = explode('-', $node['id'])[0];
                    $site_category_table->update(
                        [
                            'mtplc_order' => $root_counter
                        ],
                        'mtplsc_category_id',
                        $cat_id
                    );

                    $root_counter++;
                } else if ($type == 'mini-template') {
                    if ($node['parent'] != '#') {
                        if ($last_category_flag == $node['parent']) {
                            $mtpl_counter++;
                        } else {
                            $last_category_flag = $node['parent'];
                            $mtpl_counter = 1;
                        }
                    }

                    $template = $category_template_table->getTemplateBySiteId($site_id, $node['id'])->current();

                    if (!empty($template)) {
                        if ($node['parent'] != '#') {
                            $category_template_table->updateMiniTemplate(
                                [
                                    'mtplct_category_id' => explode('-', $node['parent'])[0],
                                    'mtplct_order' => $mtpl_counter
                                ],
                                $site_id,
                                $template->mtplct_template_name
                            );
                        } else {
                            $category_template_table->updateMiniTemplate(
                                [
                                    'mtplct_category_id' => -1,
                                    'mtplct_order' => $root_counter
                                ],
                                $site_id,
                                $template->mtplct_template_name
                            );

                            $root_counter++;
                        }
                    } else {
                        if ($node['parent'] != '#') {
                            $parent_id = explode('-', $node['parent'])[0];

                            $category_template_table->save([
                                'mtplct_site_id' => $site_id,
                                'mtplct_category_id' => $parent_id,
                                'mtplct_template_name' => $node['id'],
                                'mtplct_order' => $mtpl_counter
                            ]);
                        } else {
                            $parent_id = '-1';

                            $category_template_table->save([
                                'mtplct_site_id' => $site_id,
                                'mtplct_category_id' => $parent_id,
                                'mtplct_template_name' => $node['id'],
                                'mtplct_order' => $root_counter
                            ]);

                            $root_counter++;
                        }
                    }
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            $errors[] = $e->getMessage();
            $success = 0;
        }

        return [
            'errors' => $errors,
            'success' => $success
        ];
    }

    /**
     * @param $module
     * @return array
     */
    public function getMiniTemplates($module) {
        $path = $this->getModuleMiniTemplatePath($module);
        $templates = [];

        if (! is_array($path)) {
            $files = array_diff(scandir($path), ['..', '.']);

            foreach ($files as $file) {
                $exploded = explode('.', $file);
                $templateName = $exploded[0];

                if (! empty($exploded[1])) {
                    $extension = $exploded[1];

                    if (in_array($extension, ['phtml'])) {
                        array_push($templates, $templateName);
                    }
                }
            }
        }

        return $templates;
    }

    /**
     * @param $site_id
     * @param $locale
     * @return array
     */
    public function getCategories($site_id, $locale) {
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTable');
        $lang_table = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        $lang = $lang_table->getEntryByField('lang_cms_locale', $locale)->current();
        $categories = $table->getCategoryBySite($site_id)->toArray();
        $final_categories = [];
        $cat_ids = [];

        foreach ($categories as $category) {
            if (! in_array($category['mtplc_id'], $cat_ids) && $category['mtplct_lang_id'] == $lang->lang_cms_id) {
                $cat_ids[] = $category['mtplc_id'];
            }
        }

        foreach ($categories as $category) {
            if ($category['mtplct_lang_id'] == $lang->lang_cms_id) {
                $final_categories[] = $category;
            } else {
                if (! in_array($category['mtplc_id'], $cat_ids)) {
                    $category_lang = $lang_table->getEntryById($category['mtplct_lang_id'])->current();
                    $category['mtplct_name'] .= ' (' . $category_lang->lang_cms_name . ')';
                    $final_categories[] = $category;
                    $cat_ids[] = $category['mtplc_id'];
                }
            }
        }

        return $final_categories;
    }

    /**
     * @param $cat_ids
     * @param null $siteId
     * @return mixed
     */
    public function getDbMinitemplates($cat_ids, $siteId = null) {
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
        return $table->getTemplatesByCategoryIds($cat_ids, $siteId);
    }

    /**
     * @param $categories
     * @return array
     */
    public function getCategoryIds($categories) {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category['mtplc_id'];
        }
        return $ids;
    }

    /**
     * @param $path
     * @param $template
     * @return array
     */
    public function getMiniTemplateFiles($path, $template) {
        $files = glob($path ."/" . $template . ".*");
        $data = [];

        foreach ($files as $file) {
            $base_name = basename($file);
            $exploded_name = explode('.', $base_name);

            if (in_array($exploded_name[1], $this->file_types)) {
                $data['image']['file'] = $base_name;
                $data['image']['path'] = $file;
            } else {
                $data['template']['file'] = $base_name;
                $data['template']['path'] = $file;
            }
        }

        return $data;
    }

    /**
     * @param $path
     * @param $template_name
     * @param $site_module
     * @param $errors
     */
    private function checkCreateMiniTemplateErrors($path, $template_name, $site_module, &$errors) {
        $translator = $this->getServiceManager()->get('translator');

        // check if path has errors
        if (is_array($path)) {
            $errors[] = [
                'error' => $path['error'],
                'label' => $translator->translate('tr_meliscms_mini_template_error')
            ];
        }

        // check if file is already existing
        if (file_exists($path . '/' . $template_name . '.phtml')) {
            $errors['miniTemplateName'] = [
                'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_file_already_exists'),
                'label' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_name')
            ];
        }
    }

    /**
     * @param $category
     * @param $tree
     */
    private function insertCategoryToTheTree($category, &$tree) {
        $tree[] = [
            'id' => $category['mtplc_id'] . '-' . $category['mtplct_name'],
            'parent' => '#',
            'text' => htmlspecialchars($category['mtplct_name']),
            'icon' => 'fa fa-circle ' . ($category['mtplc_status'] ? 'text-success' : 'text-danger'),
            'type' => 'category',
            'status' => $category['mtplc_status'],
            'site_name' => $category['site_label'],
            'unique_text' => $category['site_label']. "-" . $category['mtplct_name'],
            'categoryId' => $category['mtplc_id']
        ];
    }

    /**
     * @param $category_id
     * @param $mini_template
     * @param $site_module
     * @param $image
     * @param $tree
     */
    private function insertDbMiniTemplateToTheCategory($category_id, $mini_template, $site_module, $image, &$tree) {
        $tree[] = [
            'id' => $mini_template['mtplct_template_name'],
            'parent' => $category_id,
            'text' => $mini_template['mtplct_template_name'],
            'icon' => 'fa fa-plug text-success',
            'type' => 'mini-template',
            'module' => $site_module,
            'site_name' => $mini_template['site_label'],
            'unique_text' => $mini_template['site_label']. "-" . $mini_template['mtplct_template_name'],
            'imgSource' => $image
        ];
    }

    /**
     * @param $mini_template_name
     * @param $site_module
     * @param $image
     * @param $tree
     */
    private function insertLocalMiniTemplateToTheTree($mini_template_name, $site_module, $image, &$tree, $siteName = null) {
        $tree[] = [
            'id' => $mini_template_name,
            'parent' => '#',
            'text' => $mini_template_name,
            'icon' => 'fa fa-plug text-success',
            'type' => 'mini-template',
            'module' => $site_module,
            'site_name' => $siteName,
            'unique_text' => $siteName . "-" . $mini_template_name,
            'imgSource' => $image
        ];
    }

    /**
     * @param $path
     * @param $template
     * @return array|null
     */
    public function getMiniTemplateThumbnail($path, $template)
    {
        $files = glob($path ."/" . $template . ".*");

        foreach ($files as $file) {
            $base_name = basename($file);
            $exploded_name = explode('.', $base_name);

            if (in_array($exploded_name[1], $this->file_types)) {
                return [
                    'file' => $base_name,
                    'path' => $file
                ];
            }
        }

        return null;
    }

    public function removePluginFromCategory($siteId, $template) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_remove_plugin_from_category_start', $arrayParameters);
        $success = 0;
        $errors = [];
        $connection = $this->startDbTransaction();

        try {
            $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
            $table->deletePluginFromCategory($siteId, $template);
            $connection->commit();
            $success = 1;
        } catch(\Exception $ex) {
            $connection->rollback();
            $errors[] = $ex->getMessage();
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors
        ];
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_remove_plugin_from_category_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $module
     * @return string|null
     */
    public function getModuleMiniTemplatePath($module) {
        $path = $this->getComposerModulePath($module, $ecode);
        if ($path == 'e2' || $path == null)
            $path = $this->getNonComposerModulePath($module, $ecode);

        if ($path == null)
            $error = $this->getErrorMessage('e1');
        else
            $error = $this->getErrorMessage($path);

        if (empty($error))
            return $path;
        else
            return $error;
    }

    /**
     * @param $module
     * @return string
     */
    private function getComposerModulePath($module, &$ecode) {
        $composerSrv = $this->getServiceManager()->get('MelisEngineComposer');
        $path = $composerSrv->getComposerModulePath($module);
        if (empty($path))
            return 'e2';

        $publicPath = $path . '/public';
        if (! file_exists($publicPath))
            return 'e3';
        if (! is_writable($publicPath))
            return 'e14';

        $mtplPath = $publicPath . '/miniTemplatesTinyMce';
        if (! file_exists($path . '/public' . '/miniTemplatesTinyMce')) {
            if (! mkdir($mtplPath, 0777, true))
                return 'e4';
        }

        if (! is_writable($mtplPath))
            return 'e5';

        return $mtplPath ?? null;
    }

    /**
     * @param $module
     * @return string
     */
    private function getNonComposerModulePath($module, &$ecode) {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        if (! file_exists($path)) {
            if (! is_dir($path))
                return 'e6';
            return 'e7';
        }

        $sites = $this->getDir($path);
        if (empty($sites))
            return 'e8';
        if (! in_array($module, $sites))
            return 'e9';

        $publicPath = $path . '/' . $module . '/public';
        if (! file_exists($publicPath))
            return 'e10';
        if (! is_writable($publicPath))
            return 'e13';

        $mtplPath = $publicPath . '/miniTemplatesTinyMce';
        if (! file_exists($mtplPath)) {
            if (! mkdir($mtplPath, 0777, true))
                return 'e11';
        }

        if (! is_writable($mtplPath))
            return 'e12';

        return $mtplPath ?? null;
    }

    /**
     * @return mixed
     */
    private function getCurrentUser() {
        $auth = $this->getServiceManager()->get('MelisCoreAuth');
        return $auth->getStorage()->read();
    }

    /**
     * @return mixed
     */
    private function startDbTransaction() {
        $db = $this->getServiceManager()->get('Zend\Db\Adapter\Adapter');
        $connection = $db->getDriver()->getConnection();
        $connection->beginTransaction();
        return $connection;
    }

    /**
     * @param $dir
     * @param array $excludeSubFolders
     * @return array
     */
    private function getDir($dir, $excludeSubFolders = [])
    {
        $directories = [];
        if (file_exists($dir)) {
            $excludeDir = array_merge(['.', '..', '.gitignore'], $excludeSubFolders);
            $directory  = array_diff(scandir($dir), $excludeDir);

            foreach ($directory as $d) {
                if (is_dir($dir.'/'.$d)) {
                    $directories[] = $d;
                }
            }

        }
        return $directories;
    }

    private function getErrorMessage($code) {
        $error = '';
        $missingModuleOrPathFilesErrors = ['e1', 'e2', 'e3', 'e6', 'e7', 'e8', 'e9', 'e10'];
        $noPublicDirectoryRights = ['e4', 'e11', 'e13', 'e14'];

        if (in_array($code, $missingModuleOrPathFilesErrors))
            $error = 'tr_meliscms_mini_template_error_module_or_public_does_not_exist';
        if (in_array($code, $noPublicDirectoryRights))
            $error = 'tr_meliscms_mini_template_error_rights_mtpl_directory';
        if ($code == 'e5' || $code == 'e12')
            $error = 'tr_meliscms_mini_template_error_rights_phtml';

        if (empty($error))
            return null;

        return [
            'success' => 0,
            'error' => $this->getServiceManager()->get('translator')->translate($error)
        ];
    }

    private function createPhtmlFile($filename, $html) {
        $file = fopen($filename, 'wb');
        fwrite($file, $html);
        fclose($file);
        chmod($filename, 0777);
    }

    private function updatePhtmlFileContents($filename, $html) {
        $file = fopen($filename, 'w');
        fwrite($file, $html);
        fclose($file);
    }

    private function copyThumbnail($img_tmp_path, $filename) {
        copy(
            $img_tmp_path,
            $filename
        );
    }

    private function saveMiniTemplateToCategory($category_id, $site_id, $name) {
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
        $lastOrder = $table->getLatestOrder($category_id)->current();

        if (! empty($lastOrder))
            $lastOrder = (int)$lastOrder->mtplct_order + 1;
        else
            $lastOrder = 1;

        $table->save(
            [
                'mtplct_site_id' => $site_id,
                'mtplct_category_id' => $category_id,
                'mtplct_template_name' => $name,
                'mtplct_order' => $lastOrder
            ]
        );
    }
}
