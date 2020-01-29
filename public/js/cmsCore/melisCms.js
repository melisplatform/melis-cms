// Melis Cms Functionalities 
var melisCms = (function(){
	
	// CACHE SELECTORS
	var $body 	  		= $("body"),
		$document 		= $("document"),
		$openedPageIds 	= [];
	
	// ---=[ BUG FIX ] =---  TINYMCE POPUP MODAL FOCUS 
	var windowOffset;

	window.scrollToViewTinyMCE = function(dialogHeight, iframeHeight){
		// window scroll offest
		windowOffset = $(window).scrollTop();
		
		if( dialogHeight && iframeHeight ){
			var scrollTop = (iframeHeight /2 ) - (dialogHeight);
				$("html, body").animate({scrollTop: scrollTop }, 300);
		}
		else {
			return windowOffset;
		}
	}

	window.scrollOffsetTinyMCE = function(){
		return windowOffset;
	}

    $(".sub-section").on("click", function() {
    	var $this = $(this);

	        if ( $this.next(".cms-next").is(":visible") ) {
	            $this.next(".cms-next").hide();
	        } else {
	            $this.next(".cms-next").show();
	        }
    });

	$body.on("click", ".tox-tbtn", function(){
		var mcePopUp = $("#mce-modal-block").length;
		
			if( mcePopUp ) {
				if( $("iframe.melis-iframe").length ) {
					// iframe offset top
					$("iframe.melis-iframe").position().top;
					
					// iframe height
					$("iframe.melis-iframe").height();
					
					// window height
					var windowHeight = screen.height;
					
					// body scroll top position
					bodyOffsetTop = $(window)[0].scrollHeight;
					
					// dialog box height
					var dialogHeight = $(".mce-window").outerHeight();
					
					var dialogTop = (bodyOffsetTop + windowHeight) - dialogHeight;
					
					//console.log("bodyOffsetTop = " + bodyOffsetTop);
					//console.log("windowHeight = " + windowHeight);
					//console.log("dialogHeight = " + dialogHeight);
					//console.log("has popup = "+ dialogTop);
					$(".mce-floatpanel.mce-window").css("top", dialogTop);
					$("html, body").animate({scrollTop: dialogTop }, 300);
				}
				else {
					$("#mce-modal-block").css('z-index',1049);
					$(".mce-floatpanel.mce-window").css('z-index', 1050);
				}
			}
			else{
				//console.log("no popup");
			}
	});
	
    // HIGHLIGHT ERROR COLORS
	function colorRedError(success, errors, divContainer){
		// if all form fields are error color them red
		if ( success === 0 ) {
			$("#" + divContainer + " .form-group label").css("color","#686868");
			$.each( errors, function( key, error ) { 
				$("#" + divContainer + " .form-control[name='"+key +"']").prev("label").css("color","red");
			});
		}
		// remove red color for correctly inputted fields
		else{
			$("#" + divContainer + " .form-group label").css("color","#686868");
		}
	} 
	
	// NEW PAGE
	function newPage() {
		console.log("newPage");
   	  	//close page creation tab and open new one (in case if its already open - updated parent ID), fa-file-text-o
		var pageID = $(this).data('pagenumber');
	   	  	melisHelper.tabClose('0_id_meliscms_page');
	   	  	melisHelper.tabOpen( translations.tr_meliscms_page_creation, 'fa-file-o', '0_id_meliscms_page', 'meliscms_page_creation',  { idPage: 0, idFatherPage: pageID } );
	}
	
	// SAVE PAGE
	function savePage(idPage) {
		var pageNumber 		= (typeof idPage === 'string') ? idPage :  $(this).data("pagenumber"),
			fatherPageId 	= $(this).data("fatherpageid"); 
		
		// convert the serialized form values into an array
		var datastring = $("#" + pageNumber + "_id_meliscms_page form").serializeArray();
		
			if ( $("#" + pageNumber + "_id_page_taxonomy").length ) {
				var pageTags = $("#" + pageNumber + "_id_page_taxonomy").data('tags').toString();
				
					// push tags value into the array
					datastring.push({
						name: 'page_taxonomy',
						value: pageTags
					});
			}
		
			// serialize the new array and send it to server
			datastring = $.param(datastring);

			$.ajax({
				type        : 'POST', 
		        url         : '/melis/MelisCms/Page/savePage?idPage=' + pageNumber +'&fatherPageId='+fatherPageId,
		        data        : datastring, 
		        dataType    : 'json',
				encode		: true
			}).done(function(data) {
				if ( data.success === 1 ) {
					// reload and expand the treeview
					refreshTreeview(data.datas.idPage);
		
					// call melisOkNotification 
					melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
					
					// update red colored label when successful
					colorRedError(data.success, data.errors, data.datas.item_zoneid);
					
					// get page creation ID
					var pageCreationId = data.datas.item_zoneid;
					
					// IF ITS PAGE CREATION
					if ( pageCreationId === '0_id_meliscms_page') {
						
						// close page creation page and tab
						melisHelper.tabClose(pageCreationId);
						
						//remove first char on the zoneID and replace with newly create id
						var newPageZoneId = data.datas.idPage + pageCreationId.substring(1, pageCreationId.length);
					
						//open newly opened page
						melisHelper.tabOpen( data.datas.item_name, data.datas.item_icon, newPageZoneId, data.datas.item_melisKey,  { idPage: data.datas.idPage } );	
					} else {
						// reload the preview in edition tab
						melisHelper.zoneReload(pageNumber+'_id_meliscms_page','meliscms_page', {idPage:pageNumber});
					}	    	
				}
				else {
					// error modal
					melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
					
					//color the error field in red
					colorRedError(data.success, data.errors, data.datas.item_zoneid);
				}
				
				// update flash messenger values
				melisCore.flashMessenger();

			}).fail(function(xhr, textStatus, errorThrown) {
				alert( translations.tr_meliscore_error_message );
			});
	}
	
	// PUBLISH PAGE 
	function publishPage(idPage) {
		var pageNumber = (typeof idPage === 'string') ? idPage :  $(this).data("pagenumber"); 
		
		// convert the serialized form values into an array
		var datastring = $("#" + pageNumber + "_id_meliscms_page form").serializeArray();
		
			if ( $("#" + pageNumber + "_id_page_taxonomy").length ) {
				var pageTags = $("#" + pageNumber + "_id_page_taxonomy").data('tags').toString();
				
				// push tags value into the array
				datastring.push({
					name: 'page_taxonomy',
					value: pageTags
				});
			}
		
			// serialize the new array and send it to server
			datastring = $.param(datastring);
			
			$.ajax({
				type        : 'POST', 
		        url         : '/melis/MelisCms/Page/publishPage?idPage=' + pageNumber,
		        data        : datastring,
		        dataType    : 'json',
				encode		: true
			}).done(function(data) {
				if ( data.success === 1 ) {
					// reload and expand the treeview
					refreshTreeview(data.datas.idPage);
					
					// set the online/offline button to 'online'
					$('.page-publishunpublish[data-pagenumber="'+pageNumber+'"]').bootstrapSwitch('setState', true, true);
		
					// call melisOkNotification 
					melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
					
					// update red colored label when successful
					colorRedError(data.success, data.errors, data.datas.item_zoneid);
					
					// update flash messenger values
					melisCore.flashMessenger();	
					
					// update page name tabname if page name is changed
					$("#" + data.datas.item_zoneid + " .page-title h1:not('span')").text(data.datas.item_name);
					$(".tabsbar a[data-id='" + data.datas.item_zoneid + "'] .navtab-pagename").text(data.datas.item_name);
					
					// hide the saved version text of the page once its published
					$("#" + data.datas.item_zoneid + " .page-title .saved-version-notif").fadeOut();
					
					// reload the preview in edition tab 
					melisHelper.zoneReload(pageNumber+'_id_meliscms_page','meliscms_page', {idPage:pageNumber});

					$openedPageIds.push(data.datas.idPage);
				}
				else {
					// error modal
					melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
					
					//color the error field in red
					colorRedError(data.success, data.errors, data.datas.item_zoneid);
				}
				
				// update flash messenger values
				melisCore.flashMessenger();
			}).fail(function(xhr, textStatus, errorThrown) {
				alert( translations.tr_meliscore_error_message );
			});
	}
		
	// UNPUBLISH PAGE
	function unpublishPage(idPage) {
		var pageNumber = (typeof idPage === 'string') ? idPage :  $(this).data("pagenumber");
		
			$.ajax({
				type        : 'GET', 
			    url         : '/melis/MelisCms/Page/unpublishPage?idPage='+pageNumber,
			    dataType    : 'json',
				encode		: true
			}).done(function(data) {
				if ( data.success === 1 ) {
					// reload and expand the treeview
					refreshTreeview(pageNumber);
					
					// call melisOkNotification 
					melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
					
					// update flash messenger values
					melisCore.flashMessenger();

					$openedPageIds.push(pageNumber);
				}
				else {
					// show error modal
					melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
				}
				
				// update flash messenger values
				melisCore.flashMessenger();
				
				// reload the preview in edition tab
				melisHelper.zoneReload(pageNumber+'_id_meliscms_page','meliscms_page', {idPage:pageNumber});
			}).fail(function(xhr, textStatus, errorThrown) {
				alert( translations.tr_meliscore_error_message );
			});
	}
	
	function clearPage() {
		var data 		= $(this).data(),
			idPage 		= data.pagenumber,
			zoneId 		= activeTabId,
			confirmMsg 	= data.confirmmsg;
		
	  	  	// delete page confirmation 
	  	  	melisCoreTool.confirm(
	  			translations.tr_meliscms_page_action_clear,
				translations.tr_meliscms_menu_cancel,
				translations.tr_meliscms_delete_saved_page_title, 
				confirmMsg, 
				function() {
	  				// check if node has children if TRUE then cannot be deleted
	  				$.ajax({
	  					type        : 'GET',
	  					url         : '/melis/MelisCms/Page/clearSavedPage?idPage='+idPage,
						encode		: true
					}).done(function(data) {
						if ( data.success === 1 ) {
							// notify deleted page
							melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
					   
							// reload and expand the treeview
							melisCms.refreshTreeview(idPage);
							
							// update flash messenger values
							melisCore.flashMessenger();	
							
							// reload the preview in edition tab 
							melisHelper.zoneReload(idPage+'_id_meliscms_page','meliscms_page', {idPage:idPage});
						}
						else {
							melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
						}
					}).fail(function(xhr, textStatus, errorThrown) {
						alert( translations.tr_meliscore_error_message );
					});
			});
	}
	
	// Delete Page
	function deletePage() {
		var data 	= $(this).data(),
			idPage 	= data.pagenumber,
			zoneId 	= activeTabId,
			attr 	= $(this).attr('disabled');

		/* var parentNode = ( node.key == 'root_1') ? -1 : node.key ; */
		if ( typeof attr === typeof undefined || attr === false ) {
	  	  	// delete page confirmation 
	  	  	melisCoreTool.confirm(
	  			translations.tr_meliscms_menu_delete,
				translations.tr_meliscms_menu_cancel,
				translations.tr_meliscms_delete_confirmation, 
				translations.tr_meliscms_delete_confirmation_msg, 
				function() {
					// reload and expand the treeview
					melisCms.refreshTreeview(idPage);
	  				
					// check if node has children if TRUE then cannot be deleted					
	  				$.ajax({
	  					url         : '/melis/MelisCms/Page/deletePage?idPage='+idPage,
						encode		: true
	  				}).done(function(data) {
	  					if ( data.success === 1 ) {
							
							//close the page 
							melisHelper.tabClose(zoneId);
													
							// notify deleted page
							melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
							
							// update flash messenger values
							melisCore.flashMessenger();				  
						}
						else {
							melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
						}
	  				}).fail(function(xhr, textStatus, errorThrown) {
	  					alert( translations.tr_meliscore_error_message );
	  				});
			});
		}		
	}
	
	/* //Deactivating page edition action buttons
    function disableCmsButtons(id) {
        $("div.make-switch label on, off").parent().css("z-index", -1).parents("div.make-switch").css("opacity", 0.5);
        $("#"+id+"_id_meliscms_page_action_tabs").addClass('relative').prepend("<li class='btn-disabled'></li>");
    }
    
    //Activating page edition action buttons
    function enableCmsbuttons(id) {
        $("#"+id+"_action_tabs").removeClass('relative');
        $("#"+id+"_action_tabs li.btn-disabled").remove();
        
        $("div.make-switch label on, off").parent().css("z-index", 1).parents("div.make-switch").css("opacity", 1);
    }*/
	
	// RELOAD THE TREEVIEW AND SET A NODE PAGE ACTIVE
	function refreshTreeview(pageNumber, self) {
		optionalArg = (typeof self === 'undefined') ? 0 : self;

	  	$.ajax({
  	        url         : '/melis/MelisCms/TreeSites/getPageIdBreadcrumb?idPage='+pageNumber+'&includeSelf='+optionalArg,
  	        encode		: true,
			dataType    : 'json'
  	    }).done(function(data) {
  	    	//process array to add to make this format '1/3/5/6...'
	        var newData 	= [],
	        	parentNode;	

		        $.each( data, function( key, value ) {
		  	        newData.push("/"+value);
		  	        if ( key === 0 ) {
		  	           parentNode = value;
		  	        }
		        });

		        newData = newData.toString();
		        newData = newData.replace(/,/g,'');
	        
	        var tree = $("#id-mod-menu-dynatree").fancytree("getTree");
				
	    	    // reload tree pages
		    	tree.reload({
		    		url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id'
		    	}).done(function(){
				    tree.loadKeyPath(newData, function(node, status){
		        	    if (status == "ok"){
		        	        node.setActive(true).done(function(){
		        	    	   node.setExpanded(true);
		        	        });
		        	    }
		        	}).done(function(){
		        		tree.clearFilter();
		        		// remove duplicated brach of the tree while rapidly refreshing the tree [ plugin bug fix ]
		        		if ( $("#id-mod-menu-dynatree .ui-fancytree > li:last-child").hasClass("fancytree-lastsib") === false){
							$("#id-mod-menu-dynatree .ui-fancytree > li:last-child").remove();
						}
		        	});   
			    });
  	    }).fail(function(xhr, textStatus, errorThrown) {
  	    	alert( translations.tr_meliscore_error_message );
  	    });
	}

	// DISPLAY SETTING FOR PAGES
	function displaySettings() {
		var displayWidth 	= 0,
    		displaySettings = $(this).data("display");
    	
	    	$(".displaysettingsicon span.fa").removeClass();
	    	$(".displaysettingsicon span:first-child").addClass(displaySettings);
	    	
	    	if ( displaySettings === 'fa fa-desktop' ) {
	    		displayWidth = '100%';
	    	}
	    	else if ( displaySettings === 'fa fa-tablet' ) {
	    		displayWidth = '980px';
	    	}
	    	else {
	    		displayWidth = '480px';
	    	}
	    	
	    	$(this).parents(".page-head-container").next(".page-content-container").find(".melis-iframe").animate({
	    		width: displayWidth
	    	}, 300, function(){
	    		// temporarily give the iframe height so it doensn't look bad when it animates the width
	    		$("#"+ activeTabId + " .melis-iframe").css('height','1000px');
	    		
	    		// give iframe the calculated height based from the content
	    		var iHeight = $("#"+ activeTabId + " .melis-iframe").contents().height()+50;  
	    		$("#"+ activeTabId + " .melis-iframe").css("height", iHeight);
	    	});
	}
	
	// PUBLISH - UNPUBLISH TOGGLE BUTTON
	function publishUnpublish(e, datas) {
		var pageNumber = $(this).data('pagenumber').toString();
    	
	    	if ( datas.value === true ) {
	    		publishPage(pageNumber);
	    	}
	    	else {
	    		unpublishPage(pageNumber);
	    	}
	}
	
	// INPUT CHAR COUNTER IN SEO TAB
	function charCounter(event) {
		var charLength = $(this).val().length;
		var prevLabel = $(this).prev('label');
		var limit = event.data.limit;
		
		if ( prevLabel.find('span').length ) {
			
			if ( charLength === 0 ) {
				prevLabel.removeClass('limit');
				prevLabel.find('span').remove();
			}
			else{
				prevLabel.find('span').html('<i class="fa fa-text-width"></i>(' + charLength + ')');

				if( charLength > limit ){
					prevLabel.addClass('limit');
					prevLabel.find('span').addClass('limit');
				}
				else{
					prevLabel.removeClass('limit');
					prevLabel.find('span').removeClass('limit');
				}
			}
		}
		else {
			if ( charLength !== 0 ) {
				prevLabel.find(".label-text").append("<span class='text-counter-indicator'><i class='fa fa-text-width'></i>(" + charLength + ")</span>");

				if( charLength > limit ){
					prevLabel.addClass('limit');
					prevLabel.find('span').addClass('limit');
				}
			}
		}
	}
	
	// CMS tab events (Edition, Properties, SEO . . .) 
	function cmsTabEvents() {
		// trigger keyup on SEO tabs
		$("form[name='pageseo'] input[name='pseo_meta_title']").trigger('keyup');
		$("form[name='pageseo'] input[textarea='pseo_meta_description']").trigger('keyup');
		
		// give iframe the calculated height based from the content
		var iHeight = $("#"+ activeTabId + " .melis-iframe").contents().height()+20;  
		$("#"+ activeTabId + " .melis-iframe").css("height", iHeight);
	}

	// REFRESH PAGE TAB (historic, versionining etc)
    function refreshPageTable() {
    	var zoneParent 	= $(this).parents(".melis-page-table-cont"),
    		zoneId 		= zoneParent.attr("id"),
    		melisKey 	= zoneParent.data("meliskey"),
    		pageId 		= zoneParent.data("page-id");

    		melisHelper.zoneReload(zoneId, melisKey, { idPage: pageId }); 
    }
    
    function disableCmsButtons(id) {
        $("#"+id+"_id_meliscms_page .page-publishunpublish").append("<div class='overlay-switch' style='width: 100%;height: 100%;" +
            "position: absolute;top: 0;" +
            "left: 0;z-index: 99999999;" +
            "cursor: wait; '></div>");
        $("#"+id+"_id_meliscms_page_action_tabs").addClass('relative').prepend("<li class='btn-disabled'></li>");
    }
    
    function enableCmsButtons(id) {
        $("#"+id+"_id_meliscms_page_action_tabs").removeClass('relative');
        $("#"+id+"_id_meliscms_page_action_tabs li.btn-disabled").remove();
        $("#"+id+"_id_meliscms_page .overlay-switch").remove();
    }
	
    // IFRAME HEIGHT CONTROLS (for onload, displaySettings & sidebar collapse)
    function iframeLoad(id) {
    	var height = $("#"+ id + "_id_meliscms_page .melis-iframe").contents().height();

	    	$("#"+ id + "_id_meliscms_page .melis-iframe").css("height", height);
	    	$("#"+ id + "_id_meliscms_page .melis-iframe").css("min-height", "700px");  

			// Activating page edition button action
			enableCmsButtons(id);

			//clear the opened tab pages id
			$openedPageIds = [];

	        // PAGE ACCESS user rights checking
	        $.ajax({
	            url         : '/melis/MelisCms/TreeSites/canEditPages',
				encode		: true
	        }).done(function(data) {
	        	var tree = $("#id-mod-menu-dynatree").fancytree("getTree");
	    	
					// has no access
					if(data.edit === 0){
						$(".meliscms-search-box.sidebar-treeview-search").hide();
						$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='no-access'>" + translations.tr_meliscms_no_access + "</span></div>");
					}
					// has access
					else{
						if(tree.count() === 0){
						$(".meliscms-search-box.sidebar-treeview-search").hide();
						$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>"+ translations.tr_meliscms_create_page +"</span></div>");
						}
						else{
						$(".meliscms-search-box.sidebar-treeview-search").show();
							$("#id-mod-menu-dynatree .create-newpage").remove();
						}
					}
	        }).fail(function(xhr, textStatus, errorThrown) {
	        	alert( translations.tr_meliscore_error_message );
	        });

	        // SAVE user rights checking
	        $.ajax({
	            url         : '/melis/MelisCms/Page/isActionActive?actionwanted=save',
				encode		: true
	        }).done(function(data) {
	        	if(data.active === 0){
					$("body").addClass('disable-create');
				}
				else{
					$("body").removeClass('disable-create');
				}
	        }).fail(function(xhr, textStatus, errorThrown) {
	        	alert( translations.tr_meliscore_error_message );
	        });

	        // DELETE user rights checking
	        $.ajax({
	            url         : '/melis/MelisCms/Page/isActionActive?actionwanted=delete',
				encode		: true
	        }).done(function(data) {
	        	if(data.active === 0){
					$("body").addClass('disable-delete');
				}
				else{
					$("body").removeClass('disable-delete');
				}
	        }).fail(function(xhr, textStatus, errorThrown) {
	        	alert( translations.tr_meliscore_error_message );
	        });
    }


	/**
	 * Callback function when opening a page
	 *
	 * Add another parameter if needed
	 * @param pageId
	 */
	function pageTabOpenCallback(pageId) {
		//store the opened pages id
		$openedPageIds.push(pageId);

		//add another statement below if needed
	}

	/**
	 * fix force responsive for history and versioning tab table not responsive
	 * issue: http://mantis.melistechnology.fr/view.php?id=4447
	 */
	function showHistoryVersioningTableResponsive() {
		var $this 		= $(this),
			href 		= $this.attr("href"),
			$tabContent = $(href);

			$tabContent.find(".melis-refreshPageTable").trigger("click");
	}

	// WINDOW SCROLL FUNCTIONALITIES ========================================================================================================
	if( melisCore.screenSize >= 768) {
		jQuery(window).scroll(function() {
	        // sticky page actions			
			var sidebarStatus 	= $("body").hasClass("sidebar-mini"),
				sidebarWidth 	= 0;

				if( !sidebarStatus ) {
					sidebarWidth = $( "#id_meliscore_leftmenu" ).outerWidth();
				}
			
			var activateFixed = $("#"+ activeTabId + " div.page-title").outerHeight();

	        if( (jQuery(window).scrollTop() > activateFixed ) && ( melisCore.screenSize > 1120) ){

	           $("#"+ activeTabId + " .page-head-container").css('padding-top', '72px');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").addClass('sticky-pageactions');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").css({"width": $body.width() - sidebarWidth,"left": sidebarWidth});
	        }
	        else{
	           $("#"+ activeTabId + " .page-head-container").removeAttr("style");
	           $("#"+ activeTabId + " .page-head-container > .innerAll").removeClass('sticky-pageactions');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").removeAttr("style");
	        }    
	    });

		// sticky page actions IE fix
	    $("body.show-breadcrumb.show-nav-tabs").scroll(function() {        

			// sticky page actions IE
			var sidebarStatus = $("body").hasClass("sidebar-mini");
			var sidebarWidth = 0;
			if( !sidebarStatus ){
				sidebarWidth = $( "#id_meliscore_leftmenu" ).outerWidth();
			}
			
			// var currentTitle = Math.abs($("#"+ activeTabId + " div.page-title").offset().top);
			var currentTitle = $("#"+ activeTabId + " div.page-title");
			var pageTitle = $("#"+activeTabId+" div[data-meliskey='meliscms_pagehead_title']");

			if(currentTitle.length) {
				currentTitle = Math.abs(currentTitle.offset().top);
			}

			if(pageTitle.length) {
				pageTitle = $("#"+activeTabId+" div[data-meliskey='meliscms_pagehead_title']").offset().top;
			}


			var activateFixed = $("#"+ activeTabId + " div.page-title").outerHeight();
	        if( (pageTitle <= 0) && ( melisCore.screenSize > 1120) ){
	           $("#"+ activeTabId + " .page-head-container").css('padding-top', '61px');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").addClass('sticky-pageactions');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").css({"width": $body.width() - sidebarWidth,"left": sidebarWidth});
	        }
	        else{
	           $("#"+ activeTabId + " .page-head-container").removeAttr("style");
	           $("#"+ activeTabId + " .page-head-container > .innerAll").removeClass('sticky-pageactions');
	           $("#"+ activeTabId + " .page-head-container > .innerAll").removeAttr("style");
	        }
	    });
	}

	// EVENT BINDINGS ===============================================================================================================
	
	// page actions
	$body.on("click", ".melis-newpage", newPage);
	$body.on("click", ".melis-savepage", savePage);
	$body.on("click", ".melis-clearpage", clearPage);
	$body.on("click", ".melis-publishpage", publishPage);
	$body.on("click", ".melis-unpublishpage", unpublishPage);
	$body.on("click", ".melis-deletepage", deletePage);
	/* $body.on("load", "iframe", saveToSession); */
	
	// page display settings desktop - tablet - mobile
    $body.on("click", ".display-settings", displaySettings);

    // new toggle button for 'publish' and 'unpublish'
    $body.on('switch-change', '.page-publishunpublish', publishUnpublish);
    
    // char counter in seo title
    $body.on("keyup keydown change", "form[name='pageseo'] input[name='pseo_meta_title']" , {limit: 70}, charCounter);
    
    // char counter in seo description
    $body.on("keyup keydown change", "form[name='pageseo'] textarea[name='pseo_meta_description']", {limit: 160}, charCounter);
    
    // main tab click event (edition, properties etc..)
    $body.on("shown.bs.tab", '.page-content-container .widget-head.nav ul li a', cmsTabEvents);
    
    // refresh page tab (historic, versionining etc)
	$body.on("shown.bs.tab", '.melis-refreshPageTable', refreshPageTable );
	
	// click on history tab / for newsletter dataTables
	$body.on("click", ".page-content-container .widget-head.nav ul li a.history, .page-content-container .widget-head.nav ul li a.more_windows", showHistoryVersioningTableResponsive );

	/* 
	* RETURN ======================================================================================================================== 
	* include your newly created functions inside the array so it will be accessable in the outside scope
	* sample syntax in calling it outside - melisCms.savePage;
    */
	
	return {
		//key - access name outside									// value - name of function above
		
		// page actions
		savePage 										: 			savePage,
		publishPage 									: 			publishPage,
		unpublishPage 									: 			unpublishPage,
		//refreshPageTable 								: 			refreshPageTable,
		
		//refresh treeview
		refreshTreeview									:			refreshTreeview,
        disableCmsButtons								: 			disableCmsButtons,
		enableCmsButtons								: 			enableCmsButtons,
		
		iframeLoad										:			iframeLoad,

		pageTabOpenCallback								:			pageTabOpenCallback,
	};
})();

// fixed for issue: 4274
$(function() {
	$("#pageIdRootMenu").parents(".form-group").find("label").addClass("d-flex flex-row justify-content-between");
});