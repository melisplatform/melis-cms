// Melis Cms Functionalities 
var melisCms = (function(){
	
	// CACHE SELECTORS
	var $body 	  = $("body"),
		$document = $("document"),
		$openedPageIds = [];
	
	// ---=[ BUG FIX ] =---  TINYMCE POPUP MODAL FOCUS 
	var windowOffset;

	window.scrollToViewTinyMCE = function(dialogHeight, iframeHeight){
		
		// window scroll offest
		windowOffset = $(window).scrollTop();
		
		if( dialogHeight && iframeHeight){
			
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
        if ($(this).next(".cms-next").is(":visible")) {
            $(this).next(".cms-next").hide();
        } else {
            $(this).next(".cms-next").show();
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
		if(success === 0){
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
   	  	//close page creation tab and open new one (in case if its already open - updated parent ID)
		var pageID = $(this).data('pagenumber');
   	  	melisHelper.tabClose('0_id_meliscms_page');
   	  	melisHelper.tabOpen( translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation',  { idPage: 0, idFatherPage: pageID } );
	}
	
	// SAVE PAGE
	function savePage(idPage) {
		
		var pageNumber = (typeof idPage === 'string') ? idPage :  $(this).data("pagenumber"); 
		var fatherPageId = $(this).data("fatherpageid"); 
		
		// convert the serialized form values into an array
		var datastring = $("#" + pageNumber + "_id_meliscms_page form").serializeArray();
		
		if($("#" + pageNumber + "_id_page_taxonomy").length)
		{
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
		}).done(function() {
			if(data.success === 1){
				// reload and expand the treeview
				refreshTreeview(data.datas.idPage);
	
				// call melisOkNotification 
				melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
				
				// update red colored label when successful
				colorRedError(data.success, data.errors, data.datas.item_zoneid);
				
				// get page creation ID
				var pageCreationId = data.datas.item_zoneid;
				
				// IF ITS PAGE CREATION
				if( pageCreationId === '0_id_meliscms_page'){
					
					// close page creation page and tab
					melisHelper.tabClose(pageCreationId);
					
					//remove first char on the zoneID and replace with newly create id
					var newPageZoneId = data.datas.idPage + pageCreationId.substring(1, pageCreationId.length);
				
					//open newly opened page
					melisHelper.tabOpen( data.datas.item_name, data.datas.item_icon, newPageZoneId, data.datas.item_melisKey,  { idPage: data.datas.idPage } );	
				}else{
					// reload the preview in edition tab
					melisHelper.zoneReload(pageNumber+'_id_meliscms_page','meliscms_page', {idPage:pageNumber});
				}	    	
			}
			else{
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
		
		if($("#" + pageNumber + "_id_page_taxonomy").length)
		{
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
			if(data.success === 1){
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
			if(data.success === 1){
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
		var data = $(this).data();
		var idPage = data.pagenumber;
		var zoneId = activeTabId;
		var confirmMsg = data.confirmmsg;
		
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
					if( data.success === 1){
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
		var data = $(this).data();
		var idPage = data.pagenumber;
		var zoneId = activeTabId;
		var attr = $(this).attr('disabled');
		 /* var parentNode = ( node.key == 'root_1') ? -1 : node.key ; */
		if(typeof attr === typeof undefined || attr === false){
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
	  					if( data.success === 1){
							
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
	
	// Deactivating page edition action buttons
    // function disableCmsButtons(id) {
    //     $("div.make-switch label on, off").parent().css("z-index", -1).parents("div.make-switch").css("opacity", 0.5);
    //     $("#"+id+"_id_meliscms_page_action_tabs").addClass('relative').prepend("<li class='btn-disabled'></li>");
    // }
    
    // Activating page edition action buttons
    // function enableCmsbuttons(id) {
    //     $("#"+id+"_action_tabs").removeClass('relative');
    //     $("#"+id+"_action_tabs li.btn-disabled").remove();
        
    //     $("div.make-switch label on, off").parent().css("z-index", 1).parents("div.make-switch").css("opacity", 1);
    // }
	
	// RELOAD THE TREEVIEW AND SET A NODE PAGE ACTIVE
	function refreshTreeview(pageNumber, self) {
		optionalArg = (typeof self === 'undefined') ? 0 : self;
	  	$.ajax({
  	        url         : '/melis/MelisCms/TreeSites/getPageIdBreadcrumb?idPage='+pageNumber+'&includeSelf='+optionalArg,
  	        encode		: true,
			dataType    : 'json'
  	    }).done(function(data) {
  	    	//process array to add to make this format '1/3/5/6...'
	        var newData = [];
	        var parentNode;				
	        $.each( data, function( key, value ) {
	  	        newData.push("/"+value);
	  	        if(key === 0){
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
		var displayWidth = 0;
    	var displaySettings = $(this).data("display");
    	
    	$(".displaysettingsicon span.fa").removeClass();
    	$(".displaysettingsicon span:first-child").addClass(displaySettings);
    	
    	if( displaySettings === 'fa fa-desktop'){
    		displayWidth = '100%';
    	}
    	else if( displaySettings === 'fa fa-tablet'){
    		displayWidth = '980px';
    	}
    	else{
    		displayWidth = '480px';
    	}
    	
    	$(this).parents(".page-head-container").next(".page-content-container").find(".melis-iframe").animate({
    		width: displayWidth
    	}, 300, function(){
    		// temporarily give the iframe height so it doensn't look bad when it animates the width
    		$("#"+ activeTabId + " .melis-iframe").css('height','1000px');
    		
    		// give iframe the calculated height based from the content
    		var iHeight = $("#"+ activeTabId + " .melis-iframe").contents().height()+20;  
    		$("#"+ activeTabId + " .melis-iframe").css("height", iHeight);
    	});
	}
	
	// PUBLISH - UNPUBLISH TOGGLE BUTTON
	function publishUnpublish(e, datas) {
		var pageNumber = $(this).data('pagenumber').toString();
    	
    	if( datas.value === true){
    		publishPage(pageNumber);
    	}
    	else{
    		unpublishPage(pageNumber);
    	}
	}
	
	// INPUT CHAR COUNTER IN SEO TAB
	function charCounter(event) {
		var charLength = $(this).val().length;
		var prevLabel = $(this).prev('label');
		//var limit = event.data.limit;
		
		if( prevLabel.find('span').length ){
			
			if(charLength === 0){
				prevLabel.removeClass('limit');
				prevLabel.find('span').remove();
			}
			else{
                /**
				 * Removed so that meta title & meta description has no limit
				 * cause it's data type on db is TEXT
                 */
				// prevLabel.find('span').html('<i class="fa fa-text-width"></i>(' + charLength + ')');
				//
				// if( charLength > limit ){
				// 	prevLabel.addClass('limit');
				// 	prevLabel.find('span').addClass('limit');
				// }
				// else{
				// 	prevLabel.removeClass('limit');
				// 	prevLabel.find('span').removeClass('limit');
				// }
			}
		}
		else{
			if(charLength !== 0){
				prevLabel.append("<span class='text-counter-indicator'><i class='fa fa-text-width'></i>(" + charLength + ")</span>");

                /**
                 * Removed so that meta title & meta description has no limit
				 * cause it's data type on db is TEXT
                 */
				// if( charLength > limit ){
				// 	prevLabel.addClass('limit');
				// 	prevLabel.find('span').addClass('limit');
				// }
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
    	var zoneParent = $(this).parents(".melis-page-table-cont");
    	var zoneId = zoneParent.attr("id");
    	var melisKey = zoneParent.data("meliskey");
    	var pageId = zoneParent.data("page-id");
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
	function pageTabOpenCallback(pageId)
	{
		//store the opened pages id
		$openedPageIds.push(pageId);

		//add another statement below if needed
	}

	// WINDOW SCROLL FUNCTIONALITIES ========================================================================================================
	if( melisCore.screenSize >= 768) {
		jQuery(window).scroll(function() {

	        // sticky page actions
			
			var sidebarStatus = $("body").hasClass("sidebar-mini");
			var sidebarWidth = 0;
			if( !sidebarStatus ){
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
    $body.on("keyup keydown change", "form[name='pageseo'] input[name='pseo_meta_title']" , charCounter);
    
    // char counter in seo description
    $body.on("keyup keydown change", "form[name='pageseo'] textarea[name='pseo_meta_description']", charCounter);
    
    // main tab click event (edition, properties etc..)
    $body.on("shown.bs.tab", '.page-content-container .widget-head.nav ul li a', cmsTabEvents);
    
    // refresh page tab (historic, versionining etc)
    $body.on("shown.bs.tab", '.melis-refreshPageTable', refreshPageTable );


    
    
    
    
     
    
    
    
    
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
		
		//refresh treeview
		refreshTreeview									:			refreshTreeview,
        disableCmsButtons								: 			disableCmsButtons,
		enableCmsButtons								: 			enableCmsButtons,
		
		iframeLoad										:			iframeLoad,

		pageTabOpenCallback								:			pageTabOpenCallback,
	};

})();

(function($, window, document) {

	// On Load
	$(window).on('load', function() {
			window.mainTree = function(completeEvent) {
					var melisExtensions;
					var $tabArrowTop = $("#tab-arrow-top");

					if (melisCore.screenSize <= 767) {
							melisExtensions = ['contextMenu', 'filter', 'glyph'];
					} else {
							melisExtensions = ['contextMenu', 'dnd', 'filter', 'glyph'];
					}

					$('#id-mod-menu-dynatree').fancytree({
							extensions: melisExtensions,
							glyph: {
								map: {
									//loading: "fa fa-spinner fa-pulse"
									loading: "glyphicon-refresh fancytree-helper-spin" // edited by junry
								}
							},
							activeVisible: false,
							debugLevel: 0,
							autoScroll: true,
							generateIds: true,
							idPrefix: "mt_",
							tabindex: "",
							toggleEffect: {
									height: "toggle",
									duration: 250
							},
							source: {
									url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id',
									cache: true
							},
							contextMenu: {
									menu: {
											'new': {
													'name': translations.tr_meliscms_menu_new,
													'icon': 'paste'
											},
											'edit': {
													'name': translations.tr_meliscms_menu_edit,
													'icon': 'edit'
											},
											'delete': {
													'name': translations.tr_meliscms_menu_delete,
													'icon': 'delete'
											},
											'dupe': {
													'name': translations.tr_meliscms_menu_dupe,
													'icon': 'copy'
											},
									},
									actions: function(node, action, options) {
											if (action === 'new') {
													var data = node.data;

													//close page creation tab and open new one (in case if its already open - updated parent ID)
													melisHelper.tabClose('0_id_meliscms_page');
													melisHelper.tabOpen(translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation', {
															idPage: 0,
															idFatherPage: data.melisData.page_id
													});
											}
											if (action === 'edit') {
													var data = node.data;
													melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
															idPage: data.melisData.page_id
													});
											}
											if (action === 'delete') {

													var data = node.data;
													var zoneId = data.melisData.item_zoneid;
													var idPage = data.melisData.page_id;
													var parentNode = (node.getParent().key == 'root_1') ? -1 : node.getParent().key;
													// var parentNode = ( node.key == 'root_1') ? -1 : node.getParent().key;	

													// check if page to be delete is open or not
													var openedOrNot = $(".tabsbar a[data-id='" + zoneId + "']").parent("li");

													// delete page confirmation 
													melisCoreTool.confirm(
															translations.tr_meliscms_menu_delete,
															translations.tr_meliscms_menu_cancel,
															translations.tr_meliscms_delete_confirmation,
															translations.tr_meliscms_delete_confirmation_msg,
															function() {
																// reload and expand the treeview
																melisCms.refreshTreeview(parentNode, 1);

																// check if node has children if TRUE then cannot be deleted
																$.ajax({
																	url: '/melis/MelisCms/Page/deletePage?idPage=' + idPage,
																	encode: true
																}).done(function(data) {
																	if (data.success === 1) {
																		//close the page if its open. do nothing if its not open
																		if (openedOrNot.length === 1) {
																				melisHelper.tabClose(zoneId);
																		}

																		// notify deleted page
																		melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46');

																		// update flash messenger values
																		melisCore.flashMessenger();

																	} else {
																			melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, '#000');
																	}
																}).fail(function(xhr, textStatus, errorThrown) {
																	alert( translations.tr_meliscore_error_message );
																});
															});
											}
											if (action === 'dupe') {
													var data = node.data;
													// melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { sourcePageId: data.melisData.page_id } ); 

													// initialation of local variable
													zoneId = 'id_meliscms_tools_tree_modal_form_handler';
													melisKey = 'meliscms_tools_tree_modal_form_handler';
													modalUrl = 'melis/MelisCms/TreeSites/renderTreeSitesModalContainer';
													// requesitng to create modal and display after
													melisHelper.createModal(zoneId, melisKey, false, {
															'sourcePageId': data.melisData.page_id
													}, modalUrl, function() {});
											}
									}
							},
							lazyLoad: function(event, data) {
									// get the page ID and pass it to lazyload
									var pageId = data.node.data.melisData.page_id;
									data.result = {
											url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId=' + pageId,
											data: {
													mode: 'children',
													parent: data.node.key
											},
											cache: false,
									}

							},
							create: function(event, data) {
									melisHelper.loadingZone($('#treeview-container'));
							},
							init: function(event, data, flag) {
									melisHelper.removeLoadingZone($('#treeview-container'));
									// focus search box
									$("input[name=left_tree_search]").focus();

									var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

									if (tree.count() === 0) {

											$(".meliscms-search-box.sidebar-treeview-search").hide();
											// Checking if the user has a Page rights to access
											// -1 is the value for creating new page right
											$.get('/melis/MelisCms/TreeSites/checkUserPageTreeAccress', {
													idPage: -1
											}, function(res) {
													if (res.isAccessible) {
															$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>" + translations.tr_meliscms_create_page + "</span></div>");
													}
											});

									} else {
											$(".meliscms-search-box.sidebar-treeview-search").show();
											$("#id-mod-menu-dynatree .create-newpage").remove();
									}
							},
							click: function(event, data) {
									targetType = data.targetType;
									if (targetType === "title") {
											data.node.setExpanded();

											// open page on click on mobile . desktop is double click
											if (melisCore.screenSize <= 1024) {
													var data = data.node.data;
													melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
															idPage: data.melisData.page_id
													}, null, melisCms.pageTabOpenCallback(data.melisData.page_id));
											}
									}
									$('.hasNiceScroll').getNiceScroll().resize();

									if ( $tabArrowTop.length ) {
											$tabArrowTop.removeClass("hide-arrow");
									}
							},
							dblclick: function(event, data) {
									// get eventType to know what was clicked the 'expander (+-)' or the title
									//targetType = data.targetType;

									// open tab and page
									var data = data.node.data;
									melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
											idPage: data.melisData.page_id
									}, null, melisCms.pageTabOpenCallback(data.melisData.page_id));

									$('.hasNiceScroll').getNiceScroll().resize();

									return false;
							},
							loadChildren: function(event, data) {
									//RUNS ONLY ONCE

									// if there is no/empty pages in the treeview
									var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

									// // PAGE ACCESS user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/TreeSites/canEditPages',
									//     encode		: true
									// }).success(function(data){
									// 	// has no access
									// 	if(data.edit === 0){
									// 	 	$(".meliscms-search-box.sidebar-treeview-search").hide();
									// 		$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='no-access'>" + translations.tr_meliscms_no_access + "</span></div>");
									// 	}
									// 	// has access
									// 	else{
									// 		 if(tree.count() === 0){
									// 		$(".meliscms-search-box.sidebar-treeview-search").hide();
									// 		$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>"+ translations.tr_meliscms_create_page +"</span></div>");
									// 		 }
									// 		 else{
									// 		$(".meliscms-search-box.sidebar-treeview-search").show();
									// 			$("#id-mod-menu-dynatree .create-newpage").remove();
									// 		 }
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });


									// // SAVE user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/Page/isActionActive?actionwanted=save',
									//     encode		: true
									// }).success(function(data){
									// 	if(data.active === 0){
									// 		$("body").addClass('disable-create');
									// 	}
									// 	else{
									// 		$("body").removeClass('disable-create');
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });
									//
									// // DELETE user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/Page/isActionActive?actionwanted=delete',
									//     encode		: true
									// }).success(function(data){
									// 	if(data.active === 0){
									// 		$("body").addClass('disable-delete');
									// 	}
									// 	else{
									// 		$("body").removeClass('disable-delete');
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });
							},
							renderNode: function(event, data) {
									// removed .fancytree-icon class and replace it with font-awesome icons
									$(data.node.span).find('.fancytree-icon').addClass(data.node.data.iconTab).removeClass('fancytree-icon');
									//console.log({data});

									if (data.node.statusNodeType !== 'loading') {

											if (data.node.data.melisData.page_is_online === 0) {
													$(data.node.span).find('.fancytree-title, .fa').css("color", "#686868");
											}

											if (data.node.data.melisData.page_has_saved_version === 1) {
													//check if it has already 'unpublish' circle - avoid duplicate circle bug
													if ($(data.node.span).children("span").hasClass("unpublish") == false) {
															$(data.node.span).find('.fancytree-title').before("<span class='unpublish'></span>");
													}
											}
									}
							},
							dnd: {
									autoExpandMS: 400,
									smartRevert: true,
									refreshPositions: true,
									draggable: {
											zIndex: 1000,
											scroll: false,
											appendTo: "body",
									},
									dragStart: function(node, data) {
											// disable drag & drop if its on mobile
											if (melisCore.screenSize >= 1024) {
													// determine if the node is draggable or not
													if (!data.node.data.dragdrop) {
															return false;
													} else {
															return true;
													}
											} else {
													return false;
											}
									},
									dragEnter: function(node, data) {
											return true;
									},
									dragOver: function(node, data) {

									},
									dragLeave: function(node, data) {

									},
									dragStop: function(node, data) {

									},
									dragDrop: function(node, data) {
											node.setExpanded(true).always(function() {
													// This function MUST be defined to enable dropping of items on the tree.
													// data.hitMode is 'before', 'after', or 'over'.
													// We could for example move the source to the new target:

													// catch if its 'root_*' parent
													var isRootOldParentId = data.otherNode.getParent().key.toString();
													var oldParentId = (isRootOldParentId.includes('root')) ? -1 : data.otherNode.getParent().key;

													// move the node to drag parent ------------------------------------------------

													data.otherNode.moveTo(node, data.hitMode);

													var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

													var draggedPage = data.otherNode.key

													// catch if its 'root_*' parent
													var isRootNewParentId = node.getParent().key.toString();
													var newParentId = (isRootNewParentId.includes('root')) ? -1 : node.getParent().key;

													if (data.hitMode == 'over') {
															newParentId = data.node.key;
													}

													var newIndexPosition = data.otherNode.getIndex() + 1;

													//send data to apply new position of the dragged node
													var datastring = {
															idPage: draggedPage,
															oldFatherIdPage: oldParentId,
															newFatherIdPage: newParentId,
															newPositionIdPage: newIndexPosition
													};

													$.ajax({
														url: '/melis/MelisCms/Page/movePage',
														data: datastring,
														encode: true
													}).fail(function(xhr, textStatus, errorThrown) {
														alert( translations.tr_meliscore_error_message );
													});
											});
											// end
									}
							},
					});
			}

			// initialize the tree
			mainTree();
	});

	// create page if treeview page is empty
	$("body").on("click", "#id-mod-menu-dynatree .create-newpage .btn", function() {
			melisHelper.tabOpen(translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation', {
					idPage: 0,
					idFatherPage: '-1'
			});
	});

	$("body").on("click", '#sourcePageIdFindPageTree span', function() {
			melisLinkTree.createInputTreeModal('#sourcePageId');
	});

	$("body").on("click", '#destinationPageIdFindPageTree span', function() {
			melisLinkTree.createInputTreeModal('#destinationPageId');
	});

	$("body").on("click", 'button[data-inputid="#destinationPageId"]', function() {
			$('[name="use_root"]').each(function() {
					if ($(this).is(':checked')) {
							$(this).prop("checked", false);
					}
			})
			$('.remember-me-cont .cbmask-inner').removeClass('cb-active');
			$("#destinationPageId").prop('disabled', false);
	});

	$("body").on('change', '[name="use_root"]', function() {

			if ($('[name="use_root"]:checked').length) {

					$("#destinationPageId").val("");
					$("#destinationPageId").prop('disabled', true);
			} else {

					$("#destinationPageId").prop('disabled', false);
			}
	})

	// use this callback to re-initialize the tree when its zoneReloaded
	window.treeCallBack = function() {
			if ($("#id-mod-menu-dynatree").children().length == 0) {
					mainTree();
			}
	}

	$("body").on("click", "#duplicatePageTree", function() {
			var dataString = $('#duplicatePageTreeForm').serializeArray();
			var parentNode = $('#duplicatePageTreeForm input[name="destinationPageId"]').val();
			melisCoreTool.pending("#duplicatePageTree");
			$("#duplicatePageTree").find('i').removeClass();
			$("#duplicatePageTree").find('i').addClass('fa fa-spinner fa-spin');
			$.ajax({
				type: 'POST',
				url: '/melis/MelisCms/TreeSites/duplicateTreePage',
				data: dataString,
				dataType: 'json',
				encode: true
			}).done(function(data) {
				if (data.success) {
						$('#id_meliscms_tools_tree_modal_form_handler_container').modal('hide');
						melisCms.refreshTreeview(parentNode, 1);
						// clear Add Form
						melisHelper.melisOkNotification(data.textTitle, data.textMessage);
				} else {
						melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
						melisCoreTool.highlightErrors(data.success, data.errors, "stylesForm");
				}
				melisCore.flashMessenger();
				melisCoreTool.done("#duplicatePageTree");
				$("#duplicatePageTree").find('i').removeClass();
				$("#duplicatePageTree").find('i').addClass('fa fa-save');
			}).fail(function() {
				alert(translations.tr_meliscore_error_message);
			});
	})

})(jQuery, window);
/**
 *  Here, you will be using your javacript codes to interact with
 *  the server specially on your tool actions from your tool controller
 */
$(document).ready(function() {
	// for edit button
	$("body").on("click", '.btnEditTemplates', function() {
		var id = $(this).parents("tr").attr("id");
		melisCoreTool.hideAlert("#templateupdateformalert");
		melisCoreTool.showOnlyTab('#modal-template-manager-actions', '#id_modal_tool_template_edit');
		toolTemplate.retrieveTemplateData(id);
	});
	
	$("body").on("click", '.btnDelTemplate', function() {
		var id = $(this).parents("tr").attr("id");
		toolTemplate.deleteTemplate(id);
	});
	
	$("body").on("click", "#id_meliscms_tool_templates_header_add", function() {
		melisCoreTool.hideAlert("#templateaddformalert");
		melisCoreTool.resetLabels("#id_modal_tool_template_add #id_tool_template_generic_form");
	});
	
	$("body").on("click", "#btnTemplateUpdate", function() {
		toolTemplate.updateTemplate();
	});

	$("body").on("click", ".btnMelisTemplatesExport", function() {
		var searched = $("input[type='search'][aria-controls='tableToolTemplateManager']").val();
		var siteId = $("#templatesSiteSelect").val();
		if(!melisCoreTool.isTableEmpty("tableToolTemplateManager")) {
			melisCoreTool.exportData('/melis/MelisCms/ToolTemplate/exportToCsv?filter='+searched+'&site='+siteId);
		}
	});
	
	$("body").on("change", "#templatesSiteSelect", function(){
		var tableId = $(this).parents().eq(6).find('table').attr('id');
		$("#"+tableId).DataTable().ajax.reload();
	});
});
var toolTemplate = { 

		/** THESE ARE THE MAIN FUNCTIONS THAT WILL BE USED IN YOUR PLUGIN TOOL **/
		table: function() {
			return "#tableToolTemplate";
		},
		
		initTool: function() {
			melisCoreTool.initTable(toolTemplate.table());
		},

		refreshTable : function() {
			// select default tab in the modal
			melisCoreTool.switchTab('#id_modal_tool_template_add');
			melisHelper.zoneReload("id_meliscms_tool_templates", "meliscms_tool_templates");
		},
		
		addNewTemplate : function() {
			var dataString = $("#id_modal_tool_template_add #id_tool_template_generic_form").serialize();
			melisCoreTool.pending("#btnTemplateAdd");
    		$.ajax({
    	        type        : 'POST', 
    	        url         : '/melis/MelisCms/ToolTemplate/newTemplateData',
    	        data        : dataString,
    	        dataType    : 'json',
    	        encode		: true
    	    }).done(function(data) {
    	    	
    	    	if(data.success) {
    	    		$("#modal-template-manager-actions").modal('hide');
    	    		toolTemplate.refreshTable();
    	    		// clear Add Form
    	    		melisCoreTool.clearForm("id_tool_template_generic_form");
    	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage );
    	    	}
    	    	else {
		    		melisCoreTool.alertDanger("#templateaddformalert", '', data.textMessage);
		    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
		    		melisCoreTool.highlightErrors(data.success, data.errors, "id_modal_tool_template_add #id_tool_template_generic_form");
    	    	}
    	    	melisCore.flashMessenger();
    	    	melisCoreTool.done("#btnTemplateAdd");
    	    }).fail(function(){
    	    	alert( translations.tr_meliscore_error_message );
    	    });
			
		},
		
		updateTemplate: function() {
			var dataString = $("#id_modal_tool_template_edit #id_tool_template_generic_form").serializeArray();
			dataString.push({
				name: 'tpl_id', 
				value: $("#tplid").html(),
			});
			dataString = $.param(dataString);
			melisCoreTool.resetLabels("#id_modal_tool_template_edit #id_tool_template_generic_form");
			melisCoreTool.pending("#btnTemplateEdit");
    		$.ajax({
    	        type        : 'POST', 
    	        url         : '/melis/MelisCms/ToolTemplate/updateTemplateData',
    	        data        : dataString,
    	        dataType    : 'json',
    	        encode		: true
    	    }).done(function(data){
    	    	if(data.success) {
    	    		$("#modal-template-manager-actions").modal('hide');
    	    		toolTemplate.refreshTable();
    	    		// clear Edit Form
    	    		melisCoreTool.clearForm("id_tool_template_generic_form");
    	    		melisCoreTool.resetLabels("#id_tool_template_generic_form");
    	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage);
    	    	}
    	    	else {
		    		melisCoreTool.alertDanger("#templateupdateformalert", '', data.textMessage);
		    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
		    		melisCoreTool.highlightErrors(data.success, data.errors, "id_tool_template_generic_form");
    	    	}
    	    	melisCore.flashMessenger();
    	    	melisCoreTool.done("#btnTemplateEdit");
    	    }).fail(function(){
    	    	alert( translations.tr_meliscore_error_message );
    	    });
		},
		
		retrieveTemplateData: function(id) {
			var updateForm = "#id_modal_tool_template_edit form#id_tool_template_generic_form ";
			melisCoreTool.resetLabels(updateForm);
    		$.ajax({
    	        type        : 'POST', 
    	        url         : '/melis/MelisCms/ToolTemplate/getTemplateDataById',
    	        data        : {templateId : id},
    	        dataType    : 'json',
    	        encode		: true
    	    }).done(function(data){
    	    	
    	    	
	    		$.each(data, function(index, value) {
	    			// append data to your update form
	    			$(updateForm + " input, " + updateForm +" select").each(function(index) {
	    				var name = $(this).attr('name');
	    				$(updateForm + " #" + $(this).attr('id')).val(value[name]);
	    				$("#tplid").html(value['tpl_id']);
	    			});
	    		});
    	    }).fail(function(){
    	    	alert( translations.tr_meliscore_error_message );
    	    });
		}, 
		
		deleteTemplate: function(id) {
			melisCoreTool.confirm(
				translations.tr_meliscore_common_yes,
				translations.tr_meliscore_common_no,
				translations.tr_tool_template_fm_delete_title, 
				translations.tr_tool_template_fm_delete_confirm, 
				function() {
		    		$.ajax({
		    	        type        : 'POST', 
		    	        url         : '/melis/MelisCms/ToolTemplate/deleteTemplateData',
		    	        data        : {templateId : id},
		    	        dataType    : 'json',
		    	        encode		: true
		    	    }).done(function(data){
		    	    	melisCoreTool.pending(".delTemplate");
		    	    	// refresh the table after deleting an item
		    	    	if(data.success) {
		    	    		toolTemplate.refreshTable();
		    	    		melisCore.flashMessenger();
		    	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage );
		    	    	}
		    	    	melisCoreTool.done(".delTemplate");
		    	    }).fail(function(){
		    	    	alert( translations.tr_meliscore_error_message );
		    	    });
			});
		},
};

window.initTemplateList = function(data, tblSettings){
	if($('#templatesSiteSelect').length){
		data.tpl_site_id = $('#templatesSiteSelect').val();
	}
		
}
$(document).ready(function() {
	//var formAdd  = "#formplatformadd form#idformsite";
	var formEdit = "#formplatformedit form#idformlang";
	
	addEvent("#btn_cms_new_lang",function(){
		melisCoreTool.showOnlyTab('#modal-language-cms', '#id_meliscms_tool_language_modal_content_new');
		melisCoreTool.clearForm("idformlang");
	});
	
	addEvent("#btnLangCmsAdd", function() {
		
		var dataString = $(this).parent().find("#idformlang").serialize();
		melisCoreTool.pending("#btnLangCmsAdd");
		melisCoreTool.processing();
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Language/addLanguage',
	        data		: dataString,
	        dataType    : 'json',
			encode		: true,
			success		: function(data) {
				if(data.success) {
					$('#modal-language-cms').modal('hide');
					melisHelper.zoneReload("id_meliscms_tool_language", "meliscms_tool_language");
					melisHelper.melisOkNotification(data.textTitle, data.textMessage);
				}
				else {
					melisCoreTool.alertDanger("#languagealert", '', data.textMessage);
					melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
					melisCoreTool.highlightErrors(data.success, data.errors, "idformlang");
				}
				
				melisCoreTool.done("#btnLangCmsAdd");
				melisCore.flashMessenger();	
				melisCoreTool.processDone();
			},
			error		: function(xhr, textStatus, errorThrown) {
				alert( translations.tr_meliscore_error_message );
			}
	    });
	});

	addEvent("#btnLangCmsEdit", function() {
		melisCoreTool.showOnlyTab('#modal-language-cms', '#id_meliscms_tool_language_modal_content_edit');
		var getId = $(this).parents("tr").attr("id");
		
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Language/getLanguageById',
	        data		: {id : getId},
	        dataType    : 'json',
			encode		: true,
			success 	: function(data) {
				melisCoreTool.pending(".btn");
 	    		$(formEdit + " input[type='text']").each(function(index) {
 	    			var name = $(this).attr('name');
 	    			$("input#" + $(this).attr('id')).val(data.language[name]);
 	    			$("span#platformupdateid").html(data.language['lang_cms_id']);

 	    		});
 	    		melisCoreTool.done(".btn");
			},
			error		: function(xhr, textStatus, errorThrown) {
				alert( translations.tr_meliscore_error_message );
			}
	     });
	});
	
	addEvent("#btnLangEdit", function() {
		var dataString = $(formEdit).serializeArray();
		dataString.push({
			name: "id",
			value: $("#platformupdateid").html()
		});
		dataString = $.param(dataString);
		melisCoreTool.pending("#btnLangEdit");
		melisCoreTool.processing();
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Language/editLanguage',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true
		}).done(function(data) {
			if(data.success) { //alert("success!");
				$('#modal-language-cms').modal('hide');
				melisHelper.zoneReload("id_meliscms_tool_language", "meliscms_tool_language");
				// Show Pop-up Notification
	    		melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}
			else {
				melisCoreTool.alertDanger("#langeditalert", '', data.textMessage);
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
				melisCoreTool.highlightErrors(data.success, data.errors, "formplatformedit form#idformlang");
			}
			melisCoreTool.done("#btnLangEdit");
    		melisCore.flashMessenger();
    		melisCoreTool.processDone();
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	addEvent("#btnLangCmsDelete", function() {
		var getId = $(this).parents("tr").attr("id");
		
		melisCoreTool.confirm(
            translations.tr_meliscms_common_yes,
			translations.tr_meliscore_common_no,
            translations.tr_meliscms_tool_language,
			translations.tr_meliscms_tool_language_delete_confirm,
			function() {
	    		$.ajax({
	    	        type        : 'POST', 
	    	        url         : '/melis/MelisCms/Language/deleteLanguage',
	    	        data		: {id : getId},
	    	        dataType    : 'json',
					encode		: true,
					success: function(data) {
						melisCoreTool.pending(".btn-danger");
		    	    	if(data.success) {
		    	    		melisHelper.zoneReload("id_meliscms_tool_language_content", "meliscms_tool_language_content");
		    	    		melisHelper.zoneReload("id_meliscms_header_language", "meliscms_header_language");
		    	    		melisHelper.melisOkNotification(data.textTitle, data.textMessage);
		    	    	}
		    	    	else {
		    	    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
		    	    	}
		    	    	melisCore.flashMessenger();
		    	    	melisCoreTool.done(".btn-danger");
					},
					error: function(xhr, textStatus, errorThrown) {
						alert( translations.tr_meliscore_error_message );
					}
	    	     });
		});
	});
	
	
	
	function addEvent(target, func) {
		$("body").on("click", target, func);
	}
});

window.initLangJs = function() {
	//$(document).on("init.dt", function(e, settings) {
		$('#tableLanguages td:nth-child(3):contains("'+ melisLangId +'")').siblings(':last').html('-');
	//});
}
$(function(){
	
	$("body").on("click", ".btnCmsPlatfomEdit", function(){
		var pId = $(this).parents("tr").attr("id");
		// initialation of local variable
		modalId = 'platform_tool_modal';
		platform_modal_content = 'meliscms_tool_platform_ids_modal_content';
		modalUrl = '/melis/MelisCms/Platform/renderPlatformModal';
		// requesitng to create modal and display after
    	melisHelper.createModal(modalId, platform_modal_content, false, {id:pId}, modalUrl);
	});
	
	$("body").on("click", ".btnSavePlatfomrRange", function(){
		var pId = $(this).data("id");
		
		var dataString = $('#idformplatform').serializeArray();
		
		dataString.push({
			name: "pids_id",
			value: pId
		});
		
		dataString = $.param(dataString);
		$("#cmsPlatformAlert").addClass('hidden');
		$.ajax({
			type        : 'POST', 
    	    url         : '/melis/MelisCms/Platform/savePlatformIdsRange',
    	    data        : dataString,
    	    dataType    : 'json',
    	    encode		: true
    	}).done(function(data){
    		if(data.success) {
				$('#platform_tool_modal_container').modal('hide');
				melisHelper.zoneReload("id_meliscms_tool_platform_ids", "meliscms_tool_platform_ids");
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}else{
				melisCoreTool.alertDanger("#cmsPlatformAlert", '', data.textMessage);
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
			}
    		melisCore.flashMessenger();
    		melisCoreTool.highlightErrors(data.success, data.errors, "idformplatform");
    	}).error(function(xhr, textStatus, errorThrown){
    		alert("ERROR !! Status = "+ textStatus + "\n Error = "+ errorThrown + "\n xhr = "+ xhr.statusText);
    	});
	});
	
	$("body").on("click","#id_meliscms_tool_platform_ids_add_button", function(){
		// initialation of local variable
		modalId = 'platform_tool_modal';
		platform_modal_content = 'meliscms_tool_platform_ids_modal_content';
		modalUrl = '/melis/MelisCms/Platform/renderPlatformModal';
		// requesitng to create modal and display after
    	melisHelper.createModal(modalId, platform_modal_content, false, null, modalUrl);
	});
	
	$("body").on("click", ".btnCmsPlatformIdsDelete", function(){
		var pid_id = $(this).parents("tr").attr("id");
		var dataString = new Array;
		
		dataString.push({
			name	: 'pid_id',
			value	: pid_id,
		});
		
		melisCoreTool.confirm(
			translations.tr_meliscore_common_yes,
			translations.tr_meliscore_common_no,
			translations.tr_meliscms_tool_platform_ids, 
			translations.tr_meliscms_tool_platform_ids_confirm_msg, 
			function() {
				$.ajax({
			        type        : 'POST', 
			        url         : '/melis/MelisCms/Platform/deletePlatformId',
			        data		: dataString,
			        dataType    : 'json',
			        encode		: true
				}).done(function(data) {
					melisHelper.zoneReload("id_meliscms_tool_platform_ids", "meliscms_tool_platform_ids");
					melisHelper.melisOkNotification(data.textTitle, data.textMessage);
					melisCore.flashMessenger();
				}).fail(function(){
					alert( translations.tr_meliscore_error_message );
				});
		});
	});

    window.initPlatformIdTbl = function () {
        var parent = "#platformToolTable";

        // CMS platform IDs list init to remove delete buttons
        $(parent).find('.noPlatformIdDeleteBtn').each(function () {
            var rowId = '#' + $(this).attr('id');
            $(parent).find(rowId).find('.btnCmsPlatformIdsDelete').remove();
        });
    }
});


$(function(){
	var $body = $("body");
	
	// Adding new Site Redirect
	$body.on("click", ".addRedirectSite", function(){
		s301Id = $(this).data("s301id");
		createSite301Modal(s301Id);
	});
	// Editing new Site Redirect
	$body.on("click", ".editRedirectSite", function(){
		var btn = $(this);
		var s301Id = btn.parents("tr").attr("id");
		createSite301Modal(s301Id);
	});
	// Saving Site Redirect form
	$body.on("click", ".saveSiteRedirect", function(){
		s301Id = $(this).data("s301id");
		
		var dataString = new Array;
		
		dataString = $("#siteRedirectForm").serializeArray();
		
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/SiteRedirect/saveSiteRedirect',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true,
	    }).done(function(data) {
	    	if(data.success) {
	    		$("#id_meliscms_tool_site_301_generic_form_container").modal("hide");
	    		// Notifications
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
				melisHelper.zoneReload("id_meliscms_tool_site_301_content", "meliscms_tool_site_301_content");
	    	}else{
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
	    	}
	    	melisCore.flashMessenger();
	    	melisCoreTool.highlightErrors(data.success, data.errors, "siteRedirectForm");
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	// Deleting existing Site Redirect
	$body.on("click", ".deleteRedirectSite", function(){
		var btn = $(this);
		var s301Id = btn.parents("tr").attr("id");
		
		melisCoreTool.confirm(
		translations.tr_meliscms_common_yes,
		translations.tr_meliscms_common_no,
		translations.tr_meliscms_tool_site_301_delete_site_redirect, 
		translations.meliscms_tool_site_301_delete_confirm_msg, 
		function() {
			$.ajax({
		        type        : 'POST', 
		        url         :  '/melis/MelisCms/SiteRedirect/deleteSiteRedirect',
		        data		: {s301Id: s301Id},
		        dataType    : 'json',
		        encode		: true,
		    }).done(function(data) {
		    	if(data.success) {
		    		// Notifications
					melisHelper.melisOkNotification(data.textTitle, data.textMessage);
					melisHelper.zoneReload("id_meliscms_tool_site_301_content", "meliscms_tool_site_301_content");
		    	}else{
					melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
		    	}
		    	melisCore.flashMessenger();
			}).fail(function(){
				alert( translations.tr_meliscore_error_message );
			});
		});
	});
	
	// Testing the Site Redirect
	$body.on("click", ".testRedirectSite", function(){
		var btn = $(this);
		var parentTr = btn.parents("tr");
		var s301Id = parentTr.attr("id");
		
		var oldUrl = $("#tableToolSite301 tr#"+s301Id+" td:nth-child(4)").text();
		if(oldUrl !== ''){
			window.open(oldUrl,"_blank");
		}else{
			alert( translations.tr_meliscore_error_message );
		}
	});
	
	$body.on("change", "#redirectSiteSelect", function(){
		var tableId = $(this).parents().eq(6).find('table').attr('id');
		$("#"+tableId).DataTable().ajax.reload();
	});
});

// Site Redirect mdoal form
window.createSite301Modal = function(s301Id){
	if(typeof s301Id === "undefined") s301Id = null;
	zoneId = 'id_meliscms_tool_site_301_generic_form';
	melisKey = 'meliscms_tool_site_301_generic_form';
	modalUrl = '/melis/MelisCms/SiteRedirect/renderToolSiteRedirectModal';
	melisHelper.createModal(zoneId, melisKey, false, {s301Id: s301Id}, modalUrl);
}

window.initRedirectTemplateList = function(data, tblSettings){
	if($('#redirectSiteSelect').length){
		data.s301_site_id = $('#redirectSiteSelect').val();
	}
		
}
 var melisLinkTree = (function($, window){
    
    // cache DOM
    var $body = $('body');
    var dataUrl;

    // Binding Events =================================================================================================================

    //$body.on("click", "div[aria-label='Insert/edit link']", checkBtn);
    //$body.on("click", "div.mce-menu-item", checkBtn);

    // CreateTreeModal
    $body.on("click", "#mce-link-tree", createTreeModal);
    
    // Filter Search
    $(document).on("keyup", "input[name=tree_search]", function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            startTreeSearch();
        }
    }).focus();
    
    $body.on("click", "#searchTreeView", function(e){
        startTreeSearch();
    });
    
    $body.on("click", "#resetTreeView", function(e){
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
        
    $body.on("click", "#generateTreePageLink", function(){
        melisCoreTool.pending('#generateTreePageLink');
        var id = $('#find-page-dynatree .fancytree-active').parent('li'). attr('id').split("_")[1];
        $.ajax({
            type        : 'GET', 
            url         : 'melis/MelisCms/Page/getPageLink',
            data        : {'idPage': id},
            dataType    : 'json',
            encode      : true,
            success     : function(data) {
                dataUrl = data.link;
                showUrl(dataUrl);
                $("#id_meliscms_find_page_tree_container").modal("hide");
            },
            error       : function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            }
         });
        melisCoreTool.done('#generateTreePageLink');

    });
    
    $body.on("click", "#generateTreePageId", function(){
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
            data        : {name: 'value', value: match},
            dataType    : 'json',
            encode      : true,
            success     : function(data) {
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
            },
            error: function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            }
         });
    }
    
    function showUrl( dataUrl ) {
        var $body           = $("body"),
            $dialog         = $body.find(".tox-dialog"),
            $mceLinkTree    = $body.find("#mce-link-tree"),

            $iframe         = window.parent.$(".melis-iframe"),
            $idialog        = $iframe.contents().find(".tox-dialog"),
            $imceLinkTree   = $iframe.contents().find("#mce-link-tree");

            if ( $idialog.length ) {
                $imceLinkTree.parent().find("input").val(dataUrl);
            }

            if ( $dialog.length ) {
                $mceLinkTree.parent().find("input").val(dataUrl);
            }
    }

    // not used anymore on tinymce v5
    function checkBtn() {
        var urlBox = $('body').find('.mce-has-open').prev().text();

        var check = $body.find('.mce-has-open')[0];

        var urlLabel = $('body').find('.mce-widget.mce-label');
        
        urlLabel.each( function() {
            if($(this).text() === "Url") {
                var moxie = $body.find('.mce-btn.mce-open');
                var moxieWidth = moxie.width() + 1;
                var urlInputBox = $(this).next();
                var urlInput = urlInputBox.children('.mce-textbox');
                var cInput;

                if(moxie.length){
                    cInput = urlInput.width() - moxieWidth;
                    moxie.css({'left':'0'});
                    urlInput.css({'width': cInput})
                    addTreeBtnMoxie();
                }else{
                    cInput = urlInput.width() - 32;
                    urlInput.css({'width': cInput});
                    urlInputBox.append('<div id="mce-link-tree" class="mce-btn mce-open" style="position: absolute; right: 0; width: 32px; height: 28px;"><button><i class="icon icon-sitemap fa fa-sitemap" style="font-family: FontAwesome; position: relative; top: 2px; font-size: 16px;"></i></button></div>');
                }
                
            }
        });
    }

    // not used
    function addTreeBtnMoxie() {
        var box = $body.find('.mce-has-open');
        box.append('<div id="mce-link-tree" class="mce-btn mce-open" style="position: absolute; right: 0; width: 32px; height: 28px;"><button><i class="icon icon-sitemap fa fa-sitemap" style="font-family: FontAwesome; position: relative; top: 2px; font-size: 16px;"></i></button></div>');
    }

    function createTreeModal() {

        // initialation of local variable
        zoneId = 'id_meliscms_find_page_tree';
        melisKey = 'meliscms_find_page_tree';
        modalUrl = 'melis/MelisCms/Page/renderPageModal';

        // requesitng to create modal and display after
        if( $('#id_meliscms_find_page_tree_container').length ) {
            $('#id_meliscms_find_page_tree_container').parent().remove();
        }
        
        window.parent.melisHelper.createModal(zoneId, melisKey, false, {}, modalUrl, function() {
        });
        
        $("#mce-link-tree").closest('.tox-dialog').css('z-index', 1049);
        $(".tox-tinymce-aux").css('z-index', 1048);
        $(".tox-tinymce-aux").find(".tox-dialog-wrap__backdrop").css('z-index', 1047);
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
        createInputTreeModal    :       createInputTreeModal,
        findPageMainTree        :       findPageMainTree,
        checkBtn                :       checkBtn,
        showUrl                 :       showUrl,
    }

})(jQuery, window);
$(function() {
    $("body").on("click", "a.melis-pageduplicate", function() {
        var pagenumber = $(this).data().pagenumber;
        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/PageDuplication/duplicate-page',
            data        : {id : pagenumber},
            dataType    : 'json',
            encode		: true,
            success: function(data) {
                if(data.success) {
                    melisCms.refreshTreeview(data.response.pageId);
                    if(data.response.openPageAfterDuplicate) {
                        // open page
                        melisHelper.tabOpen( data.response.name, data.response.icon, data.response.pageId + '_id_meliscms_page', 'meliscms_page',  { idPage: data.response.pageId} );
                    }
                    melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                }
                else {
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, 0);
                }
                melisCore.flashMessenger();
            },
            error: function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            }
        });
    });
});
 var melisSearchPageTree = (function($, window){

    // cache DOM
    var $body = $('body');

    $body.on("click", "#leftSearchTreeView", function(e){
    	startTreeSearch();
	});

    // Filter Search
    $(document).on("keyup", "input[name=left_tree_search]", function(event){
    	var keycode = (event.keyCode ? event.keyCode : event.which);
    	if(keycode == '13'){
    		startTreeSearch();
    	}   
    });

    $body.on("click", "#leftResetTreeView", function(e){
    	$("input[name=left_tree_search]").val('');
        $("#id-mod-menu-dynatree").fancytree("destroy");
        mainTree();
	});

    function startTreeSearch() {
    	var match = $.trim( $("input[name=left_tree_search]").val() );
		var tree = $("#id-mod-menu-dynatree").fancytree("getTree");
		var filterFunc = tree.filterNodes;

    	if(match.length) {
			var opts = {};		
			var tmp = '';
			
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
				encode		: true,
				success: function(data) {
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
						tree.loadKeyPath(arr, function(node, status){
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
				},
				error: function(xhr, textStatus, errorThrown) {
					alert( translations.tr_meliscore_error_message );
				}
		     });
    	}
    }
})(jQuery, window);
$(function(){
	var $body = $("body");
	
	$body.on("click", ".pageLangCreate", function(){
		
		var btn = $(this);
		var pageId = $(this).data('pageid');
		var formId = $(this).data("formid");
		var dataString = $("#"+formId).serializeArray();
		
		btn.attr('disabled', true);
		
		$.ajax({
			type        : 'POST', 
    	    url         : '/melis/MelisCms/PageLanguages/createNewPageLangVersion',
    	    data        : dataString,
    	    dataType    : 'json',
    	    encode		: true
    	}).done(function(data){
    		if(data.success) {
				
				if(!$.isEmptyObject(data.pageInfo)){
					// Opening new Page language created
					melisHelper.tabOpen( data.pageInfo.name, data.pageInfo.tabicon, data.pageInfo.id, data.pageInfo.meliskey, {idPage : data.pageInfo.pageid});
					// reload and expand the treeview
                    melisCms.refreshTreeview(data.pageInfo.pageid);
				}
				
				// Reload the parent page
				melisHelper.zoneReload(pageId+"_id_meliscms_page", "meliscms_page", {idPage : pageId});
				
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}else{
				melisCoreTool.alertDanger("#cmsPlatformAlert", '', data.textMessage);
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
			}
    		
    		melisCore.flashMessenger();
    		melisCoreTool.highlightErrors(data.success, data.errors, formId);
    		btn.attr('disabled', false);
    	}).error(function(xhr, textStatus, errorThrown){
    		btn.attr('disabled', false);
    		alert(translations.tr_meliscore_error_message);
    	});
	});
	
	$body.on("click", ".open-page-from-lan-tab", function(){
		var data = $(this).data();
		if(!$.isEmptyObject(data)){
			melisHelper.tabOpen( data.name, data.tabicon, data.id, data.meliskey, {idPage : data.pageid});
		}
	});
	
});
/**
 *  Here, you will be using your javacript codes to interact with
 *  the server specially on your tool actions from your tool controller
 */
