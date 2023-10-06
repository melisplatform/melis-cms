<?php 
	return [
        'relative_urls' => false,
        'selector' => 'media-editable-selector',
        'language' => 'en',
        'inline' => true,
        'menubar' => false,
        'forced_root_block' => 'div',
        'cleanup' => false,
        'verify_html' => false,
        'file_picker_types' => 'file image media',
        'file_picker_callback' => 'filePickerCallback',
        'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
        'plugins' => [
            'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'preview', 'anchor',
            'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'minitemplate'
        ],
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
        'image_advtab' => true,
        'toolbar' => 'insertfile undo redo link image media minitemplate | code',
        'deprecation_warnings' => false,
        'init_instance_callback' => 'tinyMceCleaner',
    ]; 