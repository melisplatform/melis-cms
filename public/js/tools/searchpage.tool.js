 var melisSearchPageTree = (function($, window) {
    // cache DOM
    var $body = $('body');
	    $body.on("click", "#leftSearchTreeView", function(e){
	    	startTreeSearch();
		});

		// page lock icon locked/unlocked drag and drop of fancytree node
		$body.on("click", "#leftLockDragDropTreeView", function() {
			var $this 		= $(this),
				$resetTree 	= $("#leftResetTreeView"),
				$pageLock 	= $this.find(".fa.fa-lock"),
				$pageUnlock = $this.find(".fa.fa-unlock");

				// trigger refresh
				$resetTree.trigger("click");

				/* console.log("$pageLock: ", $pageLock.length);
				console.log("$pageUnlock: ", $pageUnlock.length); */

				// toggle of lock and unlock icon
				if ( $pageLock.length ) {
					$pageLock.removeClass("fa-lock").addClass("fa-unlock");
					//console.log("pageLocked");
					lockDragDropCmsMenuTreeView();
					//console.log("lockDragDropCmsMenuTreeView triggered! time 1 minute");
				}
				else if ( $pageUnlock.length ) {
					$pageUnlock.removeClass("fa-unlock").addClass("fa-lock");
					//console.log("pageUnlocked");
				}
		});

		// lock on drag and drop treeview, automatic locked after 5 minutes
		function lockDragDropCmsMenuTreeView() {	
			var toLockTime 		= 60000, //300000 5 minutes, 60000 1 minute
				intervalTime 	= 30000;

				var checkUnlock = setInterval(function() {
					var $lockDragDropTreeView 	= $("#leftLockDragDropTreeView"),
						$pageUnlock 			= $lockDragDropTreeView.find(".fa.fa-unlock");

						if ( $pageUnlock.length ) {
							$pageUnlock.attr("title", translations.tr_meliscms_menu_treeview_page_unlock);
							
							setTimeout(function() {
								$pageUnlock.removeClass("fa-unlock").addClass("fa-lock");
							}, toLockTime);

							clearInterval( checkUnlock );
						}
				}, intervalTime);
		}

		// run on document ready
		//lockDragDropCmsMenuTreeView();

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