<?php
   return [
       'relative_urls' => false,
       'selector' => 'html-editable-selector',
       'language' => 'en',
       'inline' => true,
       //'templates' => 'miniTemplates',
       'mini_templates_url' => '/melis/MelisCore/MelisTinyMce/getTinyTemplates',
       'mini_template_preview_mode' => 'auto',
       'mini_template_preview_shell_url' => '/melis/MelisCore/MelisTinyMce/getMiniTemplatePreviewShell',
       'mini_template_site_module' => '',
       'mini_template_dropzone_selector' => '.melis-dragdropzone:first',
       'menubar' => false,
       'forced_root_block' => 'p',
       //'paste_word_valid_elements'=> "p,b,strong,i,em,h1,h2,h3,h4",
       'image_uploadtab' => false,
       'cleanup' => false,
       'verify_html' => false,
       // TinyMCE 6: verify_html=false only relaxes element/attribute rules (valid_elements='*[*]'),
       // NOT parent/child rules — a <link> inside a block <div> is still an invalid child and the
       // DomParser removes it on editor init / getContent(). AI mini-templates persist their stylesheet
       // as a <link> INSIDE the html-tag content (react-bridge.js assetTagsFor) so it reaches the front:
       // allow it, or the design vanishes from the canvas and is never published (Mantis #0010999).
       'valid_children' => '+body[link],+div[link]',
       'file_picker_types' => 'file image media',
       'file_picker_callback' => 'filePickerCallback',
       'images_upload_url' => '/melis/MelisCore/melisTinyMce/uploadImage',
       'plugins' => [
           'anchor', 'autoresize', 'autosave', 'emoticons', 'importcss', 'visualchars', 'wordcount', 'lists', 'advlist', 'autolink', 'link', 'image', 'charmap', 'emoticons', 'nonbreaking', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'minitemplate'
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
       'menubar' => 'edit view tools',
       'menu' => [
			'view' => [
				'title' => 'View',
				'items' => 'code | visualaid visualchars visualblocks'
			]
		],
       // formatselect = blocks
       'toolbar' => 'insertfile undo redo | blocks fontfamily fontsize | bold italic strikethrough underline | alignleft aligncenter alignright alignjustify | bullist numlist | link unlink image | table media | lineheight outdent indent | forecolor backcolor | charmap emoticons | minitemplate code fullscreen | anchor',
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
