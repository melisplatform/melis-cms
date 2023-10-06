<?php
   return [
       'relative_urls' => false,
       'selector' => 'html-editable-selector',
       'language' => 'en',
       'inline' => true,
       'templates' => 'miniTemplates',
       'menubar' => false,
       'forced_root_block' => 'div',
       // 'paste_word_valid_elements'=> "p,b,strong,i,em,h1,h2,h3,h4",
       'cleanup' => false,
       'verify_html' => false,
       'file_picker_types' => 'file image media',
       'file_picker_callback' => 'filePickerCallback',
       'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
       'plugins' => [
           'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'preview', 
           'anchor', 'emoticons', 'help', 'nonbreaking', 'searchreplace', 'visualblocks',
           'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'minitemplate'
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
       'toolbar' => 'insertfile undo redo | formatselect | forecolor | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media minitemplate | code',
       'deprecation_warnings' => false,
       'setup' => 'melisTinyMCE.tinyMceActionEvent',
       'init_instance_callback' => 'tinyMceCleaner'        
    ];

# For Reference
/* return array(
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
 ); */
