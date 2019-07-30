<?php 
	return array(
        'relative_urls' => false,
        'selector' => 'media-editable-selector',
        'language' => 'en',
        'inline' => true,
        'menubar' => false,
        'forced_root_block' => '',
        'cleanup' => false,
        'verify_html' => false,
        'file_picker_types' => 'file image media',
        'file_picker_callback' => 'filePickerCallback',
        'images_upload_url' => '/melis/MelisCore/MelisTinyMce/uploadImage',
        'plugins' => array(
            //[contextmenu, textcolor, colorpicker] this plugin is already built in the core editor as of TinyMCE v. 5
            'lists advlist autolink link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste'
        ),
        'image_advtab' => true,
        'toolbar' => 'insertfile undo redo link image media | code',
        'init_instance_callback' => 'tinyMceCleaner',
	); 