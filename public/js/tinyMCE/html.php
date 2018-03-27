<?php 
	return array(
        'relative_urls' => false,
        'selector' => 'html-editable-selector',
        'language' => 'en',
        'inline' => true,
        'templates' => 'miniTemplates',
        'menubar' => false,
        'forced_root_block' => '',
        'cleanup' => false,
        'verify_html' => false,
        'plugins' => array(
            'advlist autolink lists link image charmap preview anchor textcolor colorpicker emoticons help hr nonbreaking',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu  template'
        ),
        'image_advtab' => true,
        'toolbar' => 'insertfile undo redo | formatselect | forecolor backcolor | bold italic strikethrough underline hr nonbreaking | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media template | charmap emoticons code print help',
        'init_instance_callback' => 'tinyMceCleaner',
	); 