$(document).ready(function() {
	var $body = $('body');
	
	$body.on("click", "#id_meliscms_tool_styles_header_add", function(){
		melisStyleTool.openToolModal(0);
	});
	
	$body.on("click", ".btnEditStyles", function(){
		var id = $(this).closest('tr').attr("id");
		melisStyleTool.openToolModal(id);
	});
	
	$body.on("click", "#saveStyleDetails", function(){
		var id = $(this).data('style-id');
		var formData = $('#stylesForm').serializeArray();
		melisStyleTool.saveStyleDetails(formData);
	});
	
	$body.on("click", ".btnDelStyle", function(){
		var id = $(this).closest('tr').attr("id");
		melisStyleTool.deleteStyle(id);
	});
	
	$body.on("click", '#styleInputFindPageTree span', function(){
		melisLinkTree.createInputTreeModal('#id_style_page_id');
	});
});

var melisStyleTool = (function($, window){
	
	
	function openToolModal(id)
	{
			// initialation of local variable
			zoneId = 'id_meliscms_tool_styles_modal_form_handler';
			melisKey = 'meliscms_tool_styles_modal_form_handler';
			modalUrl = 'melis/MelisCms/ToolStyle/renderToolStyleModalContainer';
			// requesitng to create modal and display after
	    	melisHelper.createModal(zoneId, melisKey, false, {'styleId': id}, modalUrl, function(){
	    	});
	}
	
	function saveStyleDetails(dataString)
	{
		melisCoreTool.pending("#saveStyleDetails");
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/ToolStyle/saveStyleDetails',
	        data        : dataString,
	        dataType    : 'json',
	        encode		: true
	    }).done(function(data) {
	    	
	    	if(data.success) {
	    		$('#id_meliscms_tool_styles_modal_form_handler_container').modal('hide');
	    		melisStyleTool.refreshTable();
	    		// clear Add Form
	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage );
	    	}
	    	else {
	    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
	    		melisCoreTool.highlightErrors(data.success, data.errors, "stylesForm");
	    	}
	    	melisCore.flashMessenger();
	    	melisCoreTool.done("#saveStyleDetails");
	    }).fail(function(){
	    	alert( translations.tr_meliscore_error_message );
	    });
	}
	
	function deleteStyle(id)
	{
		var dataString =  {styleId : id};
		melisCoreTool.pending(".btnDelStyle");
		console.log(dataString);
		melisCoreTool.confirm(
			translations.tr_meliscms_common_yes,
			translations.tr_meliscms_common_no,
			translations.tr_meliscms_tool_styles_delete_title, 
			translations.tr_meliscms_tool_styles_delete_details,
			function(){
				$.ajax({
			        type        : 'POST', 
			        url         : '/melis/MelisCms/ToolStyle/deleteStyle',
			        data		: dataString,
			        dataType    : 'json',
					encode		: true,
					success: function(data) {
						melisCoreTool.done(".btnDelStyle");
						if(data.success){				
								melisHelper.melisOkNotification( data.textTitle, data.textMessage );
								melisStyleTool.refreshTable();
						}else{
							melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);				
						}		
						melisCore.flashMessenger();	
					},
					error: function(xhr, textStatus, errorThrown) {
						alert( translations.tr_meliscore_error_message );
					}
			    });
			}
		);
	}
	
	function refreshTable() {
		melisHelper.zoneReload("id_meliscms_tool_styles_content", "meliscms_tool_styles_content");
	}
	
	return {
		openToolModal : openToolModal,
		saveStyleDetails : saveStyleDetails,
		deleteStyle : deleteStyle,
		refreshTable : refreshTable
		
	}
})(jQuery, window);;
$(function () {
    var $body = $("body");

    /**
     * Site selector
     */
    $body.on("change", "#id_mcgdprbanner_site_id", function () {
        /** Removing red color from highlighted fields */
        melisCoreTool.highlightErrors(1, null, "id_melis_cms_gdpr_banner_header");

        melisHelper.zoneReload("id_melis_cms_gdpr_banner_details", "melis_cms_gdpr_banner_details", {siteId: this.value});
        if (this.value > 0) {
            $body.find(".cms-gdpr-save").show();
        } else {
            $body.find(".cms-gdpr-save").hide();
        }
    });

    /**
     * Saves banner contents
     */
    $body.on("click", ".cms-gdpr-save", function () {
        if ($body.find(this).attr('disabled') === undefined) {
            melisCoreTool.pending(".cms-gdpr-save");
            var data = {};
            var languageIds = [];

            /** Get site */
            var site = $body.find("#cms_gdpr_banner_site_filter_form");
            if (site.length > 0) {
                data["filters"] = {"siteId": site.serializeArray()};
            }

            /** Get all the language options offered */
            $body.find(".mcms-gdpr-banner-language").each(function (i, language) {
                languageIds.push($body.find(language).data("langId"));
            });

            /** Get all the form data for the languages */
            var content = [];
            var bannerData = {};
            for (var i = 0; i < languageIds.length; i++) {
                content = $body.find("#id-cms-gdpr-banner-content-form-" + languageIds[i]);
                if (content.length > 0) {
                    bannerData[languageIds[i]] = content.serializeArray();
                }
            }
            data['bannerContent'] = bannerData;

            $.ajax({
                type: 'POST',
                url: '/melis/MelisCms/GdprBanner/saveBanner',
                data: data,
                dataType: 'json',
                encode: true,
                success: function(data) {
                    if (data.success) {
                        melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                        $body.find("#id_mcgdprbanner_site_id").trigger("change");
                    }
                    else {
                        melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
                        //highlight errors
                        melisCoreTool.highlightErrors(0, data.errors, "id_melis_cms_gdpr_banner");
                    }
    
                    // update flash messenger component
                    melisCore.flashMessenger();
                    melisCoreTool.done(".cms-gdpr-save");
                },
                error: function() {
                    melisCoreTool.done(".cms-gdpr-save");
                }
            });
        }
    });
});

