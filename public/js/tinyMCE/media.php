<?php 
	return array(
        'relative_urls' => false,
        'selector' => 'media-editable-selector',
        'language' => 'en',
        'inline' => true,
        'menubar' => false,
        'forced_root_block' => 'p',
        'cleanup' => false,
        'verify_html' => false,
        'plugins' => array(
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste'
        ),
        'image_advtab' => true,
        'toolbar' => 'insertfile undo redo link image media | code',
        'init_instance_callback' => 'tinyMceCleaner',
	); 