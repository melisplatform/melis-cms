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
		'plugins' => array(
			//[contextmenu, textcolor, colorpicker] this plugin is already built in the core editor as of TinyMCE v. 5
		    'lists advlist autolink link image charmap print preview anchor',
		    'searchreplace visualblocks code fullscreen',
		    'insertdatetime media table paste autoresize'
	    ),
	    'autoresize_on_init' => false,
	    'toolbar' => 'undo redo link unlink | forecolor backcolor | code',
	    'setup' => 'melisTinyMCE.tinyMceActionEvent',
	    'init_instance_callback'  => 'tinyMceCleaner'
	);