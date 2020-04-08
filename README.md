# melis-cms

MelisCms provides a full CMS for Melis Platform, including templating system, drag'n'drop of plugins, SEO and many administration tools.

## Getting Started

These instructions will get you a copy of the project up and running on your machine.  
This Melis Platform module is made to work with the MelisCore.

### Prerequisites

You will need to install melisplatform/melis-core and melisplatform/melis-engine in order to have this module running.  
This will automatically be done when using composer.

### Installing

Run the composer command:
```
composer require melisplatform/melis-cms
```

## Tools & Elements provided

* Page Edition System (Edition, Properties, SEO, Languages)
* Site Tool
* Template Tool
* Platform Ids Tool
* Site Redirect Tool
* Styles Tool
* Page's Languages Tool
* Dashboard Indicators
* Site Tree View
* Site Tree Explorer & Search
* Melis Templating Plugins Components for back-office

## Running the code

### MelisCms Services  

MelisCms provides many services to be used in other modules:  

* MelisCmsPageService  
Services to save a page and to save its different parts (SEO, styles, languages, etc).  
File: /melis-cms/src/Service/MelisCmsPageService.php  
```
// Get the service
$pageSrv = $this->getServiceManager()->get('MelisCmsPageService');  
// Save a page and get its id back
$pageId = $pageSrv->savePage($pageTree, $pagePublished, $pageSaved, $pageSeo, $pageLang, $pageStyle);  
```

* MelisCmsSiteService  
Save a site, get the list of pages of a site and many more.  
File: /melis-cms/src/Service/MelisCmsSiteService.php  
```
// Get the service
$cmsSiteSrv = $this->getServiceManager()->get('MelisCmsSiteService');  
// Get list of pages of this site
$sitePages = $cmsSiteSrv->getSitePages($siteId);  
```

* MelisCmsPageGetterService  
Get the full HTML of a page. 
This service works with the cache system. A page must have been generated at least one, so that the cache is generated and available to be used by the service.  
Cache is generated in this folder: /cache  
File: /melis-cms/src/Service/MelisCmsPageGetterService.php  
```
// Get the service
$pageGetterService = $this->getServiceManager()->get('MelisCmsPageGetterService');  
// Get list of pages of this site
$pageContent = $cmsSiteSrv->getPageContent($pageId);  
```

* MelisCmsRightsService  
Get the rights defined for the user and adapt access to the different elements of the interface:  
File: /melis-cms/src/Service/MelisCmsRightsService.php    
```
// Get the service  
$melisCmsRights = $this->getServiceManager()->get('MelisCmsRights');  
// Get the user's rights  
$xmlRights = $melisCoreAuth->getAuthRights();  
// find if a user has access to it
// Example: find if a user has access to a specific page id
$isAccessible = $melisCmsRights->isAccessible($xmlRights, MelisCmsRightsService::MELISCMS_PREFIX_PAGES, $idPage);          
```  


### MelisCms Forms  

#### Forms factories
All Melis CMS forms are built using Form Factories.  
All form configuration are available in the file: /melis-cms/config/app.forms.php  
Any module can override or add items in this form by building the keys in an array and marge it in the Module.php config creation part.  
``` 
return array(
	'plugins' => array(
	
		// MelisCms array
		'meliscms' => array(
		
			// Form key
			'forms' => array(
			
				// MelisCms Page Properties form
				'meliscms_page_properties' => array(
					'attributes' => array(
						'name' => 'pageproperties',
						'id' => 'idformpageproperties',
						'method' => 'POST',
						'action' => '/melis/MelisCms/Page/saveProperties',
					),
					'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
					'elements' => array(  
						array(
							'spec' => array(
								...
							),
						),
					),
					'input_filter' => array(      
						'page_id' => array(
							...
						),   
					),
				),
			),
		),
	),
),
``` 

#### Forms elements
MelisCms provides many form elements to be used in forms:  
* MelisCmsTemplateSelect: a dropdown to select a template  
* MelisCmsPlatformSelect: a dropdown to select a platform  
* MelisCmsStyleSelect: a dropdown to select a style  
* MelisSwitch: an on-off button designed for Melis Platform  
* MelisCmsLanguageSelect: a dropdown to select the language 
* MelisCmsPageLanguagesSelect: a dropdown to select the page language 
* MelisMultiValInput: multiple input selection  
* MelisCmsPlatformIDsSelect: a dropdown to select the platform id   
* MelisCmsPluginSiteSelect: a dropdown to select the site   
* MelisCmsPluginSiteModuleSelect: a dropdown to select the module   


### Listening to services and update behavior with custom code  
Most services trigger events so that the behavior can be modified.  
```  
public function attach(EventManagerInterface $events)
{
    $sharedEvents      = $events->getSharedManager();
    
    $callBackHandler = $sharedEvents->attach(
    	'MelisCms',
    	array(
    		'meliscms_page_save_start',
    		'meliscms_page_publish_start',
    	),
    	function($e){

    		$sm = $e->getTarget()->getEvent()->getApplication()->getServiceManager();
    		
    		// Custom Code here
    	},
    100);
    
    $this->listeners[] = $callBackHandler;
}
```  

### TinyMCE configurations  
MelisCms brings 3 defaults configuration when editing a template within a "MelisTag" editable area:  
* html: full sets of buttons for the editor  
* textarea: buttons limited to text and links  
* media: buttons limited to media object insertion such as images and videos  
Creating other config is possible. Add the config in a file then declare the file in the module.config.php file of the module:  
```  
// Config Files  
'tinyMCE' => array(  
	'html' => 'MelisCms/public/js/tinyMCE/html.php',  
	'textarea' => 'MelisCms/public/js/tinyMCE/textarea.php',  
	'media' => 'MelisCms/public/js/tinyMCE/media.php',  
),  
```  
* mini templates will automatically turned into plugins if places in their folder.

### Javascript helpers provided with MelisCms    

* melisLinkTree: Shows a modal with the treeview to search and make a page selection
```  
melisLinkTree.createInputTreeModal('#sourcePageId');  
```  


## Authors

* **Melis Technology** - [www.melistechnology.com](https://www.melistechnology.com/)

See also the list of [contributors](https://github.com/melisplatform/melis-cms/contributors) who participated in this project.


## License

This project is licensed under the OSL-3.0 License - see the [LICENSE.md](LICENSE.md) file for details