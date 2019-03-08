<?php
   return array(
       'relative_urls' => false,
       'selector' => 'html-editable-selector',
       'language' => 'en',
       'inline' => true,
       'templates' => 'miniTemplates',
       'menubar' => false,
       'forced_root_block' => 'p',
       'paste_word_valid_elements'=> "p,b,strong,i,em,h1,h2,h3,h4",
       'cleanup' => false,
       'verify_html' => false,
       'plugins' => array(
            //[contextmenu, textcolor, colorpicker] this plugin is already built in the core editor as of TinyMCE v. 5
           'advlist autolink lists link paste image charmap preview anchor emoticons help hr nonbreaking',
           'searchreplace visualblocks code fullscreen',
           'insertdatetime media table template'
       ),
       'image_advtab' => true,
       'toolbar' => 'insertfile undo redo paste | formatselect | forecolor | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media template | code',
       'init_instance_callback' => 'tinyMceCleaner',
       'link_class_list' => array( 'class' => 'insert-edit-link' )
   );

# For Reference
/*return array(
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