$(document).ready(function() {
    $body = $("body");

    /**
     * Get all input values into one array on clicking save button except for the site translation inputs
     */
    $body.on("click","#btn-save-meliscms-tool-sites", function () {
        var currentTabId = activeTabId.split("_")[0];
        var dataString = $("#"+currentTabId+"_id_meliscms_tool_sites_edit_site form").serializeArray();
        // serialize the new array and send it to server
        var newEnabledModule = [];
        $.each(dataString, function( key, value ) {
            str1 = value.name;
            str2 = "moduleLoad";
            if(str1.indexOf(str2) != -1){
                newEnabledModule.push(str1.replace('moduleLoad',''));
            }
        });
        var currentEnabledModule = $("#"+currentTabId+"_currentEnabledModule").val();
        var sitesUsingModules = $("#"+currentTabId+"_sitesUsingModules").val();

        currentEnabledModule = jQuery.parseJSON(currentEnabledModule);
        sitesUsingModules = jQuery.parseJSON(sitesUsingModules);

        var sitesUsingModulesStr = "";
        $.each(sitesUsingModules,function (key, val) {
            sitesUsingModulesStr += "<br>- "+ val;
        });
        var moduleDiff = arrayDiff(currentEnabledModule,newEnabledModule);

        var siteModuleName = $("#"+currentTabId+"_siteModuleName").val();
        var isAdmin = $("#not-admin-notice").length < 1 ? true : false;

        if(moduleDiff.length > 0 && isAdmin){
            melisCoreTool.confirm(
                translations.tr_meliscms_common_save,
                translations.tr_meliscms_tool_sites_cancel,
                translations.tr_meliscms_tool_site_module_load_update_title,
                translations.tr_meliscms_tool_site_module_load_update_confirm.replace(/%s/g, sitesUsingModulesStr),
                function(){
                    dataString = $.param(dataString);
                    saveSite(dataString, currentTabId, siteModuleName);
                }
            );
        }else{
            dataString = $.param(dataString);
            saveSite(dataString, currentTabId, siteModuleName);
        }
    });

    /**
     * Function to save site
     * @param dataString
     * @param currentTabId
     * @param siteModuleName
     */
    function saveSite(dataString, currentTabId, siteModuleName)
    {
        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/Sites/saveSite?siteId='+currentTabId,
            data        : dataString,
            dataType    : 'json',
            encode		: true,
            beforeSend  : function(){
                melisCoreTool.pending("#btn-save-meliscms-tool-sites");
            },
            success: function(data) {
                if (data.success === 1) {
                    // call melisOkNotification
                    melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46' );
                    // update flash messenger values
                    melisCore.flashMessenger();
    
                    melisCoreTool.done("#btn-save-meliscms-tool-sites");
    
                    melisHelper.zoneReload(
                        currentTabId + '_id_meliscms_tool_sites_edit_site',
                        'meliscms_tool_sites_edit_site',
                        {
                            siteId: currentTabId,
                            moduleName: siteModuleName,
                            cpath: 'meliscms_tool_sites_edit_site'
                        }
                    );
    
                    //refresh table tool sites
                    $("#tableToolSites").DataTable().ajax.reload();
    
                    //refresh site tree view
                    $("input[name=left_tree_search]").val('');
                    $("#id-mod-menu-dynatree").fancytree("destroy");
                    mainTree();
                } else {
                    var container = currentTabId + "_id_meliscms_tool_sites_edit_site";
                    var errors = prepareErrs(data.errors, container);
    
                    highlightErrs(data.success, data.errors, container);
    
                    // error modal
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, errors);
                    melisCoreTool.done("#btn-save-meliscms-tool-sites");
                }
    
                // update flash messenger values
                melisCore.flashMessenger();
                melisCoreTool.done("#btn-save-meliscms-tool-sites");
            },
            error: function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            }
        });
    }

    /**
     * This Function gets the difference between two arrays
     * difference in terms of order and value
     * @param array1
     * @param array2
     */
    function arrayDiff(a1,a2) {
        var result = [];
        if(a1 != null) {
            if (a1.length > 0) {
                for (var i = 0; i < a1.length; i++) {

                    if (a2[i] !== a1[i]) {
                        result.push(a1[i]);
                    }

                }
                if (result.length < 1) {
                    for (var i = 0; i < a2.length; i++) {

                        if (a2[i] !== a1[i]) {
                            result.push(a1[i]);
                        }

                    }
                }
            }
        }
        return result;
    }

    function highlightErrs(success, errors, container) {
        if (success === 0 || success === false) {
            $("#" + container + " .form-group label").css("color", "#686868");

            $.each(errors, function (key, error) {
                $("#" + container + " .form-control[name='" + key + "']").parents(".form-group").children("label").css("color", "red");
            });
        } else {
            $("#" + container + " .form-group label").css("color", "#686868");
        }
    }

    function prepareErrs(errors, container) {
        var errs = {};

        $.each(errors, function (key, error) {
            var $input = $("#" + container + " #" + key);
            var lang = $input.data('lang');
            var label = $input.siblings('label').text();
            var lastChar = label.substr(label.length - 1);
            var exploded = key.split('_');

            if (lang != undefined) {
                label = $input.closest("div").siblings('label').text().slice(0, -1);
                errs[lang + ' ' + label] = error;
            } else {
                if (label === "") {
                    label = $input.closest("div").siblings('label').text().slice(0, -2);
                    errs[label] = error;
                } else {
                    if (lastChar === '*') {
                        label = $input.siblings('label').text().slice(0, -2);
                    }

                    if (exploded[1] === 'sdom') {
                        if (lastChar === '*')
                            label = $input.siblings('label').text().slice(0, -2) + '(' + exploded[0] + ')';
                        else
                            label = $input.siblings('label').text() + '(' + exploded[0] + ')';
                    }
                    errs[label] = error;
                }
            }
        });

        return errs;
    }

    /**
     * This will open a new tab when editing a site
     */
    $body.on("click", ".btnEditSites", function(){
        var tableId = $(this).closest('tr').attr('id');
        var name = $(this).closest('tr').find("td:nth-child(2)").text();
        var siteLang = $(this).closest('tr').find("td:nth-child(4)").text();
        var siteModule = $(this).closest('tr').find("td:nth-child(3)").text();
        var selId = $(this).closest('tr').attr("id");

        openSiteEditTab(updateSiteTitle(selId, name, siteModule, siteLang), tableId, siteModule);
    });


    /**
     * ======================================================================================
     * =============================== START CREATE SITES ===================================
     * ======================================================================================
     */
    var formData = {};
    var selectedLanguages = '';
    var domainType = '';
    var createFile = true;
    var newSite = true;
    var owlStep = null;
    var currentStepForm = '';
    var siteName = '';
    var siteLabel = '';
    var selectedDomainValue = [];
    var isUserSelectModuleOption = false;
    var domainSingleOpt = '';

    /**
     * This will delete the site
     */
    $body.on("click", "#tableToolSites .btnDeleteSite", function(e) {
        var siteId = $(this).parents("tr").attr("id");
        melisCoreTool.confirm(
            translations.tr_meliscore_common_yes,
            translations.tr_meliscore_common_no,
            translations.tr_meliscms_tool_site_delete_confirm_title,
            translations.tr_meliscms_tool_site_delete_confirm,
            function(){
                $.ajax({
                    type        : "POST",
                    url         : "/melis/MelisCms/Sites/deleteSite",
                    data		: {siteId: siteId},
                    dataType    : 'json',
                    encode		: true,
                    success		: function(data){
                        melisCoreTool.pending(".btnDeleteSite");
                        if(data.success) {
                            melisHelper.tabClose(siteId + "_id_meliscms_tool_sites_edit_site");
                            melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                            // melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
                            //refresh site table
                            $("#tableToolSites").DataTable().ajax.reload();
                        }
                        else {
                            melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, 0);
                        }
                        melisCore.flashMessenger();
                        melisCoreTool.done(".btnDeleteSite");
                    }
                });
            });
    });

    var modalUrl = "/melis/MelisCms/Sites/renderToolSitesModalContainer";

    $body.on('click', "#btn-new-meliscms-tool-sites", function () {
        melisCoreTool.pending("#btn-new-meliscms-tool-sites");
        melisHelper.createModal('id_meliscms_tool_sites_modal_container','meliscms_tool_sites_modal_add_content',true,[],modalUrl,function () {
            melisCoreTool.done("#btn-new-meliscms-tool-sites");
            currentStepForm = '';
        });
    });

    /**
     * Initialize owl carousel for step by step
     * process of creating site
     * @type {null}
     */
    window.initializeStep = function () {
        owlStep = $('.sites-steps-owl').owlCarousel({
            items: 1,
            touchDrag: false,
            mouseDrag: false,
            dotsSpeed: 500,
            navSpeed: 500,
            dots: false,
            pagination: false,
            loop: false,
            rewindNav: false,
            autoHeight: true,
            itemsMobile : false,
            itemsTablet: false,
            itemsDesktopSmall : false,
            itemsDesktop : false,
            afterMove: function (elem) {
                var current = this.currentItem;
                //hide the prev button when we are on the first step
                if(current === 0){
                    hideElement("#btn-prev-step");
                }else{
                    showElement("#btn-prev-step");
                }
            },
            beforeMove: function(elem){
                var current = this.currentItem;
                var step = elem.find(".item").eq(current).attr("data-step");
                checkStep(step);
                updateActiveStep(step);
            },
            afterInit: function(){
                $(".sites-steps-owl .tool-sites_container_fixed_width").find("label").not(":has(input)").removeClass("melis-radio-box");
                /**
                 * tooltip data container to body
                 */
                setTimeout(function(){
                    $(".sites-steps-owl .tool-sites_container_fixed_width").find("i.tip-info").attr("data-container", "body");
                }, 100);

                isUserSelectModuleOption = false;
                domainSingleOpt = "";
                currentStepForm = '';
            }
        });
    };

    $body.on("click", "#btn-next-step", function(e){
        if(owlStep !== null) {
            /**
             * check if form is not empty before
             * proceeding to the next slide
             */
            if(currentStepForm != "" && currentStepForm != "skip") {
                var form = getSerializedForm(currentStepForm);
                if (isFormEmpty(form, currentStepForm)) {
                    // $("#siteAddAlert").removeClass("hidden");
                } else {
                    $("#siteAddAlert").addClass("hidden");
                    removeFormError(currentStepForm);
                    owlStep.trigger('owl.next');
                }
            }else{
                $("#siteAddAlert").addClass("hidden");
                removeFormError(currentStepForm);
                owlStep.trigger('owl.next');
            }
        }
    });

    $body.on("click", "#btn-prev-step", function(e){
        if(owlStep !== null) {
            $("#siteAddAlert").addClass("hidden");
            removeFormError(currentStepForm);
            owlStep.trigger('owl.prev');
        }
    });

    /**
     * This will process each step
     * BEFORE proceeding to next slide
     * @param step
     */
    function checkStep(step)
    {
        //check if multi language
        var isMultiLang = $('#is_multi_language').bootstrapSwitch('status');
        //process step
        switch(step){
            case "step_1":
                //skip step 1 form
                currentStepForm = 'skip';
                /**
                 * Hide the step 2 forms
                 */
                hideElement(".step2-forms .sites_step2-multi-language");
                hideElement(".step2-forms .sites_step2-single-language");
                break;
            case "step_2":
                //include the from in step1
                var step1Obj = {};
                step1Obj.isMultiLang = isMultiLang ? true : false;
                step1Obj.data = getSerializedForm("#step2form-is_multi_lingual");
                formData.multiLang = step1Obj;
                /**
                 * determine what should we display
                 * depending if multi language or not
                 */
                if(isMultiLang){
                    showElement(".step2-forms .sites_step2-multi-language");
                    hideElement(".step2-forms .sites_step2-single-language");

                    currentStepForm = "#step2form-multi_language";
                }else{
                    showElement(".step2-forms .sites_step2-single-language");
                    hideElement(".step2-forms .sites_step2-multi-language");

                    currentStepForm = "#step2form-single_language";
                }
                /**
                 * Hide the step 3 forms
                 */
                hideElement(".sites_step3-single-domain");
                hideElement(".sites_step3-multi-domain");

                domainSingleOpt = "";
                break;
            case "step_3":
                //clear selected languages
                var lang = '';
                selectedLanguages = '';
                /**
                 * On this step, we are in step 3
                 * but we are still processing the data from
                 * the step 2 so that we can determine what
                 * we are displaying on step 3
                 */

                var langData = {};
                var domainData = {};
                /**
                 * check if site is multi lingual
                 */
                if(isMultiLang){
                    /**
                     * Load the multi lingual form
                     */
                    var multiLangFormData = getSerializedForm("#step2form-multi_language");
                    //include the step2 data into the object
                    langData = processSiteLanguage(multiLangFormData, lang);

                    var multiDomainsContainer = $(".sites_step3-multi-domain #multi-domains_container");
                    var div = $("<div/>",{
                        class: "form-group"
                    });

                    multiDomainsContainer.empty();
                    $.each(multiLangFormData, function(){
                        if(this.name == "site_selected_lang"){
                            var langData = this.value.split("-");
                            /**
                             * prepare lang info
                             */
                            if(lang == ""){
                                lang = langData[2];
                            }else {
                                lang = lang + " / " + langData[2];
                            }
                            /**
                             * This will create a text input for language
                             * depending the selected language
                             * of the user
                             */
                            var label = $("<label/>").html(langData[2]+"<sup>*</sup>").addClass("err_site-domain-"+this.value);
                            div.append(label);
                            var domainName = "site-domain-"+this.value;
                            var input = $("<input/>",{
                                type: "text",
                                class: "form-control",
                                name: domainName,
                                value: applyDomainValue(domainName),
                                required: "required",
                                title: ''
                            }).attr("data-langId", langData[0]);
                            div.append(input);
                            multiDomainsContainer.append(div);
                        }else if(this.name == "sites_url_setting"){
                            /**
                             * get the value of the site url setting to
                             * determine whether it is multi domain
                             * or not
                             */
                            if(this.value == 2){
                                //this will load the multi domain form
                                showElement(".sites_step3-multi-domain");
                                hideElement(".sites_step3-single-domain");
                                domainData.isMultiDomain = true;

                                currentStepForm = "#step3form-multi_domain";

                                domainSingleOpt = '';
                            }else{
                                //load the single domain form
                                showElement(".sites_step3-single-domain");
                                hideElement(".sites_step3-multi-domain");
                                domainData.isMultiDomain= false;

                                currentStepForm = "#step3form-single_domain";

                                if(this.value == 1){
                                    domainSingleOpt = " ("+translations.tr_melis_cms_sites_tool_add_step5_single_dom_opt_1_msg+")";
                                }else if(this.value == 3){
                                    domainSingleOpt = " ("+translations.tr_melis_cms_sites_tool_add_step5_single_dom_opt_3_msg+")";
                                }
                            }
                        }
                    });
                }else{
                    currentStepForm = "#step3form-single_domain";
                    /**
                     * Load the single domain if the site is not
                     * multi lingual
                     */
                    showElement(".sites_step3-single-domain");
                    hideElement(".sites_step3-multi-domain");

                    domainData.isMultiDomain = false;
                    //add step  2 data
                    var singLangFormData = getSerializedForm("#step2form-single_language");
                    langData = processSiteLanguage(singLangFormData, lang);
                    lang = langData.langDetails;
                }
                formData.languages = langData.data;
                formData.domains = domainData;

                selectedLanguages = '- '+translations.tr_melis_cms_sites_tool_add_header_title_lang+' : ' + lang;

                /**
                 * hide the step 4 forms
                 */
                hideElement('.step-4-datas');
                break;
            case "step_4":
                showElement('.step-4-datas');
                currentStepForm = "#step4form_module";
                /**
                 * Process the domain form
                 * to get the data
                 * @type {string}
                 */
                var domain = '';
                var domainFormData = {};
                if(formData.domains.isMultiDomain){
                    domainFormData = getSerializedForm("#step3form-multi_domain");
                    domain = 'Multiple';
                }else{
                    domainFormData = getSerializedForm("#step3form-single_domain");
                    domain = 'Single'+domainSingleOpt;
                }

                domainType = '- '+translations.tr_melis_cms_sites_tool_add_header_title_domains+' : ' + domain;
                formData.domains.data = processSiteDomain(domainFormData);

                /**
                 * Hide the finish button when
                 * your are on step4 ang below
                 */
                showElement("#btn-next-step");
                hideElement("#btn-finish-step");
                break;
            case "step_5":
                //get the data of step4
                var step4Obj = {};
                step4Obj.data = processSiteModule();
                step4Obj.newSite = newSite;
                step4Obj.createFile = createFile;
                formData.module = step4Obj;

                /**
                 * Hide the next button and
                 * show the finish button
                 * when you are on the last
                 * step
                 */
                showElement("#btn-finish-step");
                hideElement("#btn-next-step");

                /**
                 * prepare to display the user selected
                 * options on site creation
                 */
                var text = translations.tr_melis_cms_sites_tool_add_step5_new_site_using_existing_module;
                if(newSite){
                    text = translations.tr_melis_cms_sites_tool_add_step5_new_site_using_new_module;
                }
                var siteSumText = text.replace(/%siteModule/g, siteName).replace(/%siteName/g, siteLabel);
                $(".site_creation_info").empty().append(selectedLanguages, "<br />",domainType,
                    "<br/><p class='step5-message'>"+siteSumText+"</p>");
                break;
            default:
                break;
        }
    }

    /**
     * This will send a request to
     * create a new site
     */
    $body.on("click", "#btn-finish-step", function(e){
        $.ajax({
            url: "/melis/MelisCms/Sites/createNewSite",
            method: "POST",
            data: {"data" : formData},
            dataType: "JSON",
            beforeSend: function(){
                melisCoreTool.pending("#btn-finish-step");
            },
            success: function(data){
                if(data.success){
                    // call melisOkNotification
                    melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46' );

                    $('#id_meliscms_tool_sites_modal_container_container').modal('hide');
                    //re init variables
                    initVariables();
                    //refresh site table
                    $("#tableToolSites").DataTable().ajax.reload();
                    //open tabs for newly created site
                    $.each(data.siteIds, function(i, id){
                        openSiteEditTab(updateSiteTitle(id, data.siteName, data.siteModuleName), id,data.siteModuleName);
                    });
                    //refresh site tree view
                    $("input[name=left_tree_search]").val('');
                    $("#id-mod-menu-dynatree").fancytree("destroy");
                    mainTree();
                }else{
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
                }
                melisCore.flashMessenger();
                melisCoreTool.done("#btn-finish-step");
            },
            error: function(){
                console.log(translations.tr_melis_cms_sites_tool_add_create_site_unknown_error);
            }
        });
        e.preventDefault();
    });

    $body.on("change", "#is_create_new_module_for_site input[name='is_create_module']", function(){
        $(".step4-forms").removeClass("hidden");
        var value = $(this).val();
        var step4_form = $("#step4form_module");
        if(value == "yes"){
            newSite = false;
            createFile = false;
            //show the list of modules
            showElement(step4_form.find(".form-group.siteSelectModuleName"));
            //hide the other input
            hideElement(step4_form.find(".form-group.siteCreateModuleName"));
            hideElement(step4_form.find(".form-group.create_sites_file"));
            //add required field
            addAttribute(step4_form.find(".form-group select[name='siteSelectModuleName']"), "required", "required");
            //remove required field on create_sites_file
            removeAttribute(step4_form.find(".form-group input[name='create_sites_file']"), "required");
            removeAttribute(step4_form.find(".form-group input[name='siteCreateModuleName']"), "required");
        }else{
            newSite = true;
            //show the field to creat new module
            showElement(step4_form.find(".form-group.siteCreateModuleName"));
            showElement(step4_form.find(".form-group.create_sites_file"));
            //hide the other input
            hideElement(step4_form.find(".form-group.siteSelectModuleName"));
            //add required field
            addAttribute(step4_form.find(".form-group input[name='create_sites_file']"), "required", "required");
            addAttribute(step4_form.find(".form-group input[name='siteCreateModuleName']"), "required", "required");
            //remove required fields
            removeAttribute(step4_form.find(".form-group select[name='siteSelectModuleName']"), "required");
        }
        updateSliderHeight();
        isUserSelectModuleOption = true;
    });

    /**
     * Process site lang data(Single language)
     * @param form
     * @param lang
     */
    function processSiteLanguage(form, lang){
        var langData = {};
        var data = {};
        $.each(form, function(i, v){
            if(this.name == "site_selected_lang") {
                var langInfo = this.value.split("-");
                /**
                 * prepare lang info
                 */
                lang = langInfo[2];

                langData[langInfo[1]] = langInfo[0];
            }else{
                langData[v.name] = v.value;
            }

            data.data = langData;
            data.langDetails = lang;
        });
        return data;
    }

    /**
     * Process site domain data
     * @param form
     */
    function processSiteDomain(form){
        var domainData = {};
        //clear domain values
        selectedDomainValue = [];
        $.each(form, function(i, v){
            selectedDomainValue.push(v);
            if (v.name.indexOf("site-domain") >= 0){
                var dom = v.name.split("-");
                var langName = dom[3];
                domainData[langName] = {"sdom_domain" : v.value};
            }else{
                domainData[v.name] = v.value;
            }
        });
        return domainData;
    }

    /**
     * Process the site module data
     * @returns {*|jQuery}
     */
    function processSiteModule(){
        var form = getSerializedForm("#step4form_module");
        $.each(form, function(i, v){
            if(v.name == "create_sites_file"){
                //we decide only to create a file if it is a new site
                if(newSite) {
                    if (v.value == "yes") {
                        createFile = true;
                    } else {
                        createFile = false;
                    }
                }
                delete form[i];
            }else if(v.name == "siteSelectModuleName"){
                delete form[i];
                if(!newSite){
                    form.push({'site_name':v.value});
                    siteName = v.value;
                }
            }else if(v.name == "siteCreateModuleName"){
                delete form[i];
                if(newSite) {
                    form.push({'site_name': v.value});
                    siteName = v.value;
                }
            }else if(v.name == "site_label"){
                siteLabel = v.value;
            }
        });
        return form;
    }

    /**
     * Apply the selected domain
     * @param domainName
     * @returns {string}
     */
    function applyDomainValue(domainName){
        var value = "";
        $.each(selectedDomainValue, function(i, v){
            if(v.name == domainName){
                value = v.value;
                return value;
            }
        });
        return value;
    }

    /**
     * Check if form is empty
     * @param form
     * @param currentStepForm
     * @returns {boolean}
     */
    function isFormEmpty(form, currentStepForm){
        var fromInputNames = [];
        $.each(form, function(i, v){
            if(v.value != ""){
                fromInputNames.push(v.name)
            }
        });
        return showFormError(currentStepForm, fromInputNames);
    }

    /**
     * Show error on form
     * @param form
     * @param fieldNames
     * @returns {boolean}
     */
    function showFormError(form, fieldNames){
        var newModuleLabel = "";
        var newModuleValue = "";
        var newSDOmLabel = "";
        var newSDOmValue = "";
        var multiDomainErr = {};
        var errCtr = 0;
        var curForm = $(form+" input, "+form+" select");
        var domains = {};
        var domainsArr = [];
        var duplicates = [];

        /**
         * Bring back the original message
         */
        $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_create_site_required_field).addClass('hidden');

        /**
         * if user didn't select the module option
         * return an error
         */
        if(form == "#step4form_module") {
            if (!isUserSelectModuleOption) {
                $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step4_select_module_option_err).removeClass("hidden");
                return true;
            }
        }

        curForm.each(function(){
            if($(this).prop('required')){
                var inputName = $(this).attr("name");
                var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                if (jQuery.inArray(inputName, fieldNames) === -1) {
                    errlabel.addClass("fieldErrorColor");
                    errCtr++;
                } else {
                    errlabel.removeClass("fieldErrorColor");
                }

                if(inputName == "siteCreateModuleName"){
                    newModuleValue = $(this).val();
                    newModuleLabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                }

                if(inputName == "sdom_domain"){
                    newSDOmValue = $(this).val();
                    newSDOmLabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                }

                if(inputName.indexOf("site-domain-") !== -1){
                    var sdomMultiVal = $(this).val();
                    multiDomainErr[inputName] = sdomMultiVal;
                }
            }
        });

        if (errCtr > 0) {
            $("#siteAddAlert").removeClass("hidden");
            return true;
        }

        /**
         * This will avoid the user to input
         * space and special characters to
         * create a new module name
         */
        if(newModuleValue != "") {
            if (/^[A-Za-z]*$/.test(newModuleValue) === false) {
                newModuleLabel.addClass("fieldErrorColor");
                $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step4_create_module_error).removeClass("hidden");
                return true;
            }
        }

        var domainNameErrCtr = 0;
        /**
         * Check if domain name is valide
         */
        //for single domain
        if(newSDOmValue != "") {
            if(validatedDomainName(newSDOmLabel, newSDOmValue)){
                domainNameErrCtr++;
            }
        }

        //for multi domain
        $.each(multiDomainErr, function(lbl, value){
            var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + lbl).not(":has(input)");
            if(validatedDomainName(sdomMultiLbl, value)){
                domainNameErrCtr++;
            }

            // check if there are duplicate domains
            if ($.inArray(value, domainsArr) === -1) {
                domains[lbl] = value;
                domainsArr.push(value);
            } else {
                duplicates.push(lbl);
             }
        });

        if (domainNameErrCtr > 0) {
            $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_invalid_domain_name).removeClass("hidden");
            return true;
        } else {
            // multi domain
            if (!$.isEmptyObject(domains)) {
                if (duplicates.length !== 0) {
                    $.each(duplicates, function (key, lbl) {
                        var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + lbl).not(":has(input)");
                        sdomMultiLbl.addClass("fieldErrorColor");
                    });

                    $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_domain_unique_error).removeClass("hidden");
                    return true;
                } else {
                    $.ajax({
                        type: 'POST',
                        url: '/melis/MelisCms/SitesDomains/checkDomain',
                        data: {domain: domains},
                        beforeSend: function () {
                            melisCoreTool.pending("#btn-next-step");
                        },
                        success: function(data) {
                            if (!$.isEmptyObject(data.result)) {
                                $("#siteAddAlert").text('');
                                var length = data.result.length;
                                var counter = 1;
    
                                $.when(
                                    $.each(data.result, function (id, val) {
                                        var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + id).not(":has(input)");
                                        sdomMultiLbl.addClass("fieldErrorColor");
                                        var lang = sdomMultiLbl.text().slice(0, -1);
    
                                        $("#siteAddAlert").append(lang + ' - ' + translations.tr_melis_cms_sites_tool_add_step3_domain_error1 + val + translations.tr_melis_cms_sites_tool_add_step3_domain_error2);
    
                                        if (counter != length) {
                                            $("#siteAddAlert").append('</br>');
                                        }
    
                                        counter++;
                                    })
                                ).then(function () {
                                    melisCoreTool.done("#btn-next-step");
                                    $("#siteAddAlert").removeClass('hidden');
                                    return true;
                                });
                            } else {
                                melisCoreTool.done("#btn-next-step");
                                owlStep.trigger('owl.next');
                                return false;
                            }
                        },
                        error: function(xhr, textStatus, errorThrown) {
                            console.log('error on checking domain');
                            melisCoreTool.done("#btn-next-step");
                        }
                    });

                    return true;
                }
            }

            // single domain
            if (newSDOmValue != "") {
                $.ajax({
                    type : 'POST',
                    url : '/melis/MelisCms/SitesDomains/checkDomain',
                    data : {domain : newSDOmValue},
                    beforeSend : function () {
                        melisCoreTool.pending("#btn-next-step");
                    },
                    success: function(data) {
                        if (!$.isEmptyObject(data.result)) {
                            newSDOmLabel.addClass("fieldErrorColor");
                            $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_domain_error1 + data.result[0] + translations.tr_melis_cms_sites_tool_add_step3_domain_error2);
                            $("#siteAddAlert").removeClass('hidden');
                            melisCoreTool.done("#btn-next-step");
                            return true;
                        } else {
                            owlStep.trigger('owl.next');
                            melisCoreTool.done("#btn-next-step");
                            return false;
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        melisCoreTool.done("#btn-next-step");
                    }
                });

                return true;
            }
            return false;
        }
    }

    /**
     * Remove errors from form
     * @param form
     */
    function removeFormError(form){
        if(form != "" && form != "skip") {
            var curForm = $(form + " input, "+form+" select");
            curForm.each(function () {
                var inputName = $(this).attr("name");
                var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                errlabel.removeClass("fieldErrorColor");
            });
        }
    }

    function validatedDomainName(label, value){
        if (/^(www\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?[a-zA-Z0-9\-]{1,}(\.([a-zA-Z]{2,}))$/.test(value) === false) {
            label.addClass("fieldErrorColor");
            return true;
        }
        return false;
    }

    /**
     * Function to update the slider
     * height
     */
    function updateSliderHeight(){
        setInterval(function () {
            $(".sites-steps-owl").each(function () {
                $(this).data('owlCarousel').autoHeight();
            });
        });
    }

    /**
     * Open site edition tab
     * @param name
     * @param siteId
     * @param moduleName
     */
    function openSiteEditTab(name, siteId, moduleName){
        melisHelper.tabOpen(name, 'fa-book', siteId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : siteId, moduleName : moduleName }, 'id_meliscms_tool_sites', function(){
            $("#" + siteId + "_id_meliscms_tool_sites_edit_site_header site-title-tag").text(" / " + name);
        });
    }

    function updateActiveStep(step){
        var currStep = step.split("_");
        var ul = $("ul.create-site-step");
        ul.find("span.step-current").text(currStep[1]);

        //remove all active tab
        ul.each(function(){
            $(this).find("li").removeClass("active");
        });

        //set active tab
        ul.each(function(){
            $(this).find("li."+step).addClass("active");
        });

        ul.find("span.step-name").text(ul.find("li.active").attr("data-stepName"));
    }

    function initVariables()
    {
        formData = {};
        selectedLanguages = '';
        domainType = '';
        createFile = true;
        newSite = true;
        owlStep = null;
        currentStepForm = '';
        siteName = '';
        siteLabel = '';
        selectedDomainValue = [];
        isUserSelectModuleOption = false;
    }

    function updateSiteTitle(selId, siteName, siteModule, siteLang)
    {
        $("#tableToolSites tbody tr").each(function(){
            var siteNames = $(this).find("td:nth-child(2)").text();
            var siteModules = $(this).find("td:nth-child(3)").text();
            var lang = $(this).find("td:nth-child(4)").text();
            siteLang = (siteLang == undefined ? lang : siteLang);
            var id = $(this).attr("id");
            if(selId != id) {
                if (siteName == siteNames) {
                    if (siteModule == siteModules) {
                        siteName += " - " + siteLang;
                        return siteName;
                    }
                }
            }
        });
        return siteName;
    }

    /**
     *
     * @param form
     * @returns {*|jQuery}
     */
    function getSerializedForm(form){
        return $(form).serializeArray();
    }

    /**
     *
     * @param elem
     */
    function showElement(elem){
        $(elem).show();
    }

    /**
     *
     * @param elem
     */
    function hideElement(elem){
        $(elem).hide();
    }

    /**
     *
     * @param elem
     * @param attr
     * @param value
     */
    function addAttribute(elem, attr, value)
    {
        elem.attr(attr, value);
    }

    /**
     *
     * @param elem
     * @param attr
     */
    function removeAttribute(elem, attr){
        elem.removeAttr(attr);
    }

    // Disable enter on step 3 domains
    $body.on('keypress', '#step3form-single_domain  #sdom_domain', function(e) {
        return e.which !== 13;
    });

    /**
     * ================================================================================
     * ============================== END SITE CREATION ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START PROPERTIES TAB ============================
     * ================================================================================
     */
    $body.on("click", "#s404_page_id_button span", function() {
        var formId = $(this).closest('form').attr('id');
        melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#siteprop_s404_page_id');
    });

    $body.on("click", "#site_main_page_id_button span", function() {
        var formId = $(this).closest('form').attr('id');
        melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#siteprop_site_main_page_id');
    });

    $body.on("click", ".pageSelect span.input-group-addon", function() {
        var id = $(this).siblings('input').attr('id');
        var formId = $(this).closest('form').attr('id');

        melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#' + id);
    });
    /**
     * ================================================================================
     * ============================== END PROPERTIES TAB ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START DOMAINS TAB ===============================
     * ================================================================================
     */
    // Disable enter on domain input
    $body.on('keypress', '#meliscms_tool_sites_domain_form  input.form-control', function(e) {
        return e.which !== 13;
    });
    /**
     * ================================================================================
     * ================================ END DOMAINS TAB ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START LANGUAGES TAB =============================
     * ================================================================================
     */
    $body.on('change', '.sites-tool-lang-tab-checkbox', function () {
        var input = $(this).siblings('.sites-tool-lang-tab-checkbox-lang');

        if ($(this).data('active') === 'active' && !this.checked) {
            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_melis_cms_sites_tool_languages_title,
                translations.tr_melis_cms_sites_tool_languages_prompt_delete_data,
                function() {
                    input.val('true');
                }
            );
        } else {
            input.val('false');
        }
    });

    // Toggle single checkbox
    $body.on("click", ".cb-cont input[type=checkbox]", function () {
        //alert("junry");
        if ($(this).is(':checked')) {
            $(this).prop("checked", true);
            $(this).prev("span").find(".cbmask-inner").addClass('cb-active');
        } else {
            $(this).not(".requried-module").prop("checked", false);
            $(this).not(".requried-module").prev("span").find(".cbmask-inner").removeClass('cb-active');
        }
    });
    /**
     * ================================================================================
     * ============================== END LANGUAGES TAB ===============================
     * ================================================================================
     */


    window.generatePageLink = function(pageId, inputTarget){
        var pageId = (typeof pageId !== "undifined") ? pageId : null;

        inputTarget.data("idPage", pageId);

        dataString = inputTarget.data();

        if(pageId){
            $.ajax({
                type        : 'GET',
                url         : '/melis/MelisCms/Page/getPageLink',
                data		: dataString,
                dataType    : 'json',
                encode		: true,
                success		: function(res) {
                    inputTarget.val(res.link);
                }
            });
        }else{
            console.log("PageId is null");
        }
    };

    // Add Event to "Minify Button"
    $body.on("click", ".btnMinifyAssets", function(){
        var _this 	= $(this),
            siteId 	= _this.parents("tr").attr("id");

        $.ajax({
            type        : 'POST',
            url         : '/minify-assets',
            data		: {siteId : siteId},
            dataType    : 'json',
            encode		: true,
            beforeSend  : function(){
                _this.attr('disabled', true);
            },
            success		: function(data){
                if(data.success) {
                    melisHelper.melisOkNotification(data.title, 'tr_front_minify_assets_compiled_successfully');
                }else{
                    var errorTexts = '<h3>'+ melisHelper.melisTranslator(data.title) +'</h3>';
                    errorTexts += '<p><strong>Error: </strong>  ';
                    errorTexts += '<span>'+ data.message + '</span>';
                    errorTexts += '</p>';

                    var div = "<div class='melis-modaloverlay overlay-hideonclick'></div>";
                    div += "<div class='melis-modal-cont KOnotif'>  <div class='modal-content'>"+ errorTexts +" <span class='btn btn-block btn-primary'>"+ translations.tr_meliscore_notification_modal_Close +"</span></div> </div>";
                    $body.append(div);
                }

                _this.attr('disabled', false);
            }
        });
    });

    var meliscmsSiteSelectorInputDom = '';
    $body.on("click", "#meliscms-site-selector", function(){
        // initialation of local variable
        zoneId = 'id_meliscms_page_tree_id_selector';
        melisKey = 'meliscms_page_tree_id_selector';
        modalUrl = 'melis/MelisCms/Page/renderPageModal';

        $('#melis-modals-container').find('#id_meliscms_page_tree_id_selector_container').remove();
        meliscmsSiteSelectorInputDom = $(this).parents(".input-group").find("input");

        // remove last modal prevent from appending infinitely
        $("body").on('hide.bs.modal', "#id_meliscms_page_tree_id_selector_container", function () {
            $("#id_meliscms_page_tree_id_selector_container").remove();
            if($("body").find(".modal-backdrop").length == 2) {
                $("body").find(".modal-backdrop").last().remove();
            }
        });

        melisHelper.createModal(zoneId, melisKey, false, {}, modalUrl, function(){
            // Removing Content menu of Fancytree
            $.contextMenu("destroy", ".fancytree-title");
        });
    });

    $body.on("click", "#selectPageId", function(){

        var tartGetId = $('#find-page-dynatree .fancytree-active').parent('li').attr('id');

        if(typeof tartGetId !== "undefined"){
            // Getting the id from Id attribute
            var pageId = tartGetId.split("_")[1];

            if(meliscmsSiteSelectorInputDom.length){

                // Assigning id to page id input
                meliscmsSiteSelectorInputDom.val(pageId);

                if(meliscmsSiteSelectorInputDom.data("callback")){
                    callback = meliscmsSiteSelectorInputDom.data("callback");

                    if(typeof window[callback] === "function"){
                        window[callback](pageId, meliscmsSiteSelectorInputDom);
                    }else{
                        console.log("callback "+meliscmsSiteSelectorInputDom.data("callback")+" is not a function.")
                    }
                }

                // Close modal
                $(this).closest(".modal").modal("hide");
            }else{
                melisHelper.melisKoNotification("tr_meliscms_menu_sitetree_Name", "tr_meliscore_error_message");
            }
        }else{
            melisHelper.melisKoNotification("tr_meliscms_menu_sitetree_Name", "tr_meliscms_page_tree_no_selected_page");
        }
    });
});

