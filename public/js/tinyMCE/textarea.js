 
	tinymce.init({
		relative_urls: false,
		selector: 'div.textarea-editable',
		language: locale,
		inline: true,
		moxiemanager_title: 'Media Library',		
		menubar: false,
		force_br_newlines : false,
		force_p_newlines : false,
		forced_root_block : '',
		cleanup : false,
		verify_html : false,
		plugins: [
	    'advlist autolink lists link image charmap print preview anchor',
	    'searchreplace visualblocks code fullscreen',
	    'insertdatetime media table contextmenu paste'
	    ],
	    toolbar: 'undo redo link unlink | code',

	    init_instance_callback : tinyMceCleaner,
	}); 