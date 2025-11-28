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
		    'anchor', 'autoresize', 'autosave', 'emoticons', 'importcss', 'save', 'visualchars', 'wordcount', 'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'minitemplate'
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
		'menubar' => 'edit view tools',
		'menu' => [
			'view' => [
				'title' => 'View',
				'items' => 'code | visualaid visualchars visualblocks'
			]
		],
	    'toolbar' => 'undo redo |  fontfamily fontsize | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist | link unlink image | table media | lineheight outdent indent | forecolor backcolor | charmap | emoticons | minitemplate code fullscreen | anchor',
		'font_size_formats' => '0.6665rem 0.8331rem 1.0rem 1.1664rem 1.4996rem 2.0rem 2.5rem 3.0rem',
		'toolbar_mode' => 'sliding',
		'deprecation_warnings' => false,
		'promotion' => false,
	    'setup' => 'melisTinyMCE.tinyMceActionEvent',
	    'init_instance_callback'  => 'tinyMceCleaner'
	];