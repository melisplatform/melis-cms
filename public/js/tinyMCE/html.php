<?php
   return [
       'relative_urls' => false,
       'selector' => 'html-editable-selector',
       'language' => 'en',
       'inline' => true,
       //'templates' => 'miniTemplates',
       'mini_templates_url' => '/melis/MelisCore/MelisTinyMce/getTinyTemplates',
       'menubar' => false,
       'forced_root_block' => 'p',
       //'paste_word_valid_elements'=> "p,b,strong,i,em,h1,h2,h3,h4",
       'image_uploadtab' => false,
       'cleanup' => false,
       'verify_html' => false,
       'file_picker_types' => 'file image media',
       'file_picker_callback' => 'filePickerCallback',
       'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
       'plugins' => [
           'accordion', 'anchor', 'autoresize', 'autosave', 'codesample', 'directionality', 'emoticons', 'importcss', 'pagebreak', 'quickbars', 'save', 'visualchars', 'wordcount', 'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'preview', 'anchor', 'emoticons', 'help', 'nonbreaking', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'minitemplate'
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
       'image_advtab' => true,
       'menubar' => 'file edit view insert format tools table help',
       // formatselect = blocks
       'toolbar' => 'insertfile undo redo | accordion accordionremove | blocks fontfamily fontsize | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist | link unlink image | table media | lineheight outdent indent | forecolor backcolor removeformat | charmap emoticons | minitemplate code fullscreen preview | save print | pagebreak anchor codesample | ltr rtl',
       'font_size_formats' => '0.6665rem 0.8331rem 1.0rem 1.1664rem 1.4996rem 2.0rem 2.5rem 3.0rem',
       'toolbar_mode' => 'sliding',
       'deprecation_warnings' => false,
       'promotion' => false,
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
