 var melisSearchPageTree = (function($, window){

    // cache DOM
    var $body = $('body');

	    $body.on("click", "#leftSearchTreeView", function(e){
	    	startTreeSearch();
		});

	    // Filter Search
	    $(document).on("keyup", "input[name=left_tree_search]", function(event) {
	    	var keycode = (event.keyCode ? event.keyCode : event.which);
		    	if ( keycode == '13' ) {
		    		startTreeSearch();
		    	}   
	    });

	    $body.on("click", "#leftResetTreeView", function(e) {
	    	$("input[name=left_tree_search]").val('');
	        $("#id-mod-menu-dynatree").fancytree("destroy");
	        mainTree();
		});

	    function startTreeSearch() {
	    	var match 		= $.trim( $("input[name=left_tree_search]").val() ),
				tree 		= $("#id-mod-menu-dynatree").fancytree("getTree"),
				filterFunc 	= tree.filterNodes;

	    	if ( match.length ) {
				var opts 	= {},
					tmp 	= '';
				
	            $(".meliscms-search-box.sidebar-treeview-search").append("<div class='melis-overlay-loading'></div>");

	            tree.clearFilter();

	            $("#id-mod-menu-dynatree").fancytree("getRootNode").visit(function(node){
	                if(node.isExpanded() ) {
	                    node.resetLazy();
	                }
	            });
	 
			     // disable searchbar while searchign
				$("input[name=left_tree_search]").prop('disabled', true);
	            var searchContainer = $("input[name=left_tree_search]").closest(".meliscms-search-box");
	            
				$.ajax({
			        type        : 'POST', 
			        url         : 'melis/MelisCms/Page/searchTreePages',
			        data		: {name: 'value', value: match},
			        dataType    : 'json',
					encode		: true
			    }).done(function(data) {
			    	if(!$.trim(data)) {
						searchContainer.append("<div class='melis-search-overlay'>"+translations.tr_meliscms_form_search_not_found+"</div>").hide().fadeIn(600);
						setTimeout(function() {
							$(".melis-search-overlay").fadeOut(600, function() {
								$(this).remove();
							});
							
							$("input[name=left_tree_search]").prop('disabled', false);
							$("input[name=left_tree_search]").focus();
							
							$(".meliscms-search-box.sidebar-treeview-search .melis-overlay-loading").remove();
						}, 1000);
					} else {
						var arr = $.map(data, function(el) { return el });
						tree.loadKeyPath(arr, function(node, status) {
							switch( status ) {
								case "loaded":	
									node.makeVisible();     
									break;
								case "ok":    
									node.makeVisible();     
									break;
							}

							filterFunc.call(tree, match, opts);
						}).done(function(){
							$("input[name=left_tree_search]").prop('disabled', false);
							$(".meliscms-search-box.sidebar-treeview-search .melis-overlay-loading").remove();
							$("input[name=left_tree_search]").focus();
						});	
					}
			    }).fail(function(xhr, textStatus, errorThrown) {
			    	alert( translations.tr_meliscore_error_message );
			    });
	    	}
	    }
})(jQuery, window);