window.sitesTableCallback = function(){
    /**
     * Disable the minify button if
     * module is not found
     */
    var minifBtn = $("#tableToolSites tbody tr[data-mod-found='false']").find(".btnMinifyAssets");
    minifBtn.prop("disabled", true);
    minifBtn.attr("disabled", true);
    minifBtn.attr("title", translations.tr_melis_cms_minify_assets_no_module_button_title);
};
/*
 *	jQuery OwlCarousel v1.31
 *
 *	Copyright (c) 2013 Bartosz Wojciechowski
 *	http://www.owlgraphic.com/owlcarousel
 *
 *	Licensed under MIT
 *
 */
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('7(B 3i.3E!=="9"){3i.3E=9(e){9 t(){}t.5v=e;q 5c t}}(9(e,t,n,r){b i={1J:9(t,n){b r=d;r.$k=e(n);r.6=e.3K({},e.3A.2c.6,r.$k.w(),t);r.29=t;r.3U()},3U:9(){b t=d;7(B t.6.2M==="9"){t.6.2M.P(d,[t.$k])}7(B t.6.2I==="2F"){b n=t.6.2I;9 r(e){7(B t.6.3F==="9"){t.6.3F.P(d,[e])}m{b n="";1C(b r 2f e["h"]){n+=e["h"][r]["1K"]}t.$k.2h(n)}t.2Y()}e.5G(n,r)}m{t.2Y()}},2Y:9(e){b t=d;t.$k.w("h-4p",t.$k.2s("2t")).w("h-4K",t.$k.2s("J"));t.$k.A({2z:0});t.2A=t.6.v;t.4L();t.5R=0;t.1M;t.1P()},1P:9(){b e=d;7(e.$k.1S().S===0){q c}e.1O();e.3H();e.$X=e.$k.1S();e.G=e.$X.S;e.4M();e.$I=e.$k.16(".h-1K");e.$L=e.$k.16(".h-1h");e.2H="Y";e.15=0;e.1W=[0];e.p=0;e.4I();e.4G()},4G:9(){b e=d;e.2V();e.31();e.4D();e.35();e.4C();e.4A();e.2x();e.4z();7(e.6.2w!==c){e.4w(e.6.2w)}7(e.6.Q===j){e.6.Q=5i}e.1e();e.$k.16(".h-1h").A("4v","4r");7(!e.$k.2p(":33")){e.34()}m{e.$k.A("2z",1)}e.56=c;e.2o();7(B e.6.39==="9"){e.6.39.P(d,[e.$k])}},2o:9(){b e=d;7(e.6.1I===j){e.1I()}7(e.6.1A===j){e.1A()}e.4n();7(B e.6.3n==="9"){e.6.3n.P(d,[e.$k])}},3o:9(){b e=d;7(B e.6.3p==="9"){e.6.3p.P(d,[e.$k])}e.34();e.2V();e.31();e.4m();e.35();e.2o();7(B e.6.3t==="9"){e.6.3t.P(d,[e.$k])}},4i:9(e){b t=d;19(9(){t.3o()},0)},34:9(){b e=d;7(e.$k.2p(":33")===c){e.$k.A({2z:0});18(e.1r);18(e.1M)}m{q c}e.1M=4g(9(){7(e.$k.2p(":33")){e.4i();e.$k.4f({2z:1},2J);18(e.1M)}},5O)},4M:9(){b e=d;e.$X.5N(\'<M J="h-1h">\').3G(\'<M J="h-1K"></M>\');e.$k.16(".h-1h").3G(\'<M J="h-1h-4d">\');e.1U=e.$k.16(".h-1h-4d");e.$k.A("4v","4r")},1O:9(){b e=d;b t=e.$k.1V(e.6.1O);b n=e.$k.1V(e.6.28);7(!t){e.$k.K(e.6.1O)}7(!n){e.$k.K(e.6.28)}},2V:9(){b t=d;7(t.6.2Z===c){q c}7(t.6.4b===j){t.6.v=t.2A=1;t.6.17=c;t.6.1q=c;t.6.21=c;t.6.24=c;t.6.25=c;t.6.26=c;q c}b n=e(t.6.4a).1m();7(n>(t.6.1q[0]||t.2A)){t.6.v=t.2A}7(B t.6.17!=="3b"&&t.6.17!==c){t.6.17.5x(9(e,t){q e[0]-t[0]});1C(b r 2f t.6.17){7(B t.6.17[r]!=="3b"&&t.6.17[r][0]<=n){t.6.v=t.6.17[r][1]}}}m{7(n<=t.6.1q[0]&&t.6.1q!==c){t.6.v=t.6.1q[1]}7(n<=t.6.21[0]&&t.6.21!==c){t.6.v=t.6.21[1]}7(n<=t.6.24[0]&&t.6.24!==c){t.6.v=t.6.24[1]}7(n<=t.6.25[0]&&t.6.25!==c){t.6.v=t.6.25[1]}7(n<=t.6.26[0]&&t.6.26!==c){t.6.v=t.6.26[1]}}7(t.6.v>t.G&&t.6.49===j){t.6.v=t.G}},4C:9(){b n=d,r;7(n.6.2Z!==j){q c}b i=e(t).1m();n.3f=9(){7(e(t).1m()!==i){7(n.6.Q!==c){18(n.1r)}5o(r);r=19(9(){i=e(t).1m();n.3o()},n.6.48)}};e(t).47(n.3f)},4m:9(){b e=d;e.2j(e.p);7(e.6.Q!==c){e.3l()}},46:9(){b t=d;b n=0;b r=t.G-t.6.v;t.$I.2i(9(i){b s=e(d);s.A({1m:t.N}).w("h-1K",3q(i));7(i%t.6.v===0||i===r){7(!(i>r)){n+=1}}s.w("h-1L",n)})},45:9(){b e=d;b t=0;b t=e.$I.S*e.N;e.$L.A({1m:t*2,V:0});e.46()},31:9(){b e=d;e.44();e.45();e.43();e.3x()},44:9(){b e=d;e.N=1N.5a(e.$k.1m()/e.6.v)},3x:9(){b e=d;b t=(e.G*e.N-e.6.v*e.N)*-1;7(e.6.v>e.G){e.C=0;t=0;e.3D=0}m{e.C=e.G-e.6.v;e.3D=t}q t},42:9(){q 0},43:9(){b t=d;t.H=[0];t.2C=[];b n=0;b r=0;1C(b i=0;i<t.G;i++){r+=t.N;t.H.2D(-r);7(t.6.14===j){b s=e(t.$I[i]);b o=s.w("h-1L");7(o!==n){t.2C[n]=t.H[i];n=o}}}},4D:9(){b t=d;7(t.6.2b===j||t.6.1s===j){t.D=e(\'<M J="h-4R"/>\').4Q("4P",!t.F.13).5E(t.$k)}7(t.6.1s===j){t.3Z()}7(t.6.2b===j){t.3Y()}},3Y:9(){b t=d;b n=e(\'<M J="h-5h"/>\');t.D.1k(n);t.1w=e("<M/>",{"J":"h-1l",2h:t.6.2T[0]||""});t.1y=e("<M/>",{"J":"h-Y",2h:t.6.2T[1]||""});n.1k(t.1w).1k(t.1y);n.z("2W.D 1Z.D",\'M[J^="h"]\',9(e){e.1n()});n.z("2a.D 2n.D",\'M[J^="h"]\',9(n){n.1n();7(e(d).1V("h-Y")){t.Y()}m{t.1l()}})},3Z:9(){b t=d;t.1o=e(\'<M J="h-1s"/>\');t.D.1k(t.1o);t.1o.z("2a.D 2n.D",".h-1p",9(n){n.1n();7(3q(e(d).w("h-1p"))!==t.p){t.1i(3q(e(d).w("h-1p")),j)}})},3T:9(){b t=d;7(t.6.1s===c){q c}t.1o.2h("");b n=0;b r=t.G-t.G%t.6.v;1C(b i=0;i<t.G;i++){7(i%t.6.v===0){n+=1;7(r===i){b s=t.G-t.6.v}b o=e("<M/>",{"J":"h-1p"});b u=e("<3Q></3Q>",{54:t.6.38===j?n:"","J":t.6.38===j?"h-5l":""});o.1k(u);o.w("h-1p",r===i?s:i);o.w("h-1L",n);t.1o.1k(o)}}t.3a()},3a:9(){b t=d;7(t.6.1s===c){q c}t.1o.16(".h-1p").2i(9(n,r){7(e(d).w("h-1L")===e(t.$I[t.p]).w("h-1L")){t.1o.16(".h-1p").Z("2d");e(d).K("2d")}})},3d:9(){b e=d;7(e.6.2b===c){q c}7(e.6.2e===c){7(e.p===0&&e.C===0){e.1w.K("1b");e.1y.K("1b")}m 7(e.p===0&&e.C!==0){e.1w.K("1b");e.1y.Z("1b")}m 7(e.p===e.C){e.1w.Z("1b");e.1y.K("1b")}m 7(e.p!==0&&e.p!==e.C){e.1w.Z("1b");e.1y.Z("1b")}}},35:9(){b e=d;e.3T();e.3d();7(e.D){7(e.6.v>=e.G){e.D.3N()}m{e.D.3L()}}},5g:9(){b e=d;7(e.D){e.D.3j()}},Y:9(e){b t=d;7(t.1G){q c}t.p+=t.6.14===j?t.6.v:1;7(t.p>t.C+(t.6.14==j?t.6.v-1:0)){7(t.6.2e===j){t.p=0;e="2k"}m{t.p=t.C;q c}}t.1i(t.p,e)},1l:9(e){b t=d;7(t.1G){q c}7(t.6.14===j&&t.p>0&&t.p<t.6.v){t.p=0}m{t.p-=t.6.14===j?t.6.v:1}7(t.p<0){7(t.6.2e===j){t.p=t.C;e="2k"}m{t.p=0;q c}}t.1i(t.p,e)},1i:9(e,t,n){b r=d;7(r.1G){q c}7(B r.6.1F==="9"){r.6.1F.P(d,[r.$k])}7(e>=r.C){e=r.C}m 7(e<=0){e=0}r.p=r.h.p=e;7(r.6.2w!==c&&n!=="4e"&&r.6.v===1&&r.F.1u===j){r.1B(0);7(r.F.1u===j){r.1H(r.H[e])}m{r.1x(r.H[e],1)}r.2q();r.4k();q c}b i=r.H[e];7(r.F.1u===j){r.1T=c;7(t===j){r.1B("1D");19(9(){r.1T=j},r.6.1D)}m 7(t==="2k"){r.1B(r.6.2u);19(9(){r.1T=j},r.6.2u)}m{r.1B("1j");19(9(){r.1T=j},r.6.1j)}r.1H(i)}m{7(t===j){r.1x(i,r.6.1D)}m 7(t==="2k"){r.1x(i,r.6.2u)}m{r.1x(i,r.6.1j)}}r.2q()},2j:9(e){b t=d;7(B t.6.1F==="9"){t.6.1F.P(d,[t.$k])}7(e>=t.C||e===-1){e=t.C}m 7(e<=0){e=0}t.1B(0);7(t.F.1u===j){t.1H(t.H[e])}m{t.1x(t.H[e],1)}t.p=t.h.p=e;t.2q()},2q:9(){b e=d;e.1W.2D(e.p);e.15=e.h.15=e.1W[e.1W.S-2];e.1W.55(0);7(e.15!==e.p){e.3a();e.3d();e.2o();7(e.6.Q!==c){e.3l()}}7(B e.6.3z==="9"&&e.15!==e.p){e.6.3z.P(d,[e.$k])}},W:9(){b e=d;e.3k="W";18(e.1r)},3l:9(){b e=d;7(e.3k!=="W"){e.1e()}},1e:9(){b e=d;e.3k="1e";7(e.6.Q===c){q c}18(e.1r);e.1r=4g(9(){e.Y(j)},e.6.Q)},1B:9(e){b t=d;7(e==="1j"){t.$L.A(t.2y(t.6.1j))}m 7(e==="1D"){t.$L.A(t.2y(t.6.1D))}m 7(B e!=="2F"){t.$L.A(t.2y(e))}},2y:9(e){b t=d;q{"-1R-1a":"2B "+e+"1z 2r","-27-1a":"2B "+e+"1z 2r","-o-1a":"2B "+e+"1z 2r",1a:"2B "+e+"1z 2r"}},3I:9(){q{"-1R-1a":"","-27-1a":"","-o-1a":"",1a:""}},3J:9(e){q{"-1R-O":"1g("+e+"T, E, E)","-27-O":"1g("+e+"T, E, E)","-o-O":"1g("+e+"T, E, E)","-1z-O":"1g("+e+"T, E, E)",O:"1g("+e+"T, E,E)"}},1H:9(e){b t=d;t.$L.A(t.3J(e))},3M:9(e){b t=d;t.$L.A({V:e})},1x:9(e,t){b n=d;n.2g=c;n.$L.W(j,j).4f({V:e},{59:t||n.6.1j,3O:9(){n.2g=j}})},4L:9(){b e=d;b r="1g(E, E, E)",i=n.5f("M");i.2t.3P="  -27-O:"+r+"; -1z-O:"+r+"; -o-O:"+r+"; -1R-O:"+r+"; O:"+r;b s=/1g\\(E, E, E\\)/g,o=i.2t.3P.5k(s),u=o!==1d&&o.S===1;b a="5z"2f t||5C.4U;e.F={1u:u,13:a}},4A:9(){b e=d;7(e.6.22!==c||e.6.23!==c){e.3R();e.3S()}},3H:9(){b e=d;b t=["s","e","x"];e.12={};7(e.6.22===j&&e.6.23===j){t=["2W.h 1Z.h","2P.h 3V.h","2a.h 3W.h 2n.h"]}m 7(e.6.22===c&&e.6.23===j){t=["2W.h","2P.h","2a.h 3W.h"]}m 7(e.6.22===j&&e.6.23===c){t=["1Z.h","3V.h","2n.h"]}e.12["3X"]=t[0];e.12["2O"]=t[1];e.12["2N"]=t[2]},3S:9(){b t=d;t.$k.z("5A.h",9(e){e.1n()});t.$k.z("1Z.40",9(t){q e(t.1f).2p("5F, 5H, 5Q, 5S")})},3R:9(){9 o(e){7(e.2L){q{x:e.2L[0].2K,y:e.2L[0].41}}m{7(e.2K!==r){q{x:e.2K,y:e.41}}m{q{x:e.52,y:e.53}}}}9 u(t){7(t==="z"){e(n).z(i.12["2O"],f);e(n).z(i.12["2N"],l)}m 7(t==="R"){e(n).R(i.12["2O"]);e(n).R(i.12["2N"])}}9 a(n){b n=n.3B||n||t.3w;7(n.5d===3){q c}7(i.G<=i.6.v){q}7(i.2g===c&&!i.6.3v){q c}7(i.1T===c&&!i.6.3v){q c}7(i.6.Q!==c){18(i.1r)}7(i.F.13!==j&&!i.$L.1V("3s")){i.$L.K("3s")}i.11=0;i.U=0;e(d).A(i.3I());b r=e(d).2l();s.3g=r.V;s.3e=o(n).x-r.V;s.3c=o(n).y-r.5y;u("z");s.2m=c;s.30=n.1f||n.4c}9 f(r){b r=r.3B||r||t.3w;i.11=o(r).x-s.3e;i.2S=o(r).y-s.3c;i.U=i.11-s.3g;7(B i.6.2R==="9"&&s.2Q!==j&&i.U!==0){s.2Q=j;i.6.2R.P(i,[i.$k])}7(i.U>8||i.U<-8&&i.F.13===j){r.1n?r.1n():r.5M=c;s.2m=j}7((i.2S>10||i.2S<-10)&&s.2m===c){e(n).R("2P.h")}b u=9(){q i.U/5};b a=9(){q i.3D+i.U/5};i.11=1N.3x(1N.42(i.11,u()),a());7(i.F.1u===j){i.1H(i.11)}m{i.3M(i.11)}}9 l(n){b n=n.3B||n||t.3w;n.1f=n.1f||n.4c;s.2Q=c;7(i.F.13!==j){i.$L.Z("3s")}7(i.U<0){i.1t=i.h.1t="V"}m{i.1t=i.h.1t="2G"}7(i.U!==0){b r=i.4h();i.1i(r,c,"4e");7(s.30===n.1f&&i.F.13!==j){e(n.1f).z("3u.4j",9(t){t.4S();t.4T();t.1n();e(n.1f).R("3u.4j")});b o=e.4O(n.1f,"4V")["3u"];b a=o.4W();o.4X(0,0,a)}}u("R")}b i=d;b s={3e:0,3c:0,4Y:0,3g:0,2l:1d,4Z:1d,50:1d,2m:1d,51:1d,30:1d};i.2g=j;i.$k.z(i.12["3X"],".h-1h",a)},4h:9(){b e=d,t;t=e.4l();7(t>e.C){e.p=e.C;t=e.C}m 7(e.11>=0){t=0;e.p=0}q t},4l:9(){b t=d,n=t.6.14===j?t.2C:t.H,r=t.11,i=1d;e.2i(n,9(s,o){7(r-t.N/20>n[s+1]&&r-t.N/20<o&&t.3m()==="V"){i=o;7(t.6.14===j){t.p=e.4o(i,t.H)}m{t.p=s}}m 7(r+t.N/20<o&&r+t.N/20>(n[s+1]||n[s]-t.N)&&t.3m()==="2G"){7(t.6.14===j){i=n[s+1]||n[n.S-1];t.p=e.4o(i,t.H)}m{i=n[s+1];t.p=s+1}}});q t.p},3m:9(){b e=d,t;7(e.U<0){t="2G";e.2H="Y"}m{t="V";e.2H="1l"}q t},4I:9(){b e=d;e.$k.z("h.Y",9(){e.Y()});e.$k.z("h.1l",9(){e.1l()});e.$k.z("h.1e",9(t,n){e.6.Q=n;e.1e();e.36="1e"});e.$k.z("h.W",9(){e.W();e.36="W"});e.$k.z("h.1i",9(t,n){e.1i(n)});e.$k.z("h.2j",9(t,n){e.2j(n)})},2x:9(){b e=d;7(e.6.2x===j&&e.F.13!==j&&e.6.Q!==c){e.$k.z("57",9(){e.W()});e.$k.z("58",9(){7(e.36!=="W"){e.1e()}})}},1I:9(){b t=d;7(t.6.1I===c){q c}1C(b n=0;n<t.G;n++){b i=e(t.$I[n]);7(i.w("h-1c")==="1c"){4q}b s=i.w("h-1K"),o=i.16(".5b"),u;7(B o.w("1X")!=="2F"){i.w("h-1c","1c");4q}7(i.w("h-1c")===r){o.3N();i.K("4s").w("h-1c","5e")}7(t.6.4t===j){u=s>=t.p}m{u=j}7(u&&s<t.p+t.6.v&&o.S){t.4u(i,o)}}},4u:9(e,t){9 s(){r+=1;7(n.2X(t.2U(0))||i===j){o()}m 7(r<=2v){19(s,2v)}m{o()}}9 o(){e.w("h-1c","1c").Z("4s");t.5j("w-1X");n.6.4x==="4y"?t.5m(5n):t.3L();7(B n.6.3r==="9"){n.6.3r.P(d,[n.$k])}}b n=d,r=0;7(t.5p("5q")==="5r"){t.A("5s-5t","5u("+t.w("1X")+")");b i=j}m{t[0].1X=t.w("1X")}s()},1A:9(){9 s(){i+=1;7(t.2X(n.2U(0))){o()}m 7(i<=2v){19(s,2v)}m{t.1U.A("3h","")}}9 o(){b n=e(t.$I[t.p]).3h();t.1U.A("3h",n+"T");7(!t.1U.1V("1A")){19(9(){t.1U.K("1A")},0)}}b t=d;b n=e(t.$I[t.p]).16("5w");7(n.2U(0)!==r){b i=0;s()}m{o()}},2X:9(e){7(!e.3O){q c}7(B e.4B!=="3b"&&e.4B==0){q c}q j},4n:9(){b t=d;7(t.6.37===j){t.$I.Z("2d")}t.1v=[];1C(b n=t.p;n<t.p+t.6.v;n++){t.1v.2D(n);7(t.6.37===j){e(t.$I[n]).K("2d")}}t.h.1v=t.1v},4w:9(e){b t=d;t.4E="h-"+e+"-5B";t.4F="h-"+e+"-2f"},4k:9(){9 u(e,t){q{2l:"5D",V:e+"T"}}b e=d;e.1G=j;b t=e.4E,n=e.4F,r=e.$I.1E(e.p),i=e.$I.1E(e.15),s=1N.4H(e.H[e.p])+e.H[e.15],o=1N.4H(e.H[e.p])+e.N/2;e.$L.K("h-1Y").A({"-1R-O-1Y":o+"T","-27-4J-1Y":o+"T","4J-1Y":o+"T"});b a="5I 5J 5K 5L";i.A(u(s,10)).K(t).z(a,9(){e.3C=j;i.R(a);e.32(i,t)});r.K(n).z(a,9(){e.2E=j;r.R(a);e.32(r,n)})},32:9(e,t){b n=d;e.A({2l:"",V:""}).Z(t);7(n.3C&&n.2E){n.$L.Z("h-1Y");n.3C=c;n.2E=c;n.1G=c}},4z:9(){b e=d;e.h={29:e.29,5P:e.$k,X:e.$X,I:e.$I,p:e.p,15:e.15,1v:e.1v,13:e.F.13,F:e.F,1t:e.1t}},4N:9(){b r=d;r.$k.R(".h h 1Z.40");e(n).R(".h h");e(t).R("47",r.3f)},1Q:9(){b e=d;7(e.$k.1S().S!==0){e.$L.3y();e.$X.3y().3y();7(e.D){e.D.3j()}}e.4N();e.$k.2s("2t",e.$k.w("h-4p")||"").2s("J",e.$k.w("h-4K"))},5T:9(){b e=d;e.W();18(e.1M);e.1Q();e.$k.5U()},5V:9(t){b n=d;b r=e.3K({},n.29,t);n.1Q();n.1J(r,n.$k)},5W:9(e,t){b n=d,i;7(!e){q c}7(n.$k.1S().S===0){n.$k.1k(e);n.1P();q c}n.1Q();7(t===r||t===-1){i=-1}m{i=t}7(i>=n.$X.S||i===-1){n.$X.1E(-1).5X(e)}m{n.$X.1E(i).5Y(e)}n.1P()},5Z:9(e){b t=d,n;7(t.$k.1S().S===0){q c}7(e===r||e===-1){n=-1}m{n=e}t.1Q();t.$X.1E(n).3j();t.1P()}};e.3A.2c=9(t){q d.2i(9(){7(e(d).w("h-1J")===j){q c}e(d).w("h-1J",j);b n=3i.3E(i);n.1J(t,d);e.w(d,"2c",n)})};e.3A.2c.6={v:5,17:c,1q:[60,4],21:[61,3],24:[62,2],25:c,26:[63,1],4b:c,49:c,1j:2J,1D:64,2u:65,Q:c,2x:c,2b:c,2T:["1l","Y"],2e:j,14:c,1s:j,38:c,2Z:j,48:2J,4a:t,1O:"h-66",28:"h-28",1I:c,4t:j,4x:"4y",1A:c,2I:c,3F:c,3v:j,22:j,23:j,37:c,2w:c,3p:c,3t:c,2M:c,39:c,1F:c,3z:c,3n:c,2R:c,3r:c}})(67,68,69)',62,382,'||||||options|if||function||var|false|this||||owl||true|elem||else|||currentItem|return|||||items|data|||on|css|typeof|maximumItem|owlControls|0px|browser|itemsAmount|positionsInArray|owlItems|class|addClass|owlWrapper|div|itemWidth|transform|apply|autoPlay|off|length|px|newRelativeX|left|stop|userItems|next|removeClass||newPosX|ev_types|isTouch|scrollPerPage|prevItem|find|itemsCustom|clearInterval|setTimeout|transition|disabled|loaded|null|play|target|translate3d|wrapper|goTo|slideSpeed|append|prev|width|preventDefault|paginationWrapper|page|itemsDesktop|autoPlayInterval|pagination|dragDirection|support3d|visibleItems|buttonPrev|css2slide|buttonNext|ms|autoHeight|swapSpeed|for|paginationSpeed|eq|beforeMove|isTransition|transition3d|lazyLoad|init|item|roundPages|checkVisible|Math|baseClass|setVars|unWrap|webkit|children|isCss3Finish|wrapperOuter|hasClass|prevArr|src|origin|mousedown||itemsDesktopSmall|mouseDrag|touchDrag|itemsTablet|itemsTabletSmall|itemsMobile|moz|theme|userOptions|touchend|navigation|owlCarousel|active|rewindNav|in|isCssFinish|html|each|jumpTo|rewind|position|sliding|mouseup|eachMoveUpdate|is|afterGo|ease|attr|style|rewindSpeed|100|transitionStyle|stopOnHover|addCssSpeed|opacity|orignalItems|all|pagesInArray|push|endCurrent|string|right|playDirection|jsonPath|200|pageX|touches|beforeInit|end|move|touchmove|dragging|startDragging|newPosY|navigationText|get|updateItems|touchstart|completeImg|logIn|responsive|targetElement|calculateAll|clearTransStyle|visible|watchVisibility|updateControls|hoverStatus|addClassActive|paginationNumbers|afterInit|checkPagination|undefined|offsetY|checkNavigation|offsetX|resizer|relativePos|height|Object|remove|apStatus|checkAp|moveDirection|afterAction|updateVars|beforeUpdate|Number|afterLazyLoad|grabbing|afterUpdate|click|dragBeforeAnimFinish|event|max|unwrap|afterMove|fn|originalEvent|endPrev|maximumPixels|create|jsonSuccess|wrap|eventTypes|removeTransition|doTranslate|extend|show|css2move|hide|complete|cssText|span|gestures|disabledEvents|updatePagination|loadContent|mousemove|touchcancel|start|buildButtons|buildPagination|disableTextSelect|pageY|min|loops|calculateWidth|appendWrapperSizes|appendItemsSizes|resize|responsiveRefreshRate|itemsScaleUp|responsiveBaseWidth|singleItem|srcElement|outer|drag|animate|setInterval|getNewPosition|reload|disable|singleItemTransition|closestItem|updatePosition|onVisibleItems|inArray|originalStyles|continue|block|loading|lazyFollow|lazyPreload|display|transitionTypes|lazyEffect|fade|owlStatus|moveEvents|naturalWidth|response|buildControls|outClass|inClass|onStartup|abs|customEvents|perspective|originalClasses|checkBrowser|wrapItems|clearEvents|_data|clickable|toggleClass|controls|stopImmediatePropagation|stopPropagation|msMaxTouchPoints|events|pop|splice|baseElWidth|minSwipe|maxSwipe|dargging|clientX|clientY|text|shift|onstartup|mouseover|mouseout|duration|round|lazyOwl|new|which|checked|createElement|destroyControls|buttons|5e3|removeAttr|match|numbers|fadeIn|400|clearTimeout|prop|tagName|DIV|background|image|url|prototype|img|sort|top|ontouchstart|dragstart|out|navigator|relative|appendTo|input|getJSON|textarea|webkitAnimationEnd|oAnimationEnd|MSAnimationEnd|animationend|returnValue|wrapAll|500|baseElement|select|wrapperWidth|option|destroy|removeData|reinit|addItem|after|before|removeItem|1199|979|768|479|800|1e3|carousel|jQuery|window|document'.split('|'),0,{}));

