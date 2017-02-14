<?php 
	return array(
		'relative_urls' => false,
		'selector' => 'html-editable-selector',
		'language' => 'en',
		'inline' => true,
		'templates'  => 'miniTemplates',
		'menubar' => false,
		'force_br_newlines'  => false,
		'force_p_newlines'  => false,
		'cleanup'  => false,
		'verify_html'  => false,
		'forced_root_block'  => '',
		'plugins' => array(
		    'advlist autolink lists link image charmap print preview anchor',
		    'searchreplace visualblocks code fullscreen',
		    'insertdatetime media table contextmenu paste template'
	    ),
	    'toolbar' => 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media template | code',
	    'init_instance_callback'  => 'tinyMceCleaner',
	); 