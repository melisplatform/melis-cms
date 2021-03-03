<?php 
	return array(
		'relative_urls' => false,
		'selector' => 'textarea-editable-selector',
		'language' => 'en',
		'inline' => true,
		'menubar' => false,
		'forced_root_block'  => '',
		'cleanup'  => false,
		'verify_html'  => false,
		'file_picker_types' => 'file image media',
        'file_picker_callback' => 'filePickerCallback',
        'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
		'plugins' => array(
			//[contextmenu, textcolor, colorpicker] this plugin is already built in the core editor as of TinyMCE v. 5
		    'lists advlist autolink link image charmap print preview anchor',
		    'searchreplace visualblocks code fullscreen',
		    'insertdatetime media table paste autoresize minitemplate'
	    ),
		'external_plugins' => [
            'minitemplate' => '/MelisCore/js/minitemplate/plugin.min.js'
        ],
		'melis_minitemplate' => [
			/**
			 * return templates with the given prefix only
			 */
			'prefix' => '',

			/**
			 * site id
			 */
			'site_id' => ''
		],
	    'autoresize_on_init' => false,
	    'toolbar' => 'undo redo link unlink | forecolor backcolor minitemplate | code',
	    'setup' => 'melisTinyMCE.tinyMceActionEvent',
	    'init_instance_callback'  => 'tinyMceCleaner'
	);