$(document).ready(function() {
    $body = $("body");


    function switchButtonWithoutEvent(moduleName, status)
    {
        var currentTabId = activeTabId.split("_")[0];
        $('div[data-siteModule-name="'+moduleName+'"].'+currentTabId+'_module_switch').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-"+status);
        if(status === 'on'){
            $('div[data-siteModule-name='+moduleName+'].'+currentTabId+'_module_switch>div>input').attr("checked",true);
        }else{
            $('div[data-siteModule-name='+moduleName+'].'+currentTabId+'_module_switch>div>input').removeAttr("checked");
        }
    }

    /**
     * This block of code handles the main switch in site module loading
     * where will switch on/off all modules.
     */
    $body.on("switch-change", "#site-select-deselect-all-module", function(e, data){

        var currentTabId = activeTabId.split("_")[0];
        var val = "";
        if(data.value === false){
            val = "off";
        }else{
            val = "on";
        }
        
        $("."+currentTabId+"_module_switch").each(function (index, el) {
            var moduleName = $(el).attr('data-siteModule-name');
            switchButtonWithoutEvent(moduleName,val);
        });
    });

    /**
     * variables used for module loading
     * stated detection
     */
    var selectedModule = "";
    var moduleOrigState = false;
    var isCallBackTriggered = false;
    var flag = false;

    $("body").on('switch-change', 'div[data-siteModule-name]', function (e, data) {
        var currentTabId = activeTabId.split("_")[0];
        var moduleName = $(this).attr("data-siteModule-name");
        var value 	   = data.value;
        var isInactive = false;
        var isActive   = true;
        selectedModule = moduleName;

        if(value === isInactive) {
            if(flag === false) {
                moduleOrigState = true;
            }

            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', false);

            $.ajax({
                type        : 'POST',
                url         : '/melis/MelisCms/SitesModuleLoader/getDependents',
                data		: {module : moduleName},
                dataType    : 'json',
                encode		: true,
                success: function(data) {
                    var modules    = "<br/><br/><div class='container'><div class='row'><div class='col-lg-12'><ul>%s</ul></div></div></div>";
                    var moduleList = '';

                    $.each(data.modules, function(i, v) {
                        moduleList += "<li>"+v+"</li>";

                    });

                    modules = modules.replace("%s", moduleList);

                    if(data.success) {
                        melisCoreTool.confirm(
                            translations.tr_meliscore_common_yes,
                            translations.tr_meliscore_common_nope,
                            translations.tr_meliscms_tool_site_module_load_deactivation_title,
                            data.message+modules+translations.tr_melis_cms_sites_module_loading_deactivate_module_with_prerequisites_notice_confirmation,
                            function() {
                                $.each(data.modules, function(i, v) {
                                    // this will trigger a switch-change event
                                    // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                                    // this will just trigger an animate switch
                                    switchButtonWithoutEvent(v, "off");
                                });

                                isCallBackTriggered = true;
                            },
                            function() {
                                switchButtonWithoutEvent(moduleName, "off");
                                isCallBackTriggered = true;
                            }
                        );
                    }else{
                        switchButtonWithoutEvent(moduleName, "off");
                        // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                    }
                    $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                    $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);

                    setTimeout(function(){
                        $("body").find(".confirm-modal-header").addClass("module-modal-dependency-checker");
                    },200);
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert( translations.tr_meliscore_error_message );
                }
            });
        }


        if(value === isActive) {
            if(flag === false) {
                moduleOrigState = false;
            }

            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', false);

            $.ajax({
                type        : 'POST',
                url         : '/melis/MelisCms/SitesModuleLoader/getRequiredDependencies?siteId='+currentTabId,
                data		: {module : moduleName},
                dataType    : 'json',
                encode		: true,
                success: function(data) {
                    var modules    = "<br/><br/><div class='container'><div class='row'><div class='col-lg-12'><ul>%s</ul></div></div></div>";
                    var moduleList = '';

                    $.each(data.modules, function(i, v) {
                        moduleList += "<li>"+v+"</li>";

                    });

                    modules = modules.replace("%s", moduleList);
                    if(data.success) {
                        melisCoreTool.confirm(
                            translations.tr_meliscore_common_yes,
                            translations.tr_meliscore_common_nope,
                            translations.tr_meliscms_tool_site_module_load_activation_title,
                            data.message+modules+translations.tr_melis_cms_sites_module_loading_activate_module_with_prerequisites_notice_confirmation,
                            function() {
                                $.each(data.modules, function(i, v) {
                                    // this will trigger a switch-change event
                                    // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                                    // this will just trigger an animate switch
                                    switchButtonWithoutEvent(v, "on");
                                    isCallBackTriggered = true;
                                });
                                switchButtonWithoutEvent(moduleName, "on");
                            },
                            function() {
                                switchButtonWithoutEvent(moduleName, "on");
                                isCallBackTriggered = true;
                            }
                        );


                    }else{
                        switchButtonWithoutEvent(moduleName, "on");
                        // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                    }
                    $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                    $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);

                    setTimeout(function(){
                        $("body").find(".confirm-modal-header").addClass("module-modal-dependency-checker");
                    },200);
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert( translations.tr_meliscore_error_message );
                }
            });
        }

        flag = true;
    });

    //hide the selected module on modal close
    $(document).on("hidden.bs.modal", ".module-modal-dependency-checker", function(){
        if(isCallBackTriggered === false) {
            //sends back the original state of the module
            if (moduleOrigState === true) {
                switchButtonWithoutEvent(selectedModule, "on");
            } else {
                switchButtonWithoutEvent(selectedModule, "off");
            }
        }

        isCallBackTriggered = false;
        moduleOrigState = false;
        selectedModule = "";
        flag = false;
    });

    window.moduleLoadJsCallback = function () {
        setOnOff();
        if($("#not-admin-notice").length > 0){
            $(".has-switch").bootstrapSwitch('setActive', false);
        }
    };

});

