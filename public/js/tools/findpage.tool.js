 var melisLinkTree = (function($, window){
    
    // cache DOM
    var dataUrl,
        $body           = $('body'),
        $t              = tinymce.dom.DomQuery,
        $taux           = $t(".tox-tinymce-aux"),
        $mceLinkTree    = $t("#mce-link-tree");

    // Binding Events =================================================================================================================
    //$insertEditLink.on("click", checkBtn);
    //$editLink.on("click", checkBtn);

    // tox menu
    //$body.on("contextmenu", ".tox-collection__item--active", checkBtn);

    // CreateTreeModal
    $body.on("click", "#mce-link-tree", createTreeModal);

    // Filter Search
    $(document).on("keyup", "input[name=tree_search]", function(event) {
    	var keycode = (event.keyCode ? event.keyCode : event.which);
    	if(keycode == '13'){
    		startTreeSearch();
    	}   
    }).focus();
    
    $body.on("click", "#searchTreeView", function(e) {
    	startTreeSearch();
	});
    
    $body.on("click", "#resetTreeView", function(e) {
    	melisHelper.loadingZone($('.page-evolution-content'));
    	$("input[name=tree_search]").val('');
    	var tree = $("#find-page-dynatree").fancytree("getTree");
    	tree.clearFilter();
		$("#find-page-dynatree").fancytree("getRootNode").visit(function(node){
            node.setExpanded(false);
	    });
	    setTimeout(function(){
	    	melisHelper.removeLoadingZone($('.page-evolution-content'));
		}, 2000);
	});
        
    $body.on("click", "#generateTreePageLink", function() {
        melisCoreTool.pending('#generateTreePageLink');
        var id = $('#find-page-dynatree .fancytree-active').parent('li'). attr('id').split("_")[1];
        $.ajax({
            type        : 'GET', 
            url         : 'melis/MelisCms/Page/getPageLink',
            data        : {'idPage': id},
            dataType    : 'json',
            encode      : true
         }).success(function(data){
            dataUrl = data.link;
            showUrl();
            $("#id_meliscms_find_page_tree_container").modal("hide");
         }).error(function(){
             console.log('failed');
         });
        melisCoreTool.done('#generateTreePageLink');

    });
    
    $body.on("click", "#generateTreePageId", function() {
    	melisCoreTool.pending('#generateTreePageLink');
    	var id = $('#find-page-dynatree .fancytree-active').parent('li'). attr('id').split("_")[1];
    	var inputId = $('#generateTreePageId').data('inputid');
    	$(inputId).val(id);
    	
    	$('#id_meliscms_input_page_tree_container').modal("hide");
    	
    	melisCoreTool.done('#generateTreePageLink');
    });

    function startTreeSearch() {
    	var match = $("input[name=tree_search]").val();
		var tree = $("#find-page-dynatree").fancytree("getTree");
		var filterFunc = tree.filterNodes;
		var opts = {};		
		var tmp = '';
		 
		 tree.clearFilter();
		 $("#find-page-dynatree").fancytree("getRootNode").visit(function(node){
	        node.resetLazy();
	     });
		 $("input[name=tree_search]").prop('disabled', true);
         var searchContainer = $("input[name=tree_search]").closest(".meliscms-search-box");
         searchContainer.addClass("searching");

		 $.ajax({
	        type        : 'POST', 
	        url         : 'melis/MelisCms/Page/searchTreePages',
	        data		: {name: 'value', value: match},
	        dataType    : 'json',
	        encode		: true
	     }).success(function(data){
            if(!$.trim(data)) {
                searchContainer.append("<div class='melis-search-overlay'>Not Found</div>").hide().fadeIn(600);
                setTimeout(function() {
                    $(".melis-search-overlay").fadeOut(600, function() {
                        $(this).remove();
                    });
                    $("input[name=tree_search]").prop('disabled', false);
                    $("input[name=tree_search]").focus();
                }, 1000);
            } else {
    	    	var arr = $.map(data, function(el) { return el });

        		tree.loadKeyPath(arr, function(node, status){
                    if(!node.isVisible()) {
            			switch( status ) {
        				case "loaded":							
        					node.makeVisible();		
        					break;
        				case "ok":
        					node.makeVisible();
        					break;
            			}
                    }
        			filterFunc.call(tree, match, opts);					
    			}).done(function(){
    				$("input[name=tree_search]").prop('disabled', false);
                    searchContainer.removeClass("searching");
    			});					
            }
	     }).error(function(){
	    	 console.log('failed');
	     });
    }
    
    function showUrl() {
    	var inputBox = $('.melis-iframe').contents().find('#mce-link-tree').parent().find('input').val(dataUrl);
                       $(".mce-floatpanel.mce-window").find('#mce-link-tree').parent().find('input').val(dataUrl);
    }

    function checkBtn() {
        var $aux            = $t(".tox-tinymce-aux"),
            $dialog         = $aux.find(".tox-dialog"),
            $conHStacks     = $dialog.find(".tox-form__controls-h-stack"),
            $inputUrl       = $conHStacks.find(".tox-control-wrap input"),
            $urlBtn         = $conHStacks.find("#mce-link-tree");

            if ( !$urlBtn.length ) {
                if ( $conHStacks.length ) {
                    $conHStacks.append('<button title="Site tree view" id="mce-link-tree" class="mce-btn mce-open" style="width: 34px; height: 34px;"><i class="icon icon-sitemap fa fa-sitemap" style="font-family: FontAwesome; position: relative; font-size: 16px; display: block; text-align: center;"></i></button>');
                }
            }
    }

    /*function addTreeBtnMoxie() {
        console.log("addTreeBtnMovie");

        var //$melisIframe    = $body.find(".melis-iframe"),
            //$melisIframe    = $(window).parent,
            //$dialog         = $(".melis-iframe").contents().find(".tox-dialog"),
            $aux            = $t(".tox-tinymce-aux"),
            $dialog         = $aux.find(".tox-dialog"),
            $conHStacks     = $dialog.find(".tox-form__controls-h-stack");

            if ( $conHStacks.length ) {
                $conHStacks.append('<button title="Site tree view" id="mce-link-tree" class="mce-btn mce-open" style="width: 34px; height: 34px;"><i class="icon icon-sitemap fa fa-sitemap" style="font-family: FontAwesome; position: relative; font-size: 16px; display: block; text-align: center;"></i></button>');
            }
    }*/

    function createTreeModal() {
        // initialation of local variable
        zoneId = 'id_meliscms_find_page_tree';
        melisKey = 'meliscms_find_page_tree';
        modalUrl = 'melis/MelisCms/Page/renderPageModal';

        // requesitng to create modal and display after
        if($('#id_meliscms_find_page_tree_container').length){
        	$('#id_meliscms_find_page_tree_container').parent().remove();
        }
        
        window.parent.melisHelper.createModal(zoneId, melisKey, false, {}, modalUrl, function() {
        });
        
        $("#mce-link-tree").closest('.mce-panel').css('z-index', 1049);
        $("#mce-modal-block").css('z-index', 1048);
    }
    
    // used in regular form buttons
    function createInputTreeModal(id) {
    	
    	// initialation of local variable
    	zoneId = 'id_meliscms_input_page_tree';
    	melisKey = 'meliscms_input_page_tree';
    	modalUrl = 'melis/MelisCms/Page/renderPageModal';
    	
    	// requesitng to create modal and display after
    	if($('#id_meliscms_input_page_tree_container').length){
    		$('#id_meliscms_input_page_tree_container').parent().remove();
    	}
    	
    	window.parent.melisHelper.createModal(zoneId, melisKey, false, {'pageTreeInputId' : id}, modalUrl, function() {
    	});
    	
    }

    function selectedNodes() {
        var title = $(this).closest('li').attr('id');
            $('#statusLine').append(title);
    }

    function findPageMainTree() {
        $("#find-page-dynatree").fancytree({
            extensions: ["filter"],
            keyboard: true,
            generateIds: true, // Generate id attributes like <span id='fancytree-id-KEY'>
            idPrefix: "pageid_", // Used to generate node id´s like <span id='fancytree-id-<key>'>
            source: {
                url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id',
                cache: true
            },         
            lazyload: function(event, data) {
              // get the page ID and pass it to lazyload
              var pageId = data.node.data.melisData.page_id;
              data.result = { 
                      url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId='+pageId,
                      data: {
                          mode: 'children',
                          parent: data.node.key
                      },
                      cache: false
              }
            },
            renderNode: function (event, data) {
                // removed .fancytree-icon class and replace it with font-awesome icons
                $(data.node.span).find('.fancytree-icon').addClass(data.node.data.iconTab).removeClass('fancytree-icon');
                
                if(data.node.statusNodeType !== 'loading'){
                    
                    if( data.node.data.melisData.page_is_online === 0){
                        //$(data.node.span).find('.fancytree-title, .fa').css("color","#000");     
                    }
                    
                    if( data.node.data.melisData.page_has_saved_version === 1){
                        //check if it has already 'unpublish' circle - avoid duplicate circle bug
                        if( $(data.node.span).children("span").hasClass("unpublish") == false  ){
                            $(data.node.span).find('.fancytree-title').before("<span class='unpublish'></span>");
                        }
                    } 
                }   
            },
            filter: {
                autoApply: true,   // Re-apply last filter if lazy data is loaded
                autoExpand: false, // Expand all branches that contain matches while filtered
                counter: true,     // Show a badge with number of matching child nodes near parent icons
                fuzzy: false,      // Match single characters in order, e.g. 'fb' will match 'FooBar'
                hideExpandedCounter: true,  // Hide counter badge if parent is expanded
                hideExpanders: false,       // Hide expanders if all child nodes are hidden by filter
                highlight: true,   // Highlight matches by wrapping inside <mark> tags
                leavesOnly: false, // Match end nodes only
                nodata: true,      // Display a 'no data' status node if result is empty
                mode: "hide"       // Grayout unmatched nodes (pass "hide" to remove unmatched node instead)
              },
        });
        

    }

    return {
        createTreeModal         :       createTreeModal,
        createInputTreeModal	: 		createInputTreeModal,
        findPageMainTree        :       findPageMainTree,
        checkBtn                :       checkBtn,
        showUrl                 :       showUrl
    }

})(jQuery, window);
