<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MiniTemplateMenuManagerController extends AbstractActionController
{
    public $module = 'meliscms';
    public $tool_key = 'meliscms_mini_template_menu_manager_tool';
    public $form_add_category_key = 'menu_manager_tool_site_add_category';

    public function renderMenuManagerToolAction() {}
    public function renderMenuManagerToolHeaderAction() {}
    public function renderMenuManagerToolBodyAction() {}
    public function renderMenuManagerToolAddCategoryBodyPropertiesContentAction() {}
    public function renderMiniTemplateMenuManagerToolTableRefreshAction() {}

    /**
     * Renders the category plugins zone
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyPluginsContentAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->isHidden = (empty($params['isHidden'])) ? true : false;
        $view->id = (! empty($params['id'])) ? $params['id'] : null;
        $view->formType = (! empty($params['formType'])) ? $params['formType'] : 'add';
        $view->status = $params['status'] ?? 0;
        return $view;
    }

    /**
     * Renders the category zone contents
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyContentsAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->isHidden = (empty($params['isHidden'])) ? true : false;
        $view->id = (! empty($params['id'])) ? $params['id'] : null;
        $view->formType = (! empty($params['formType'])) ? $params['formType'] : 'add';
        return $view;
    }

    /**
     * Renders the category data table
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyPluginsTableAction() {
        $params = $this->params()->fromQuery();
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('meliscms', 'meliscms_mini_template_menu_manager_tool');
        $columns = $melisTool->getColumns();
        $columns['actions'] = ['text' => $translator->translate('tr_meliscms_action')];

        $tableOption = array(
            'serverSide' => 'false',
            'paging' => 'false',
            'responsive' => array(
                'details' => array(
                    'type' => 'column'
                )
            )
        );
        /**
         * rowReorder option for the fix: http://mantis.melistechnology.fr/view.php?id=4459
         * ::before / + button on mobile responsive doesn't show the other column details
         */
        if (!preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4))) {
            $tableOption['rowReorder'] = [
                'dataSrc' => 'tempId',
                'selector' => 'td:nth-child(1)',
            ];
        }

        $config = [];

        if (! empty($this->params()->fromQuery()['formType'])) {
            $config = $melisTool->getDataTableConfiguration(
                '#tableMiniTemplateMenuManagerPlugins',
                true,
                false,
                $tableOption
            );
        }

        $view = new ViewModel();
        $view->formType = 'edit';
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $config;
        return $view;
    }

    /**
     * Returns the mini templates of a category
     * @return JsonModel
     */
    public function getMiniTemplatesAction() {
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $post = $this->getRequest()->getPost();
        $draw = $post['draw'];
        $start = $post['start'];
        $length = $post['length'];
        $total = $filtered = 0;
        $miniTemplates = [];

        $mini_templates = $service->getCategoryMiniTemplates($post['id']);
        $total = $filtered = count($mini_templates);

        return new JsonModel([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $mini_templates
        ]);
    }

    /**
     * Renders the category header
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryHeaderAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->formType = (!empty($params['formType'])) ? $params['formType'] : 'add';
        $view->id = (! empty($params['id'])) ? $params['id'] : null;
        return $view;
    }

    /**
     * Renders the cateogory tabs (properties and plugins)
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyTabsAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->formType = (!empty($params['formType'])) ? $params['formType'] : 'add';
        return $view;
    }

    /**
     * Renders the cateogry container
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryContainerAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->isHidden = (empty($params['isHidden'])) ? true : false;
        $view->id = (! empty($params['id'])) ? $params['id'] : null;
        $view->formType = (! empty($params['formType'])) ? $params['formType'] : 'add';
        return $view;
    }

    /**
     * Renders the category zone
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->isHidden = (empty($params['isHidden'])) ? true : false;
        $view->id = (! empty($params['id'])) ? $params['id'] : null;
        $view->formType = (! empty($params['formType'])) ? $params['formType'] : 'add';
        return $view;
    }

    /**
     * renders the category form
     * @return ViewModel
     */
    public function renderMenuManagerToolAddCategoryBodyPropertiesFormAction() {
        $params = $this->params()->fromQuery();
        $lang_service = $this->getServiceLocator()->get('MelisEngineLang');
        $languages = $lang_service->getAvailableLanguages();
        $form = $this->getForm($this->module, $this->tool_key, $this->form_add_category_key);
        $data = [];

        if (! empty($params['id'])) {
            $exploded_id = explode('-', $params['id']);
            $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
            $texts = $service->getCategoryTexts($exploded_id[0]);

            foreach ($texts as $text) {
                $data[$text['mtplct_lang_id']] = $text['mtplct_name'];
            }

            $form->setAttribute('id', 'id_menu_manager_tool_site_update_category');
            $form->setAttribute('name', 'menu_manager_tool_site_update_category');
        }

        $view = new ViewModel();
        $view->languages = $languages;
        $view->form = $form;
        $view->texts = $data;
        $view->cat_id = $exploded_id[0] ?? 0;
        $view->status = $params['status'] ?? 0;
        return $view;
    }

    /**
     * Renders the left zone (site select)
     * @return ViewModel
     */
    public function renderMenuManagerToolBodyLeftAction() {
        $view = new ViewModel();
        $view->sites = $this->getServiceLocator()->get('MelisCmsSiteService')->getAllSites();
        return $view;
    }

    /**
     * Renders the right (jstree)
     * @return ViewModel
     */
    public function renderMenuManagerToolBodyRightAction() {
        $lang_service = $this->getServiceLocator()->get('MelisEngineLang');
        $languages = $lang_service->getAvailableLanguages();

        $container = new Container('meliscore');
        $current_locale = $container['melis-lang-locale'];
        $current_lang = $lang_service->getLangByLocale($current_locale);

        $view = new ViewModel();
        $view->melisKey = $this->getMelisKey();
        $view->languages = $languages;
        $view->current_lang = $current_lang;
        return $view;
    }

    /**
     * Returns the tree data
     * @return JsonModel
     */
    public function getTreeAction() {
        $params = $this->params()->fromQuery();
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $tree = $service->getTree($params['siteId'], $params['langlocale']);

        return new JsonModel($tree);
    }

    /**
     * Saves the category
     * @return JsonModel
     */
    public function saveCategoryAction() {
        $params = $this->params()->fromPost();
        $event = 'meliscms_mini_template_menu_manager_create_category';
        $type_code = 'CMS_MTPL_CATEGORY_ADD';
        $message = 'tr_meliscms_mini_template_menu_manager_category_create_fail';

        if (! empty($params['cat_id'])) {
            $event = 'meliscms_mini_template_menu_manager_update_category';
            $type_code = 'CMS_MTPL_CATEGORY_UPDATE';
            $message = 'tr_meliscms_mini_template_menu_manager_category_update_fail';
        }

        $this->getEventManager()->trigger($event . '_start', $this, $params);
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $res = $service->saveCategory($params, $params['cat_id']);

        if ($res['success']) {
            $message = 'tr_meliscms_mini_template_menu_manager_category_created_successfully';
            if (! empty($params['cat_id']))
                $message = 'tr_meliscms_mini_template_menu_manager_category_updated_successfully';
        }

        $response = [
            'success' => $res['success'],
            'textTitle' => 'tr_meliscms_mini_template_menu_manager_category',
            'textMessage' => $message,
            'errors' => $res['errors'],
            'id' => $res['id']
        ];

        $this->getEventManager()->trigger(
            $event . '_end',
            $this,
            array_merge($response, ['typeCode' => $type_code, 'itemId' => $res['id'] ?? 0])
        );
        return new JsonModel($response);
    }

    /**
     * Deletes a category
     * @return JsonModel
     */
    public function deleteCategoryAction() {
        $params = $this->params()->fromPost();
        $this->getEventManager()->trigger('meliscms_mini_template_menu_manager_delete_category_start', $this, $params);
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $cat_id = explode('-', $params['id'])[0];
        $message = 'tr_meliscms_mini_template_menu_manager_category_delete_fail';
        $res = $service->deleteCategory($cat_id);

        if ($res['success']) {
            $message = 'tr_meliscms_mini_template_menu_manager_category_deleted_successfully';
        }

        $response = [
            'success' => $res['success'],
            'errors' => $res['errors'],
            'textTitle' => 'tr_meliscms_mini_template_menu_manager_category',
            'textMessage' => $message,
            'id' => $cat_id
        ];

        $this->getEventManager()->trigger(
            'meliscms_mini_template_menu_manager_delete_category_end',
            $this,
            array_merge($response, ['typeCode' => 'CMS_MTPL_CATEGORY_DELETE', 'itemId' => $cat_id])
        );

        return new JsonModel($response);
    }

    /**
     * Saves the js tree
     * @return JsonModel
     */
    public function saveTreeAction() {
        $params = $this->params()->fromPost();
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $response = $service->saveTree($params['site_id'], json_decode($params['tree_data'], true));

        return new JsonModel([
            'success' => $response['success'],
            'errors' => $response['errors']
        ]);
    }

    /**
     * Saves the new order of mini templates when it is dragged and dropped in the data table
     * @return JsonModel
     */
    public function reorderMiniTemplatesAction() {
        $params = $this->params()->fromPost();
        $templates = explode(',', $params['data']);
        $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');

        foreach ($templates as $template) {
            $exploded = explode('-', $template);
            $newPosition = $exploded[count($exploded) - 1];
            unset($exploded[count($exploded) - 1]);
            $templateName = implode('-', $exploded);
            $table->update(
                [
                    'mtplct_order' => $newPosition
                ],
                'mtplct_template_name',
                $templateName
            );
        }

        return new JsonModel([
            'success' => true,
            'errors' => []
        ]);
    }

    /**
     * Get Form
     * @param $module
     * @param $toolKey
     * @param $formKey
     * @return mixed
     */
    private function getForm($module, $toolKey, $formKey)
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey($module, $toolKey);
        return $toolSvc->getForm($formKey);
    }

    /**
     * This will get the errors of the form
     * @param $errors
     * @param $formConfig
     * @return mixed
     */
    private function getFormErrors($errors, $form)
    {
        foreach ($errors as $fieldName => $fieldErrors) {
            $errors[$fieldName] = $fieldErrors;
            $errors[$fieldName]['label'] = $form->get($fieldName)->getLabel();
        }
        return $errors;
    }

    /**
     * Returns the melis key
     * @return mixed
     */
    private function getMelisKey()
    {
        return $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
    }
}