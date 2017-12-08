<?php 
	return array(
		'relative_urls' => false,
		'selector' => 'textarea-editable-selector',
		'language' => 'en',
		'inline' => true,
		'menubar' => false,
		'forced_root_block'  => 'p',
		'cleanup'  => false,
		'verify_html'  => false,
		'plugins' => array(
		    'advlist autolink lists link image charmap print preview anchor',
		    'searchreplace visualblocks code fullscreen',
		    'insertdatetime media table contextmenu paste autoresize'
	    ),
	    'autoresize_on_init' => false,
	    'toolbar' => 'undo redo link unlink | code',
	    'init_instance_callback'  => 'tinyMceCleaner',
	); 