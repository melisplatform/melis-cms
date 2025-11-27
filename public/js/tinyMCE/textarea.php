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
		    'accordion', 'anchor', 'autoresize', 'autosave', 'codesample', 'directionality', 'emoticons', 'importcss', 'pagebreak', 'quickbars', 'save', 'visualchars', 'wordcount', 'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'autoresize', 'minitemplate'
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
		'menubar' => 'file edit view insert format tools table help',
	    'toolbar' => 'undo redo | accordion accordionremove | blocks fontfamily fontsize | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist | link unlink image | table media | lineheight outdent indent | forecolor backcolor removeformat | charmap | emoticons | minitemplate code fullscreen preview | save print | pagebreak anchor codesample | ltr rtl',
		'toolbar_mode' => 'sliding',
		'deprecation_warnings' => false,
		'promotion' => false,
	    'setup' => 'melisTinyMCE.tinyMceActionEvent',
	    'init_instance_callback'  => 'tinyMceCleaner'
	];