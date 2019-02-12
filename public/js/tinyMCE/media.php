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
            //[contextmenu, textcolor, colorpicker] this plugin is already built in the core editor as of TinyMCE v. 5
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste'
        ),
        'image_advtab' => true,
        'toolbar' => 'insertfile undo redo link image media | code',
        'init_instance_callback' => 'tinyMceCleaner',
	); 