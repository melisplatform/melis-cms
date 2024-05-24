 var melisSearchPageTree = (function($, window) {
    // cache DOM
    var $body = $('body');
	    $body.on("click", "#leftSearchTreeView", function(e) {
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

				// toggle of lock and unlock icon
				if ( $pageLock.length ) {
					$pageLock.removeClass("fa-lock").addClass("fa-unlock");

					$this.attr("title", translations.tr_meliscms_menu_treeview_page_unlock);

					lockDragDropCmsMenuTreeView();
				}
				
				if ( $pageUnlock.length ) {
					$pageUnlock.removeClass("fa-unlock").addClass("fa-lock");

					$this.attr("title", translations.tr_meliscms_menu_treeview_page_lock);
				}
		});

		// lock on drag and drop treeview, automatic locked after 5 minutes
		function lockDragDropCmsMenuTreeView() {	
			var toLockTime 		= 300000, // 300000 5 minutes, 60000 1 minute
				intervalTime 	= 30000; // 30 seconds

				var checkUnlock = setInterval(function() {
					var $lockDragDropTreeView 	= $("#leftLockDragDropTreeView"),
						$pageUnlock 			= $lockDragDropTreeView.find(".fa.fa-unlock");

						if ( $pageUnlock.length ) {
							setTimeout(function() {
								$pageUnlock.removeClass("fa-unlock").addClass("fa-lock");

								$lockDragDropTreeView.attr("title", translations.tr_meliscms_menu_treeview_page_lock);
							}, toLockTime);

							clearInterval( checkUnlock );
						}
				}, intervalTime);
		}

	    // Filter Search
	    $(document).on("keyup", "input[name=left_tree_search]", function(event) {
	    	var keycode = (event.key ? event.key : event.which);
				
		    	if ( keycode === 'Enter' ) {
		    		startTreeSearch();
		    	}   
	    });

	    $body.on("click", "#leftResetTreeView", function(e) {
			$("input[name=left_tree_search]").val('');
			// $("#id-mod-menu-dynatree").fancytree("getTree"), deprecated
			var $tree = $.ui.fancytree.getTree("#id-mod-menu-dynatree");
				$tree.clearFilter();
				$tree.reload();
		});

	    function startTreeSearch() {
	    	var match 		= $("input[name=left_tree_search]").val().trim(),
				//tree 		= $("#id-mod-menu-dynatree").fancytree("getTree"),
				tree 		= $.ui.fancytree.getTree("#id-mod-menu-dynatree"),
				filterFunc 	= tree.filterNodes;
				
				if ( match.length ) {
					var opts 	= {},
						tmp 	= '';
					
						$(".meliscms-search-box.sidebar-treeview-search").append("<div class='melis-overlay-loading'></div>");

						tree.clearFilter();

						tree.getRootNode().visit(function(node) {
							if ( node.isExpanded() ) {
								node.resetLazy();
							}
						});
			
						// disable searchbar while searchig
						$("input[name=left_tree_search]").prop('disabled', true);
						var searchContainer = $("input[name=left_tree_search]").closest(".meliscms-search-box");
						
						$.ajax({
							type        : 'POST', 
							url         : 'melis/MelisCms/Page/searchTreePages',
							data		: {name: 'value', value: match},
							dataType    : 'json',
							encode		: true
						}).done(function(data) {
							// match value already trim()
							if(! Array.isArray(data) ) {
								searchContainer.append("<div class='melis-search-overlay'>"+translations.tr_meliscms_form_search_not_found+"</div>").hide().fadeIn(600);
								setTimeout(function() {
									$(".melis-search-overlay").fadeOut(600, function() {
										$(this).remove();
									});
									
									$("input[name=left_tree_search]").prop('disabled', false);
									$("input[name=left_tree_search]").trigger("focus");
									
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
										$("input[name=left_tree_search]").trigger("focus");
									});	
							}
						}).fail(function(xhr, textStatus, errorThrown) {
							alert( translations.tr_meliscore_error_message );
						});
				}
		}

	var $leftLockDragDropTreeView = $("#leftLockDragDropTreeView");
		$leftLockDragDropTreeView.attr("title", translations.tr_meliscms_menu_treeview_page_lock);

})(jQuery, window);