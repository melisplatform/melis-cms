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
    public function createMiniTemplate($site_id, $name, $html, $image_tmp_path = null, $img_ext = null, $cat_id = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_create_template_start', $arrayParameters);

        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $module = $siteTable->getEntryById($arrayParameters['site_id'])->current()->site_name;
        $translator = $this->getServiceManager()->get('translator');
        $path = $this->getModuleMiniTemplatePath($module);

        $success = 0;
        $errors = [];

        $this->checkCreateMiniTemplateErrors($path, $arrayParameters['name'], $module, $errors);

        if (empty($errors)) {
            // create the file
            $miniTemplateFile = fopen($path . '/' . $arrayParameters['name'] . '.phtml', 'wb');
            // add the html
            fwrite($miniTemplateFile, $arrayParameters['html']);
            fclose($miniTemplateFile);
            chmod($path . '/' . $arrayParameters['name'] . '.phtml', 0777);
            // copy the thumbnail
            if (!empty($arrayParameters['image_tmp_path'])) {
                copy(
                    $arrayParameters['image_tmp_path'],
                    $path . '/' . $arrayParameters['name'] . '.' . $arrayParameters['img_ext']
                );
            }

            if (! empty($cat_id)) {
                $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
                $lastOrder = $table->getLatestOrder($cat_id)->current();

                if (! empty($lastOrder))
                    $lastOrder = (int)$lastOrder->mtplct_order + 1;
                else
                    $lastOrder = 1;

                $table->save(
                    [
                        'mtplct_site_id' => $arrayParameters['site_id'],
                        'mtplct_category_id' => $cat_id,
                        'mtplct_template_name' => $arrayParameters['name'],
                        'mtplct_order' => $lastOrder
                    ]
                );
            }

            $success = 1;
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors,
            'data' => [
                'module' => $module,
                'template_name' => $arrayParameters['name'],
                'thumbnail' => (! empty($arrayParameters['image_tmp_path']))
                    ? $module . '/miniTemplatesTinyMce/' . $arrayParameters['name'] . '.' . $arrayParameters['img_ext']
                    : '',
            ]
        ];
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_create_template_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $current_data
     * @param $new_data
     * @param bool $image
     * @return mixed
     */
    public function updateMiniTemplate($current_data, $new_data, $image = false) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_update_template_start', $arrayParameters);

        $current_data = $arrayParameters['current_data'];
        $new_data = $arrayParameters['new_data'];
        $image = $arrayParameters['image'] != 'false';

        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $current_site = $siteTable->getEntryById($current_data['miniTemplateSite'])->current();
        $current_module = $current_site->site_name;
        $current_site_path = $this->getModuleMiniTemplatePath($current_site->site_name);
        $new_site = $siteTable->getEntryById($new_data['miniTemplateSite'])->current();
        $new_site_path = $this->getModuleMiniTemplatePath($new_site->site_name);
        $current_template_name = $current_data['miniTemplateName'];
        $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
        $success = 1;
        $errors = [];

        if (!is_writable($current_site_path . '/' . $current_data['miniTemplateName'] . '.phtml')) {
            $translator = $this->getServiceManager()->get('translator');
            $errors[] = [
                'error' => $current_site_path . '/' . $current_data . '.phtml ' . $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_path_not_writable'),
                'label' => $translator->translate('tr_meliscms_mini_template_error')
            ];
        }

        if (empty($errors)) {
            try {
                if ($current_data['miniTemplateSite'] !== $new_data['miniTemplateSite']) {
                    $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template_name);
                    // Move thumbnail to new site
                    if (!empty($thumbnail_file)) {
                        rename($thumbnail_file['path'], $new_site_path . '/' . $thumbnail_file['file']);
                    }
                    // Move template to new site
                    rename(
                        $current_site_path . '/' . $current_template_name . '.phtml',
                        $new_site_path . '/' . $current_template_name . '.phtml'
                    );
                    // remove link to category if there is any
                    $table->deleteByField('mtplct_template_name', $current_template_name);
                    $current_site_path = $new_site_path;
                    $current_module = $new_site->site_name;
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
                $file = fopen($current_site_path . '/' . $current_template_name . '.phtml', 'w');
                fwrite($file, $new_data['miniTemplateHtml']);
                fclose($file);

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
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                $success = 0;
            }
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

        $arrayParameters = $this->sendEvent('meliscms_mini_template_service_update_template_end', $arrayParameters);
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

        if (is_writable($arrayParameters['mini_template_path'])) {
            unlink($arrayParameters['mini_template_path']);

            if (! empty($arrayParameters['mini_template_thumbnail_path'])) {
                if (is_writable($arrayParameters['mini_template_thumbnail_path'])) {
                    unlink($arrayParameters['mini_template_thumbnail_path']);
                } else {
                    $errors[] = 'No permission to delete image. No file/s deleted';
                }
            }

            if (! empty($arrayParameters['mini_template_name'])) {
                $table = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTemplateTable');
                $table->deleteByField('mtplct_template_name', $arrayParameters['mini_template_name']);
            }

            $success = 1;
        } else {
            $errors[] = 'No permission to delete minitemplate. No file/s deleted';
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

        $data = $params;
        unset($data['site_id']);
        unset($data['cat_id']);
        unset($data['status']);

        // check for trans data
        $counter = 0;
        foreach ($data as $key => $value) {
            if (! empty($value))
                $counter++;
        }
        // error for no category name provided
        if ($counter == 0) {
            $translator = $this->getServiceManager()->get('translator');
            foreach ($data as $key => $value) {
                $errors[$key] = [
                    'error' => $translator->translate('tr_meliscms_mini_template_error_category_atleast_one_provided'),
                    'label' => $translator->translate('tr_meliscms_mini_template_form_category_name')
                ];
                break;
            }
        }

        if (empty($errors)) {
            try {
                $category_table = $this->getServiceManager()->get('MelisCmsCategoryTable');
                $category_site_table = $this->getServiceManager()->get('MelisCmsMiniTplSiteCategoryTable');
                $table = $this->getServiceManager()->get('MelisCmsCategoryTransTable');
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

                    if (!empty($value)) {
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
        $category_table = $this->getServiceManager()->get('MelisCmsCategoryTable');
        $translation_table = $this->getServiceManager()->get('MelisCmsCategoryTransTable');
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
                    $image = '<img data-image="test" src="' . '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'] . '" width=100 height=100>';
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
                        'imgSource' => $thumbnail_file,
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
        $table = $this->getServiceManager()->get('MelisCmsCategoryTransTable');
        $texts = $table->getEntryByField('mtplc_id', $cat_id)->toArray();

        return $texts;
    }

    /**
     * @param $module
     * @return string|null
     */
    public function getModuleMiniTemplatePath($module) {
        $path = $this->getComposerModulePath($module);
        if (empty($path))
            $path = $this->getNonComposerModulePath($module);
        return $path ?? null;
    }

    /**
     * @param $siteId
     * @param $locale
     * @return array
     */
    public function getTree($siteId, $locale) {
        $table = $this->getServiceManager()->get('MelisEngineTableSite');
        $site = $table->getEntryById($siteId)->current();
        $module = $site->site_name;
        $site_path = $this->getModuleMiniTemplatePath($module);
        $tree = [];

        if (file_exists($site_path)) {
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
                                $this->insertLocalMiniTemplateToTheTree($item['mtplct_template_name'], $module, $image, $tree);
                            }
                        }
                    }
                } else {
                    $template = $this->getMiniTemplateFiles($site_path, $item);

                    if (!empty($template['image']['file']))
                        $image = '/' . $module . '/miniTemplatesTinyMce/' . $template['image']['file'];
                    else
                        $image = '/MelisFront/plugins/images/default.jpg';

                    $this->insertLocalMiniTemplateToTheTree($item, $module, $image, $tree);
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

        if (!file_exists($path)) {
            if (is_writable($path)) {
                mkdir($path);
            }
        } else {
            $files = array_diff(scandir($path), ['..', '.']);

            foreach ($files as $file) {
                $exploded = explode('.', $file);
                $templateName = $exploded[0];
                $extension = $exploded[1];

                if (in_array($extension, ['phtml'])) {
                    array_push($templates, $templateName);
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
        $table = $this->getServiceManager()->get('MelisCmsCategoryTable');
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

        if (! empty($path)) {
            if (file_exists($path)) {
                if (!file_exists($path . '/' . $template_name . '.phtml')) {
                    if (!is_writable($path)) {
                        $errors[] = [
                            'error' => $translator->translate('tr_meliscms_mini_template_error_minitemplate_directory_not_writable'),
                            'label' => $translator->translate('tr_meliscms_mini_template_error')
                        ];
                    }
                } else {
                    $errors['miniTemplateName'] = [
                        'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_file_already_exists'),
                        'label' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_name')
                    ];
                }
            } else {
                $errors[] = [
                    'error' => $translator->translate('tr_meliscms_mini_template_error_minitemplate_directory_does_not_exist'),
                    'label' => $translator->translate('tr_meliscms_mini_template_error')
                ];
            }
        } else {
            $errors[] = [
                'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_no_site_path_found') . $site_module,
                'label' => $translator->translate('tr_meliscms_mini_template_error')
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
            'text' => $category['mtplct_name'],
            'icon' => 'fa fa-circle ' . ($category['mtplc_status'] ? 'text-success' : 'text-danger'),
            'type' => 'category',
            'status' => $category['mtplc_status'],
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
            'imgSource' => $image
        ];
    }

    /**
     * @param $mini_template_name
     * @param $site_module
     * @param $image
     * @param $tree
     */
    private function insertLocalMiniTemplateToTheTree($mini_template_name, $site_module, $image, &$tree) {
        $tree[] = [
            'id' => $mini_template_name,
            'parent' => '#',
            'text' => $mini_template_name,
            'icon' => 'fa fa-plug text-success',
            'type' => 'mini-template',
            'module' => $site_module,
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

    /**
     * @param $module
     * @return string
     */
    private function getComposerModulePath($module) {
        $composerSrv = $this->getServiceManager()->get('MelisEngineComposer');
        $path = $composerSrv->getComposerModulePath($module);
        $miniTemplatePath = $path . '/public' . '/miniTemplatesTinyMce';
        return (! empty($path)) ? $miniTemplatePath : '';
    }

    /**
     * @param $module
     * @return string
     */
    private function getNonComposerModulePath($module) {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        if (file_exists($path) && is_dir($path)) {
            $sites = $this->getDir($path);
            if (! empty($sites)) {
                if (in_array($module, $sites)) {
                    $publicPath = $path . '/' . $module . '/public';
                    $miniTemplatePath = $publicPath . '/miniTemplatesTinyMce';
                }
            }
        }
        return $miniTemplatePath;
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
}