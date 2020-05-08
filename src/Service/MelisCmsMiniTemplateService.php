<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Metadata\Metadata;

class MelisCmsMiniTemplateService extends MelisCoreGeneralService
{
    public $file_types = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG'];
    private $latest_category_id = 0;

    /**
     * @param $site_module
     * @param $name
     * @param $html
     * @param null $uploaded_image
     * @param null $uploaded_image_extension
     * @return array
     */
    public function createMiniTemplate($site_module, $name, $html, $uploaded_image = null, $uploaded_image_extension = null, $cat_id = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_create_start', $arrayParameters);

        $translator = $this->getServiceLocator()->get('translator');
        $path = $this->getModuleMiniTemplatePath($arrayParameters['site_module']);

        $success = 0;
        $errors = [];

        $this->checkCreateMiniTemplateErrors($path, $arrayParameters['name'], $arrayParameters['site_module'], $errors);

        if (empty($errors)) {
            // create the file
            $miniTemplateFile = fopen($path . '/' . $arrayParameters['name'] . '.phtml', 'wb');
            // add the html
            fwrite($miniTemplateFile, $arrayParameters['html']);
            fclose($miniTemplateFile);
            chmod($path . '/' . $arrayParameters['name'] . '.phtml', 0777);
            // copy the thumbnail
            if (!empty($arrayParameters['uploaded_image'])) {
                copy(
                    $arrayParameters['uploaded_image'],
                    $path . '/' . $arrayParameters['name'] . '.' . $arrayParameters['uploaded_image_extension']
                );
            }

            if (! empty($cat_id)) {
                $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
                $lastOrder = $table->getLatestOrder($cat_id)->current();

                if (! empty($lastOrder))
                    $lastOrder = (int)$lastOrder->mtplct_order + 1;
                else
                    $lastOrder = 1;

                $table->save(
                    [
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
            'errors' => $errors
        ];
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_create_start', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * @param $path
     * @param $template_name
     * @param $site_module
     * @param $errors
     */
    private function checkCreateMiniTemplateErrors($path, $template_name, $site_module, &$errors) {
        $translator = $this->getServiceLocator()->get('translator');

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
     * @param $current_data
     * @param $new_data
     * @param null $image
     * @return mixed
     */
    public function updateMiniTemplate($current_data, $new_data, $image = false) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_update_mtpl_start', $arrayParameters);

        $current_data = $arrayParameters['current_data'];
        $new_data = $arrayParameters['new_data'];
        if (! empty($arrayParameters['image']))
            $image = $arrayParameters['image'];

        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $current_site = $siteTable->getEntryById($current_data['miniTemplateSite'])->current();
        $current_site_path = $this->getModuleMiniTemplatePath($current_site->site_name);
        $new_site = $siteTable->getEntryById($new_data['miniTemplateSite'])->current();
        $new_site_path = $this->getModuleMiniTemplatePath($new_site->site_name);
        $current_template_name = $current_data['miniTemplateName'];
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
        $success = 1;
        $errors = [];

        if (!is_writable($current_site_path . '/' . $current_data['miniTemplateName'] . '.phtml')) {
            $translator = $this->getServiceLocator()->get('translator');
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
                    if ($image) {
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

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors
        ];

        $arrayParameters = $this->sendEvent('melis_cms_mini_template_update_mtpl_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    public function deleteMiniTemplate($mini_template_path, $mini_template_thumbnail_path, $mini_template_name = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_delete_mtpl_start', $arrayParameters);
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
                $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
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
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_delete_mtpl_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    public function getCategoryMiniTemplates($cat_id) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
        $mini_templates = $table->getTemplatesByCategoryIds([$cat_id])->toArray();
        $site_category_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $category_site = $site_category_table->getEntryByField('mtplsc_category_id', $cat_id)->current();
        $site_table = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site = $site_table->getEntryById($category_site->mtplsc_site_id)->current();
        $site_path = $this->getModuleMiniTemplatePath($site->site_name);
        $templates = [];
        $final_mini_templates = [];
        $counter = 1;

        foreach ($mini_templates as $mini_template) {
            $template = $this->getMiniTemplateFiles($site_path, $mini_template['mtplct_template_name']);
            if (! empty($template['image']))
                $image = '<img data-image="test" src="' . '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'] . '" width=100 height=100>';
            else
                $image = '<img data-image="test" src="/MelisFront/plugins/images/default.jpg" width=100 height=100>';
            $exploded = explode('.', $template['template']['file']);
            $templateName = $exploded[0];
            $tag = '<p data-module="' . $site->site_name . '" class="mini-template-tool-table-path">' . explode('..', $template['template']['path'])[1] . '/' . $template['template']['file'] . '</p>';

            $final_mini_templates[] = [
                'tempId' => (string)$counter,
                'image' => $image,
                'html_path' => $tag,
                'DT_RowId' => $mini_template['mtplct_template_name'],
                'DR_RowAttr' => [
                    'template_name' => $mini_template['mtplct_template_name']
                ],
                'DT_RowClass' => 'is-draggable'
            ];
            $counter++;
        }

        return $final_mini_templates;
    }

    public function reorderCategory($old_position, $new_position) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $categories = $table->getAffectedCategories($new_position)->toArray();
        $counter = 1;

        $connection = $this->startDbTransaction();
        try {
            foreach ($categories as $category) {
                if ($category['mtplc_order'] == $old_position) {
                    $table->save(
                        [
                            'mtplc_order' => $new_position
                        ],
                        $category['mtplsc_id']
                    );
                } else {
                    $table->save(
                        [
                            'mtplc_order' => $new_position + $counter
                        ],
                        $category['mtplsc_id']
                    );
                    $counter++;
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => ''
        ];
    }

    public function reorderMiniTemplate($old_position, $new_position, $old_parent, $new_parent, $is_same_category = true) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
        $mini_templates = $table->getAffectedMiniTemplates($new_position)->toArray();
        $counter = 1;

        $connection = $this->startDbTransaction();
        try {
            foreach ($mini_templates as $mini_template) {
                if ($mini_template['mtplct_order'] == $old_position) {
                    $table->save(
                        [
                            'mtplct_order' => $new_position
                        ],
                        $mini_template['mtplct_id']
                    );
                } else {
                    $table->save(
                        [
                            'mtplct_order' => $new_position + $counter
                        ],
                        $mini_template['mtplct_id']
                    );
                    $counter++;
                }
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => ''
        ];
    }

    public function removeMiniTemplateInCategory($mini_template) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');

        try {
            $table->deleteByField('mtplct_template_name', $mini_template);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => ''
        ];
    }

    public function addMiniTemplateInCategory($cat_id, $mini_template, $order) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');

        try {
            $table->save([
                'mtplct_category_id' => (int) $cat_id,
                'mtplct_template_name' => $mini_template,
                'mtplct_order' => $order
            ]);

            $affected_mini_tempaltes = $table->getAffectedMiniTemplates($order)->toArray();
            foreach ($affected_mini_tempaltes as $affected_mini_tempalte) {
                if ($mini_template != $affected_mini_tempalte['mtplct_template_name']) {
                    $table->save([
                        'mtplct_order' => (int)$affected_mini_tempalte['mtplct_order'] + 1
                    ], $affected_mini_tempalte['mtplct_id']);
                }
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => ''
        ];
    }

    public function deleteCategory($cat_id) {
        $category_table = $this->getServiceLocator()->get('MelisCmsCategoryTable');
        $translation_table = $this->getServiceLocator()->get('MelisCmsCategoryTransTable');
        $category_site_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $connection = $this->startDbTransaction();

        try {
            $category_table->deleteById($cat_id);
            $translation_table->deleteByField('mtplc_id', $cat_id);
            $category_site_table->deleteByField('mtplsc_category_id', $cat_id);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();

            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => ''
        ];
    }

    public function getCategoryTexts($cat_id) {
        $table = $this->getServiceLocator()->get('MelisCmsCategoryTransTable');
        $texts = $table->getEntryByField('mtplc_id', $cat_id)->toArray();

        return $texts;
    }

    /**
     * Save category
     */
    public function saveCategory($params, $cat_id) {
        $connection = $this->startDbTransaction();
        $success = 0;
        $errors = [];

        // check for trans data
        $data = $params;
        unset($data['site_id']);
        unset($data['cat_id']);
        unset($data['status']);
        $counter = 0;

        foreach ($data as $key => $value) {
            if (! empty($value))
                $counter++;
        }
        $translator = $this->getServiceLocator()->get('translator');
        if ($counter == 0) {
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
                $category_table = $this->getServiceLocator()->get('MelisCmsCategoryTable');
                $category_site_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
                $table = $this->getServiceLocator()->get('MelisCmsCategoryTransTable');
                // save category
                $user_id = $this->getCurrentUser()->usr_id;

                if (!empty($cat_id))
                    $this->latest_category_id = $cat_id;

                $this->latest_category_id = $category_table->save([
                    'mtplc_user_id' => $user_id,
                    'mtplc_creation_date' => date('Y-m-d h:i:s'),
                    'mtplc_status' => $params['status']
                ], $cat_id);

                // save category site
                $category_site_data = [
                    'mtplsc_site_id' => $params['site_id'],
                    'mtplsc_category_id' => $this->latest_category_id,
                ];

                if (empty($cat_id)) {
                    $category_site_data['mtplc_order'] = 999;
                }

                $category_site_table->save($category_site_data, $cat_id);

                unset($params['site_id']);
                unset($params['cat_id']);
                unset($params['status']);

                // save category trans
                foreach ($params as $key => $value) {
                    $exploded_text = explode('_', $key);
                    $lang_id = (int)$exploded_text[0];

                    if (!empty($value)) {
                        if (empty($cat_id)) {
                            $table->save([
                                'mtplc_id' => $this->latest_category_id,
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

        return [
            'success' => $success,
            'errors' => $errors
        ];
    }

    // find a better solution
    private function getCategoryNewOrder() {
        $category_site_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $order_id = $category_site_table->getLastOrderId()->current();

        if (! empty($order_id))
            $order_id = $order_id->mtplc_order;

        if (empty($order_id))
            $order_id = 1;
        else
            $order_id += 1;

        return $order_id;
    }

    /**
     * Returns the module path for a site
     * @param $module
     * @return string
     */
    public function getModuleMiniTemplatePath($module) {
        $path = $this->getComposerModulePath($module);
        if (empty($path))
            $path = $this->getNonComposerModulePath($module);
        return $path ?? null;
    }

    /**
     * Returns the tree data for the categories and plugins
     * @param $module
     * @param $locale
     */
    public function getTree($siteId, $locale) {
        $site_table = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site = $site_table->getEntryById($siteId)->current();
        $module = $site->site_name;
        $site_path = $this->getModuleMiniTemplatePath($site->site_name);
        $tree = [];

        if (file_exists($site_path)) {
            $mini_templates = $this->getMiniTemplates($module);
            $categories = $this->getCategories($siteId, $locale);
            $cat_ids = $this->getCategoryIds($categories);
            $db_mini_templates = $this->getDbMinitemplates($cat_ids)->toArray();
            $root_mini_templates = $this->getDbMinitemplates([-1], $siteId)->toArray();
            $root_mtpls = [];

            // only get the ones that are present on the site
            for ($i = 0; $i < count($root_mini_templates); $i++) {
                if (in_array($root_mini_templates[$i]['mtplct_template_name'], $mini_templates)) {
                    $root_mtpls[] = $root_mini_templates[$i];
                }
            }

            $mini_templates_db_temp = [];
            $temp_categories = [];
            $total = count($categories) + count($root_mini_templates);


            $cat_counter = 0;
            $root_mtpl_counter = 0;
            for ($i = 0; $i <= $total; $i++) {
                // insert category
                if (! empty($categories[$cat_counter])) {
                    if ($categories[$cat_counter]['mtplc_order'] == $i + 1) {
                        $this->insertCategoryToTheTree($categories[$cat_counter], $tree);
                        $cat_mini_templates = [];
                        foreach ($db_mini_templates as $mini_template) {
                            if ($mini_template['mtplct_category_id'] == $categories[$cat_counter]['mtplc_id']) {
                                $cat_mini_templates[] = $mini_template;
                            }

                            if (!in_array($mini_template['mtplct_template_name'], $mini_templates_db_temp))
                                $mini_templates_db_temp[] = $mini_template['mtplct_template_name'];
                        }
                        // insert category mini-templates
                        foreach ($cat_mini_templates as $cat_mini_template) {
                            $template = $this->getMiniTemplateFiles($site_path, $cat_mini_template['mtplct_template_name']);

                            if (!empty($template['image']['file']))
                                $image = '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'];
                            else
                                $image = '/MelisFront/plugins/images/default.jpg';

                            $this->insertDbMiniTemplateToTheCategory(
                                $categories[$cat_counter]['mtplc_id'] . '-' . $categories[$cat_counter]['mtplct_name'],
                                $cat_mini_template,
                                $module,
                                $image,
                                $tree
                            );
                        }

                        $cat_counter++;
                    }
                }
                // Insert mini-templates in db with parent #
                if (! empty($root_mtpls[$root_mtpl_counter])) {
                    if ($root_mtpls[$root_mtpl_counter]['mtplct_order'] == $i + 1) {
                        $template = $this->getMiniTemplateFiles($site_path, $root_mtpls[$root_mtpl_counter]['mtplct_template_name']);

                        if (!empty($template['image']['file']))
                            $image = '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'];
                        else
                            $image = '/MelisFront/plugins/images/default.jpg';
                        $this->insertLocalMiniTemplateToTheTree($root_mtpls[$root_mtpl_counter]['mtplct_template_name'], $module, $image, $tree);

                        if (! in_array($root_mtpls[$root_mtpl_counter]['mtplct_template_name'], $mini_templates_db_temp))
                            $mini_templates_db_temp[] = $root_mtpls[$root_mtpl_counter]['mtplct_template_name'];

                        $root_mtpl_counter++;
                    }
                }
            }

            // insert mini-templates that are not in db
            foreach($mini_templates as $mini_template){
                if(!in_array($mini_template, $mini_templates_db_temp)){
                    $template=$this->getMiniTemplateFiles($site_path,$mini_template);

                    if(!empty($template['image']['file']))
                        $image='/'.$site->site_name.'/miniTemplatesTinyMce/'.$template['image']['file'];
                    else
                        $image='/MelisFront/plugins/images/default.jpg';
                    $this->insertLocalMiniTemplateToTheTree($mini_template,$module,$image,$tree);
                }
            }

            foreach ($categories as $category) {
                if ($category['mtplc_order'] == 999) {
                    $this->insertCategoryToTheTree($category, $tree);
                }
            }
        }

        return $tree;
    }

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

    public function saveTree($site_id, $tree_data) {
        $site_category_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $category_template_table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
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
                    } else {

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
     * Returns the list of mini templates of a site
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

    public function getCategories($site_id, $locale) {
        $table = $this->getServiceLocator()->get('MelisCmsCategoryTable');
        $lang_table = $this->serviceLocator->get('MelisEngineTableCmsLang');
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

    public function getDbMinitemplates($cat_ids, $siteId = null) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
        return $table->getTemplatesByCategoryIds($cat_ids, $siteId);
    }

    public function getCategoryIds($categories) {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category['mtplc_id'];
        }
        return $ids;
    }

    /**
     * Get all files of a mini template
     * @param $path
     * @param $template
     * @return array
     */
    public function getMiniTemplateFiles($path, $template) {
        $files = glob($path ."/*" . $template . "*");
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
     * Gets the mini template thumbnail
     * @param $path
     * @param $template
     * @return array|null
     */
    private function getMiniTemplateThumbnail($path, $template)
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

    private function structureData($templates) {
        // get categories and ordering
        // structure data based on categories and ordering
        $miniTemplates = [];
        foreach ($templates as $template) {
            $plugin = [];
            $plugin['text'] = $template;
            $plugin['icon'] = 'fa fa-circle text-success';
            $plugin['state']['opened'] = false;
            $plugin['state']['disabled'] = false;
            $plugin['state']['selected'] = false;
            $plugin['type'] = 'plugin';
            $plugin['children'] = [];
            array_push($miniTemplates, $plugin);
        }

        return $miniTemplates;
    }

    /**
     * Returns the module path for a site that is inside the vendor
     * @param $module
     * @return string
     */
    private function getComposerModulePath($module) {
        $composerSrv = $this->serviceLocator->get('MelisEngineComposer');
        $path = $composerSrv->getComposerModulePath($module);
        $miniTemplatePath = $path . '/public' . '/miniTemplatesTinyMce';
        return (! empty($path)) ? $miniTemplatePath : '';
    }

    /**
     * Returns the module path for a site that is not inside vendor
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
     * Get current user
     * @return mixed
     */
    private function getCurrentUser() {
        $auth = $this->getServiceLocator()->get('MelisCoreAuth');
        return $auth->getStorage()->read();
    }

    /**
     * start db transaction
     * @return mixed
     */
    private function startDbTransaction() {
        $db = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $connection = $db->getDriver()->getConnection();
        $connection->beginTransaction();
        return $connection;
    }

    /**
     * Returns the files in the directory
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
