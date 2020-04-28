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
    public function createMiniTemplate($site_module, $name, $html, $uploaded_image = null, $uploaded_image_extension = null) {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_create_start', $arrayParameters);

        $translator = $this->getServiceLocator()->get('translator');
        $path = $this->getModuleMiniTemplatePath($arrayParameters['site_module']);
        $success = 0;

        if (! empty($path)) {
            if (! file_exists($path . '/' . $arrayParameters['name'] . '.phtml')) {
                $errors = [];

                if (is_writable($path)) {
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
                    $success = 1;
                } else {
                    $errors[] = $path . $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_path_not_writable');
                }
            } else {
                $errors['miniTemplateName'] = [
                    'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_file_already_exists'),
                    'label' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_name')
                ];
            }
        } else {
            $errors[] = $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_no_site_path_found') . $arrayParameters['site_module'];
        }

        $arrayParameters['results'] = [
            'success' => $success,
            'errors' => $errors
        ];
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_create_start', $arrayParameters);
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
            $image = '<img data-image="test" src="' . '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'] . '" width=100 height=100>';
            $exploded = explode('.', $template['template']['file']);
            $templateName = $exploded[0];
            $tag = '<p class="mini-template-tool-table-path">' . explode('..', $template['template']['path'])[1] . '/' . $template['template']['file'] . '</p>';

            $final_mini_templates[] = [
                'id' => (string)$counter,
                'image' => $image,
                'html_path' => $tag,
                'DT_RowId' => $mini_template['mtplct_id'],
                'DR_RowAttr' => array('data-productid' => $counter, 'data-productname' => 'test'),
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
        } catch (\Exception $e) {
            $connection->rollback();
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        $connection->commit();
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
        } catch (\Exception $e) {
            $connection->rollback();
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        $connection->commit();
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
                'mtplct_category_id' => $cat_id,
                'mtplct_template_name' => $mini_template,
                'mtplct_order' => $order
            ]);
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
        } catch (\Exception $e) {
            $connection->rollback();

            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        $connection->commit();
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

        try {
            $this->saveCategorySite($params['site_id'], $cat_id);
            unset($params['site_id']);
            unset($params['cat_id']);
            $this->saveCategoryTrans($params, $cat_id);
            $success = 1;
        } catch (\Exception $e) {
            $connection->rollback();
            $errors[] = $e->getMessage();
        }

        $connection->commit();

        return [
            'success' => $success,
            'errors' => $errors
        ];
    }

    // move to service
    private function saveCategorySite($site_id, $cat_id) {
        $category_table = $this->getServiceLocator()->get('MelisCmsCategoryTable');
        $category_site_table = $this->getServiceLocator()->get('MelisCmsMiniTplSiteCategoryTable');
        $user_id = $this->getCurrentUser()->usr_id;

        if (! empty($cat_id))
            $this->latest_category_id = $cat_id;

        $this->latest_category_id = $category_table->save([
            'mtplc_user_id' => $user_id,
            'mtplc_creation_date' => date('Y-m-d h:i:s')
        ], $cat_id);


        $category_site_data = [
            'mtplsc_site_id' => $site_id,
            'mtplsc_category_id' => $this->latest_category_id,
        ];

        if (empty($cat_id)) {
            $new_order = $this->getCategoryNewOrder();
            $category_site_data['mtplc_order'] = $new_order;
        }

        $category_site_table->save($category_site_data, $cat_id);
    }

    // move to service
    private function saveCategoryTrans($texts, $cat_id) {
        $table = $this->getServiceLocator()->get('MelisCmsCategoryTransTable');

        if (! empty($cat_id))
            $db_texts = $this->getCategoryTexts($cat_id);


        foreach ($texts as $key => $value) {
            $exploded_text = explode('_', $key);
            $lang_id = $exploded_text[0];

            if (!empty($value)) {
                if (empty($cat_id)) {
                    $table->save([
                        'mtplc_id' => $this->latest_category_id,
                        'mtplct_lang_id' => $lang_id,
                        'mtplct_name' => $value
                    ]);
                } else {
                    foreach ($db_texts as $db_text) {
                        if ($lang_id == $db_text['mtplct_lang_id']) {
                            $table->save([
                                'mtplct_name' => $value
                            ], $db_text['mtplct_id']);
                        }
                    }
                }
            }
        }
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
        return $path;
    }

    /**
     * Returns the tree data for the categories and plugins
     * @param $module
     * @param $locale
     */
    public function getTree($module, $locale) {
        $site_table = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site = $site_table->getEntryByField('site_name', $module)->current();
        $site_path = $this->getModuleMiniTemplatePath($site->site_name);
        $tree = [];

        $mini_templates = $this->getMiniTemplates($module);
        $categories = $this->getCategories($site->site_id, $locale);
        $cat_ids = $this->getCategoryIds($categories);
        $db_mini_templates = $this->getDbMinitemplates($cat_ids)->toArray();

        foreach ($categories as $category) {
            $this->insertCategoryToTheTree($category, $tree);

            // better to use array_map? and just remove the empty elements?
            $cat_mini_templates = [];
            foreach ($db_mini_templates as $mini_template) {
                if ($mini_template['mtplct_category_id'] == $category['mtplc_id']) {
                    $cat_mini_templates[] = $mini_template;
                }

                $key = array_search(
                    $mini_template['mtplct_template_name'],
                    $mini_templates
                );
                unset($mini_templates[$key]);
            }

            foreach ($cat_mini_templates as $cat_mini_template) {
                $this->insertDbMiniTemplateToTheCategory(
                    $category['mtplc_id'] . '-' . $category['mtplct_name'],
                    $cat_mini_template,
                    $tree
                );
            }
        }

        foreach ($mini_templates as $mini_template) {
            $template = $this->getMiniTemplateFiles($site_path, $mini_template);

            if (! empty($template['image']['file']))
                $image = '/' . $site->site_name . '/miniTemplatesTinyMce/' . $template['image']['file'];
            else
                $image = '/MelisFront/plugins/images/default.jpg';
            $this->insertLocalMiniTemplateToTheTree($mini_template, $module, $image, $tree);
        }

        return $tree;
    }

    private function insertCategoryToTheTree($category, &$tree) {
        $tree[] = [
            'id' => $category['mtplc_id'] . '-' . $category['mtplct_name'],
            'parent' => '#',
            'text' => $category['mtplct_name'],
            'icon' => 'fa fa-circle text-success',
            'type' => 'category'
        ];
    }

    private function insertDbMiniTemplateToTheCategory($category_id, $mini_template, &$tree) {
        $tree[] = [
            'id' => $mini_template['mtplct_template_name'],
            'parent' => $category_id,
            'text' => $mini_template['mtplct_template_name'],
            'icon' => 'fa fa-plug text-success',
            'type' => 'mini-template'
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

    /**
     * Returns the list of mini templates of a site
     * @param $module
     * @return array
     */
    public function getMiniTemplates($module) {
        $path = $this->getModuleMiniTemplatePath($module);
        $files = array_diff(scandir($path), ['..', '.']);
        $templates = [];

        // TODO: change variable name to files
        foreach ($files as $file) {
            $exploded = explode('.', $file);
            $templateName = $exploded[0];
            $extension = $exploded[1];

            if (in_array($extension, ['phtml'])) {
                array_push($templates, $templateName);
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

    public function getDbMinitemplates($cat_ids) {
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
        return $table->getTemplatesByCategoryIds($cat_ids);
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
        return $miniTemplatePath ?? '';
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