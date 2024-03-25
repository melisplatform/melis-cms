<?php 
	return [
		'relative_urls' => false,
		'selector' => 'textarea-editable-selector',
		'language' => 'en',
		'inline' => true,
		'menubar' => false,
		'forced_root_block'  => 'p',
		'image_uploadtab' => false,
		'cleanup'  => false,
		'verify_html'  => false,
		'file_picker_types' => 'file image media',
        'file_picker_callback' => 'filePickerCallback',
        'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
		'plugins' => [
		    'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 
			'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'autoresize', 'minitemplate'
		],
		'external_plugins' => [
            'minitemplate' => '/MelisCore/js/minitemplate/plugin.min.js?v=20230214'
        ],
		'melis_minitemplate' => [
			// return templates with the given prefix only
			'prefix' => '',
			// site id
			'site_id' => ''
		],
	    'autoresize_on_init' => false,
	    'toolbar' => 'undo redo link unlink | forecolor backcolor | minitemplate code',
		'toolbar_mode' => 'sliding',
		'deprecation_warnings' => false,
		'promotion' => false,
	    'setup' => 'melisTinyMCE.tinyMceActionEvent',
	    'init_instance_callback'  => 'tinyMceCleaner'
	];