//public variable for site translation loaded table detection
var sitesTranslationLoadedTblLists = [];

$(document).ready(function(){
    var body = $("body");
    var mst_id = 0;
    var mstt_id = 0;
    var transZoneKey = "meliscms_tool_sites_site_translations";

    /**
     * This will trigger the language filter
     */
    body.on("change", ".transLangFilter", function(){
        var tableId = $(this).parents().eq(6).find('table').attr('id');
        $("#"+tableId).DataTable().ajax.reload();
    });

    /**
     * This will refresh the table
     */
    body.on("click", ".mt-tr-refresh", function(){
        var siteId = activeTabId.split("_")[0];
        melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
            $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
        });
    });

    /**
     * This will edit the translation
     */
    body.on("click", ".btnEditSiteTranslation", function(){
        var zoneId = "id_meliscms_tool_sites_site_translations_modal_edit";
        var melisKey = "meliscms_tool_sites_site_translations_modal_edit";
        var modalUrl = "/melis/MelisCms/SitesTranslation/renderToolSitesSiteTranslationModal";

        var langId = $(this).closest("tr").attr('data-lang-id');
        var siteId = $(this).closest("tr").attr('data-site-id');
        var key = $(this).closest("tr").find('td:nth-child(2)').text();

        mstt_id = $(this).closest("tr").attr('data-mstt-id');
        mst_id = $(this).closest("tr").attr('data-mst-id');

        melisHelper.createModal(zoneId, melisKey, true, {translationKey:key, langId:langId, siteId:siteId},  modalUrl);
    });

    /**
     * This will delete the translation
     */
    body.on("click", "#btnDeleteSiteTranslation", function(e){
        var siteId = activeTabId.split("_")[0];
        var t_id = $(this).closest("tr").attr('data-mst-id');
        var tt_id = $(this).closest("tr").attr('data-mstt-id');
        var id = $(this).closest("tr").attr('data-site-id');
        var obj = {};
        obj.siteId = id;

        if(t_id != 0 && t_id != "") {
            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_melis_site_translation_name,
                translations.tr_melis_site_translation_delete_confirm,
                function() {
                    $.ajax({
                        type: 'POST',
                        url: '/melis/MelisCms/SitesTranslation/deleteTranslation',
                        data: $.param(obj)
                    }).done(function (data) {
                        //process the returned data
                        if (data.success) {//success
                            melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_delete_success);
                            melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
                                $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                            });
                        }
                    });
                });
        }
        e.preventDefault();
    });

    /**
     * This will save the translation
     */
    body.on("click", ".btnSaveSiteTranslation", function(e){
        var siteId = activeTabId.split("_")[0];
        // var form = $("#site-translation-form");
        var form = $("form[name='sitestranslationform']").serializeArray();

        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/SitesTranslation/saveTranslation',
            data		   : $.param(form)
        }).done(function(data) {
            //process the returned data
            if(data.success){//success
                if(mst_id == 0) {
                    melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_inserting_success);
                }else{
                    melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_update_success);
                }
                //remove highlighted label
                // melisCoreTool.highlightErrors(1, null, "site-translation-form");
                $("#modal-site-translation").modal("hide");
                melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey,{siteId:siteId}, function(){
                    mst_id = 0;
                    mstt_id = 0;
                    $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                });
            }else{//failed
                //show errors
                melisHelper.melisKoNotification(translations.tr_melis_site_translations, translations.tr_melis_site_translation_save_failed, data.errors);
                //highlight errors
                $.each(data.langErrorIds, function(i, langId){
                    melisCoreTool.highlightErrors(0, data.errors, langId+"_site-translation-form");
                });
            }
        });
        e.preventDefault();
    });

    /**
     * adjust table column to make it responsive on mobile
     * when the user click on sites translation tab
     */
    body.on("shown.bs.tab", ".sites-tool-tabs a[data-toggle='tab']", function(){
        if ($(window).width() <= 768) {
            var target = $(this).attr("href");
            target = target.replace("#", "");
            var cleanString = target.replace(/\d+/g, '');
            if (target != "") {
                var transId = target.split("_");
                if (cleanString == "_id_meliscms_tool_sites_site_translations") {
                    $("#" + transId[0] + "_tableMelisSiteTranslation").DataTable().columns.adjust().responsive.recalc();
                }
            }
        }
    });
});

/**
 * Callback for site translation table
 *
 */
window.siteTransTableCallBack = function(data, tblSetting){
    /**
     * get the current site id
     */
    var siteId = activeTabId.split("_")[0];
    /**
     * Remove the delete button if the
     * translation is came from the file
     */
    $("#"+siteId+"_tableMelisSiteTranslation tbody tr[data-mst-id='0']").find("#btnDeleteSiteTranslation").remove();
};

/**
 * This will prepare to add the additional
 * data of the translation
 * @param data
 */
window.initAdditionalTransParam = function(data){
    var siteId = activeTabId.split("_")[0];
    data.site_translation_language_name = $('#'+siteId+'_siteTranslationLanguageName').val();
    data.siteId = siteId;
};
