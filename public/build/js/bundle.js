// Melis Cms Functionalities 
var melisCms = (function(){
	
	// CACHE SELECTORS
	var $body = $("body");
	
	// ---=[ BUG FIX ] =---  TINYMCE POPUP MODAL FOCUS 
	var windowOffset
	window.scrollToViewTinyMCE = function(dialogHeight, iframeHeight){
		
		// window scroll offest
		windowOffset = $(window).scrollTop();
		
		if( dialogHeight && iframeHeight){
			
			var scrollTop = (iframeHeight /2 ) - (dialogHeight);
			$("html, body").animate({scrollTop: scrollTop }, 300);
			
			/* console.log("open pop up"); */
		}
		else{
			/* console.log("close pop up");
			console.log("offset = "+ windowOffset); */
			return windowOffset;
		}
	} 
	window.scrollOffsetTinyMCE = function(){
		return windowOffset;
	}
	
	$("body").on("click", ".mce-btn", function(){
		
		var mcePopUp = $("#mce-modal-block").length;
		
		if(mcePopUp){
			
			
			if($("iframe.melis-iframe").length){
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
				
				/* console.log("bodyOffsetTop = " + bodyOffsetTop);
				console.log("windowHeight = " + windowHeight);
				console.log("dialogHeight = " + dialogHeight);		
				console.log("has popup = "+ dialogTop); */
				$(".mce-floatpanel.mce-window").css("top", dialogTop);
				$("html, body").animate({scrollTop: dialogTop }, 300);
			}else{
				$("#mce-modal-block").css('z-index',1049);
				$(".mce-floatpanel.mce-window").css('z-index', 1050);
			}
		}
		else{
			/* console.log("no popup"); */
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
	function newPage(){
   	  	//close page creation tab and open new one (in case if its already open - updated parent ID)
		var pageID = $(this).data('pagenumber');
   	  	melisHelper.tabClose('0_id_meliscms_page');
   	  	melisHelper.tabOpen( translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation',  { idPage: 0, idFatherPage: pageID } ); 
		
	}
	
		// SAVE PAGE
	function savePage(idPage){
		
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
		}).success(function(data){
			
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
		    		var newPageZoneId = data.datas.idPage + pageCreationId.substring(1, pageCreationId.length) 
		    	
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
	    	
		}).error(function(xhr, textStatus, errorThrown){
			alert( translations.tr_meliscore_error_message );
		});
		
	}
	
	// PUBLISH PAGE 
	function publishPage(idPage){
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
		}).success(function(data){
			if(data.success === 1){
				// reload and expand the treeview
	    		refreshTreeview(data.datas.idPage);
	    		
	    		// set the online/offline button to 'online'
	    		$('.page-publishunpublish').bootstrapSwitch('setState', true, true);
	
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
			}
			else
			{
				// error modal
	    		melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
	    		
	    		//color the error field in red
	    		colorRedError(data.success, data.errors, data.datas.item_zoneid);
			}
			
			// update flash messenger values
	    	melisCore.flashMessenger();
	    	
		}).error(function(xhr, textStatus, errorThrown){
			alert( translations.tr_meliscore_error_message );
		});
		
		}
		
	// UNPUBLISH PAGE
	function unpublishPage(idPage){
		var pageNumber = (typeof idPage === 'string') ? idPage :  $(this).data("pagenumber");
		
		$.ajax({
			type        : 'GET', 
		    url         : '/melis/MelisCms/Page/unpublishPage?idPage='+pageNumber,
		    dataType    : 'json',
		    encode		: true
		}).success(function(data){
			if(data.success === 1){
				// reload and expand the treeview
	    		refreshTreeview(pageNumber);
				
	    		// call melisOkNotification 
	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
	    		
	    		// update flash messenger values
		    	melisCore.flashMessenger();
			}
			else{
				// show error modal
				melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
			}
			
			// update flash messenger values
	    	melisCore.flashMessenger();
			
	    	// reload the preview in edition tab
	    	melisHelper.zoneReload(pageNumber+'_id_meliscms_page','meliscms_page', {idPage:pageNumber});
	    	
		}).error(function(xhr, textStatus, errorThrown){
			alert( translations.tr_meliscore_error_message );
		});
	}
	
	function clearPage(){
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
  				}).success(function(data){
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
  					else{
  						melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
  					}  
  				}).error(function(xhr, textStatus, errorThrown){
  					alert( translations.tr_meliscore_error_message );
  				});
		});
	}
	
	// Delete Page
	function deletePage(){
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
	  				}).success(function(data){
	  					if( data.success === 1){
							
	  						//close the page 
	  						melisHelper.tabClose(zoneId);
								    				  
	  						// notify deleted page
	  						melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
	  						
	  						// update flash messenger values
	  				    	melisCore.flashMessenger();
	  				    	
														
	  					}
	  					else{
	  						melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
	  					}  
	  				}).error(function(xhr, textStatus, errorThrown){
	  					alert( translations.tr_meliscore_error_message );
	  				});
			});
		}		
	}
	
	// Deactivating page edition action buttons
    function disableCmsButtons(id)
    {
        $("div.make-switch label on, off").parent().css("z-index", -1).parents("div.make-switch").css("opacity", 0.5);
        $("#"+id+"_id_meliscms_page_action_tabs").addClass('relative').prepend("<li class='btn-disabled'></li>");
    }
    
    // Activating page edition action buttons
    function enableCmsbuttons(id)
    {
        $("#"+id+"_action_tabs").removeClass('relative');
        $("#"+id+"_action_tabs li.btn-disabled").remove();
        
        $("div.make-switch label on, off").parent().css("z-index", 1).parents("div.make-switch").css("opacity", 1);
    }
	
	// RELOAD THE TREEVIEW AND SET A NODE PAGE ACTIVE
	function refreshTreeview(pageNumber, self){
		optionalArg = (typeof self === 'undefined') ? 0 : self;
	  	$.ajax({
  	        url         : '/melis/MelisCms/TreeSites/getPageIdBreadcrumb?idPage='+pageNumber+'&includeSelf='+optionalArg,
  	        encode		: true,
  	        dataType    : 'json',
  	    }).success(function(data){
				
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
  	    	
  	    }).error(function(xhr, textStatus, errorThrown){
  	    	// error modal
  	    	alert( translations.tr_meliscore_error_message );
  	    });
	}

	// DISPLAY SETTING FOR PAGES
	function displaySettings(){
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
	function publishUnpublish(e, datas){
		var pageNumber = $(this).data('pagenumber').toString();
    	
    	if( datas.value === true){
    		publishPage(pageNumber);
    	}
    	else{
    		unpublishPage(pageNumber);
    	}
	}
	
	// INPUT CHAR COUNTER IN SEO TAB
	function charCounter(event){
	
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
	function cmsTabEvents(){
		// trigger keyup on SEO tabs
		$("form[name='pageseo'] input[name='pseo_meta_title']").trigger('keyup');
		$("form[name='pageseo'] input[textarea='pseo_meta_description']").trigger('keyup');
		
		// give iframe the calculated height based from the content
		var iHeight = $("#"+ activeTabId + " .melis-iframe").contents().height()+20;  
		$("#"+ activeTabId + " .melis-iframe").css("height", iHeight);
	}
	
	
	// REFRESH PAGE TAB (historic, versionining etc)
    function refreshPageTable(){
    	var zoneParent = $(this).parents(".melis-page-table-cont");
    	var zoneId = zoneParent.attr("id");
    	var melisKey = zoneParent.data("meliskey");
    	var pageId = zoneParent.data("page-id");
    	melisHelper.zoneReload(zoneId, melisKey, { idPage: pageId }); 
    }
    
    function disableCmsButtons(id)
    {
        $("#" + activeTabId + " .page-publishunpublish").append("<div class='overlay-switch' style='width: 100%;height: 100%;" +
            "position: absolute;top: 0;" +
            "left: 0;z-index: 99999999;" +
            "cursor: wait; '></div>");
        $("#"+id+"_id_meliscms_page_action_tabs").addClass('relative').prepend("<li class='btn-disabled'></li>");
    }
    
    function enableCmsbuttons(id)
    {
        $("#"+id+"_action_tabs").removeClass('relative');
        $("#"+id+"_action_tabs li.btn-disabled").remove();
        $("#" + activeTabId + " .overlay-switch").remove();
    }
	
    // IFRAME HEIGHT CONTROLS (for onload, displaySettings & sidebar collapse)
    function iframeLoad(){
    	var height = $("#"+ activeTabId + " .melis-iframe").contents().height();
    	$("#"+ activeTabId + " .melis-iframe").css("height", height);
    	$("#"+ activeTabId + " .melis-iframe").css("min-height", "700px");  
    	
		// Check and Get all Editable Value and dataTags from Editor TinyMCE
		// $.ajax({
		// 	type        : 'POST',
		// 	url         : '/melis/MelisCms/PageEdition/savePageSessionPlugin?idPage='+$("#"+ activeTabId + " iframe").attr('data-iframe-id'),
		// 	dataType    : 'json',
		// 	encode		: true
		// });
		
		// Activating page edition button action
		melisCms.enableCmsButtons(activeTabId);

        // PAGE ACCESS user rights checking
        $.ajax({
            url         : '/melis/MelisCms/TreeSites/canEditPages',
            encode		: true
        }).success(function(data){
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
        }).error(function(xhr, textStatus, errorThrown){
        	alert( translations.tr_meliscore_error_message );
        });


        // SAVE user rights checking
        $.ajax({
            url         : '/melis/MelisCms/Page/isActionActive?actionwanted=save',
            encode		: true
        }).success(function(data){
        	if(data.active === 0){
        		$("body").addClass('disable-create');
        	}
        	else{
        		$("body").removeClass('disable-create');
        	}
        }).error(function(xhr, textStatus, errorThrown){
        	alert( translations.tr_meliscore_error_message );
        });

        // DELETE user rights checking
        $.ajax({
            url         : '/melis/MelisCms/Page/isActionActive?actionwanted=delete',
            encode		: true
        }).success(function(data){
        	if(data.active === 0){
        		$("body").addClass('disable-delete');
        	}
        	else{
        		$("body").removeClass('disable-delete');
        	}
        }).error(function(xhr, textStatus, errorThrown){
        	alert( translations.tr_meliscore_error_message );
        });
    }
	
	// WINDOW SCROLL FUNCTIONALITIES ========================================================================================================
	if( melisCore.screenSize >= 768){
		jQuery(window).scroll(function(){

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
    
    //  refresh page tab (historic, versionining etc)
    $body.on("shown.bs.tab", '.melis-refreshPageTable', refreshPageTable );
    

    
    
    
    
     
    
    
    
    
	/* 
	* RETURN ======================================================================================================================== 
	* include your newly created functions inside the array so it will be accessable in the outside scope
	* sample syntax in calling it outside - melisCms.savePage;
    */
	
	return{
		//key - access name outside									// value - name of function above
		
		// page actions
		savePage 										: 			savePage,
		publishPage 									: 			publishPage,
		unpublishPage 									: 			unpublishPage,
		
		//refresh treeview
		refreshTreeview									:			refreshTreeview,
        disableCmsButtons								: 			disableCmsButtons,
		enableCmsButtons								: 			enableCmsbuttons,
		
		iframeLoad										:			iframeLoad,
	};

})();
(function ($, window, document) {

    // On Load
    $(window).on('load', function () {
    	window.mainTree = function(completeEvent){
            var melisExtensions;
            if( melisCore.screenSize <= 767 ) {
                melisExtensions = ['contextMenu',  'filter'];
			} else {
                melisExtensions = ['contextMenu', 'dnd', 'filter'];
			}

	        $('#id-mod-menu-dynatree').fancytree({
	        	extensions: melisExtensions,
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
	                  'new': { 'name': translations.tr_meliscms_menu_new, 'icon': 'paste' },
	                  'edit': { 'name': translations.tr_meliscms_menu_edit, 'icon': 'edit' },
	                  'delete': { 'name': translations.tr_meliscms_menu_delete, 'icon': 'delete' },
	                  'dupe': { 'name': translations.tr_meliscms_menu_dupe, 'icon': 'copy' },
	                },
	                actions: function(node, action, options) {
	                  	if(action === 'new'){
	                	  var data = node.data;
	                	  
	                	  //close page creation tab and open new one (in case if its already open - updated parent ID)
	                	  melisHelper.tabClose('0_id_meliscms_page');
	                	  melisHelper.tabOpen( translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation',  { idPage: 0, idFatherPage: data.melisData.page_id } );                	  
	                  	}
	                  	if(action === 'edit'){
	                	  var data = node.data;
	              		  melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { idPage: data.melisData.page_id } ); 
	                 	}
						if(action === 'delete'){
	
							var data = node.data;
							var zoneId = data.melisData.item_zoneid;
							var idPage = data.melisData.page_id;	  
	                		var parentNode = ( node.getParent().key == 'root_1') ? -1 : node.getParent().key;
							// var parentNode = ( node.key == 'root_1') ? -1 : node.getParent().key;	
	                		
							// check if page to be delete is open or not
							var openedOrNot = $(".tabsbar a[data-id='"+zoneId+"']").parent("li");
	                	  
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
										url         : '/melis/MelisCms/Page/deletePage?idPage='+idPage,
										encode		: true 
		  	                		}).success(function(data){
										if( data.success === 1){
		  	                				//close the page if its open. do nothing if its not open
		  	                				if(openedOrNot.length === 1){
												melisHelper.tabClose(zoneId);
		  	                            	}
		  	                				  
		  	                				// notify deleted page
		  	                				melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
		  	                				 
			  	                			// update flash messenger values
			  	          				    melisCore.flashMessenger();				    	
		  	    	            			
		  	    	            		} else {
		  	                				melisHelper.melisKoNotification( data.textTitle, data.textMessage, data.errors, '#000' );
		  	                			}
		  	                		}).error(function(xhr, textStatus, errorThrown){
										alert( translations.tr_meliscore_error_message );
		  	                		});
								});  
						}
						if(action === 'dupe'){
		                	var data = node.data;
							// melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { sourcePageId: data.melisData.page_id } ); 
		              		
		              		// initialation of local variable
		          			zoneId = 'id_meliscms_tools_tree_modal_form_handler';
		          			melisKey = 'meliscms_tools_tree_modal_form_handler';
		          			modalUrl = 'melis/MelisCms/TreeSites/renderTreeSitesModalContainer';
		          			// requesitng to create modal and display after
		          	    	melisHelper.createModal(zoneId, melisKey, false, { 'sourcePageId' : data.melisData.page_id }, modalUrl, function(){
		          	    	});
		                }
	               }
	        	},
			    lazyLoad: function(event, data) {
			      // get the page ID and pass it to lazyload
			     var pageId = data.node.data.melisData.page_id;
			      data.result = {
			    		  url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId='+pageId,
			    		  data: {
			    			  mode: 'children',
			    			  parent: data.node.key
			    		  },
			    		  cache: false,
			      }

			    },
    			create: function(event, data) {
    				melisHelper.loadingZone( $('#treeview-container') );
				},
				init: function(event, data, flag) {
			        melisHelper.removeLoadingZone( $('#treeview-container') );
			           // focus search box
			           $("input[name=left_tree_search]").focus();

			        var tree = $("#id-mod-menu-dynatree").fancytree("getTree");
			        
		           if(tree.count() === 0) {
		        	   
			            $(".meliscms-search-box.sidebar-treeview-search").hide();
			            // Checking if the user has a Page rights to access
			            // -1 is the value for creating new page right
			            $.get('/melis/MelisCms/TreeSites/checkUserPageTreeAccress', {idPage: -1}, function(res){
			            	if(res.isAccessible){
			            		$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>"+ translations.tr_meliscms_create_page +"</span></div>");
			            	}
			            });
			            
		            } else {
			            $(".meliscms-search-box.sidebar-treeview-search").show();
			            $("#id-mod-menu-dynatree .create-newpage").remove();
		            }
			    },
	        	click: function (event, data) {
	        		targetType = data.targetType;
	        		if(targetType === "title"){
	        			data.node.setExpanded();
	        			
	        			// open page on click on mobile . desktop is double click
	        			if( melisCore.screenSize <= 1024 ){
	        				var data = data.node.data;
	                		melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { idPage: data.melisData.page_id } );
	        			}
	        		}	
	        		$('.hasNiceScroll').getNiceScroll().resize();
	            },
	            dblclick: function(event, data) {
	            	// get eventType to know what was clicked the 'expander (+-)' or the title
	        		//targetType = data.targetType;
	        		
	        		// open tab and page
	    			var data = data.node.data;
	        		melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { idPage: data.melisData.page_id } );
	        	
	        		$('.hasNiceScroll').getNiceScroll().resize();
	        		 
	        		return false; 
	            },
	            loadChildren: function(event, data){
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
	            renderNode: function (event, data) {
	            	// removed .fancytree-icon class and replace it with font-awesome icons
	                $(data.node.span).find('.fancytree-icon').addClass(data.node.data.iconTab).removeClass('fancytree-icon');
	
	                if(data.node.statusNodeType !== 'loading'){
	                	
	                	if( data.node.data.melisData.page_is_online === 0){
	                		$(data.node.span).find('.fancytree-title, .fa').css("color","#686868");     
	                	}
	                	
	                	if( data.node.data.melisData.page_has_saved_version === 1){
	                		//check if it has already 'unpublish' circle - avoid duplicate circle bug
	                		if( $(data.node.span).children("span").hasClass("unpublish") == false  ){
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
		            	 if( melisCore.screenSize >= 1024){
		            		 // determine if the node is draggable or not
		            		 if(!data.node.data.dragdrop){
			                	  return false;
			                  }
			                  else{
			                	  return true;
			                  }
		            	 }
		            	 else{
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
                        node.setExpanded(true).always(function(){
                        // This function MUST be defined to enable dropping of items on the tree.
                        // data.hitMode is 'before', 'after', or 'over'.
                        // We could for example move the source to the new target:

                        // catch if its 'root_*' parent
                        var isRootOldParentId = data.otherNode.getParent().key.toString();
                        var oldParentId = ( isRootOldParentId.includes('root') ) ? -1 : data.otherNode.getParent().key ;

                        // move the node to drag parent ------------------------------------------------

                        data.otherNode.moveTo(node, data.hitMode);

                        var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

                        var draggedPage = data.otherNode.key

                        // catch if its 'root_*' parent
                        var isRootNewParentId = node.getParent().key.toString();
                        var newParentId  = ( isRootNewParentId.includes('root') ) ? -1 : node.getParent().key ;

                        if(data.hitMode == 'over'){
                            newParentId  =  data.node.key ;
                        }

                        var newIndexPosition = data.otherNode.getIndex()+1;

	                	//send data to apply new position of the dragged node
						var datastring = {
							idPage				:  draggedPage,
							oldFatherIdPage		:  oldParentId,
							newFatherIdPage		:  newParentId,
							newPositionIdPage	: newIndexPosition
						};

	                	$.ajax({
	                	    url         : '/melis/MelisCms/Page/movePage',
	                	    data        : datastring,
	                	    encode		: true
	                	}).success(function(data){
	                	}).error(function(xhr, textStatus, errorThrown){
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
    $("body").on("click","#id-mod-menu-dynatree .create-newpage .btn", function(){
    	melisHelper.tabOpen( translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation',  { idPage: 0, idFatherPage: '-1' } );  
    });
    
    $("body").on("click", '#sourcePageIdFindPageTree span', function(){
		melisLinkTree.createInputTreeModal('#sourcePageId');
	});
    
    $("body").on("click", '#destinationPageIdFindPageTree span', function(){
		melisLinkTree.createInputTreeModal('#destinationPageId');
	});
    
    $("body").on("click", 'button[data-inputid="#destinationPageId"]', function(){
    	$('[name="use_root"]').each(function(){
    		if($(this).is(':checked')){
    			$(this).prop( "checked", false );
    		}
    	})
    	$('.remember-me-cont .cbmask-inner').removeClass('cb-active');
    	$("#destinationPageId").prop('disabled', false);
    });
    
    $("body").on('change', '[name="use_root"]', function(){
    	
    	if($('[name="use_root"]:checked').length){
    		
    		$("#destinationPageId").val("");
    		$("#destinationPageId").prop('disabled', true);
    	}else{
    		
    	$("#destinationPageId").prop('disabled', false);
    	}
	})
    
    // use this callback to re-initialize the tree when its zoneReloaded
    window.treeCallBack = function(){
    	if( $("#id-mod-menu-dynatree").children().length == 0 ){
    		mainTree();
    	}
    }
    
    $("body").on("click", "#duplicatePageTree", function(){
    	var dataString = $('#duplicatePageTreeForm').serializeArray();
    	var parentNode = $('#duplicatePageTreeForm input[name="destinationPageId"]').val();	
    	melisCoreTool.pending("#duplicatePageTree");
    	$("#duplicatePageTree").find('i').removeClass();
    	$("#duplicatePageTree").find('i').addClass('fa fa-spinner fa-spin');
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/TreeSites/duplicateTreePage',
	        data        : dataString,
	        dataType    : 'json',
	        encode		: true
	    }).done(function(data) {
	    	
	    	if(data.success) {
	    		$('#id_meliscms_tools_tree_modal_form_handler_container').modal('hide');
	    		melisCms.refreshTreeview(parentNode, 1);
	    		// clear Add Form
	    		melisHelper.melisOkNotification( data.textTitle, data.textMessage );
	    	}
	    	else {
	    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
	    		melisCoreTool.highlightErrors(data.success, data.errors, "stylesForm");
	    	}
	    	melisCore.flashMessenger();
	    	melisCoreTool.done("#duplicatePageTree");
	    	$("#duplicatePageTree").find('i').removeClass();
	    	$("#duplicatePageTree").find('i').addClass('fa fa-save');
	    }).fail(function(){
	    	alert( translations.tr_meliscore_error_message );
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
		if(!melisCoreTool.isTableEmpty("tableToolTemplateManager")) {
			melisCoreTool.exportData('/melis/MelisCms/ToolTemplate/exportToCsv?filter='+searched);
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
	
	var meliscmsSiteSelectorInputDom = '';
	addEvent("#meliscms-site-selector", function(){
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


	
	addEvent("#selectPageId", function(){
        
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
	}
	
	
	// Add Event to "Add New Site" button
	addEvent("#id_meliscms_tool_site_header_add", function(e) {
		melisCoreTool.showOnlyTab('#modal-cms-tool-site', '#id_meliscms_tool_site_modal_add');
		melisCoreTool.hideAlert("#siteaddalert");
		melisCoreTool.resetLabels("#siteCreationForm");
	});
	
	// Add Event to "Edit Site" button
	addEvent(".btnEditSite", function(e) {
		
		melisCoreTool.showOnlyTab('#modal-cms-tool-site', '#id_meliscms_tool_site_modal_edit');
		var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
		$(".widget #siteEditionForm").append(tempLoader);
		var tdParent = $(this).parents("td");
		var trParent = tdParent.parents("tr");
		var siteID = trParent.attr("id");
		var options = $("#siteEditionForm #id_sdom_env");

		$("#siteEditionForm #id_site_id").val(siteID);
		melisCoreTool.hideAlert("#siteeditalert");
		melisCoreTool.resetLabels("#siteEditionForm");
		$(options).show();
		melisCoreTool.clearForm("siteEditionForm");
		// clear current environments first
		$(options).html("");
		
		// retrieve environments
		$.ajax({
	        type        : 'GET', 
	        url         : '/melis/MelisCms/Site/getSiteEnvironment',
	        data		: {siteId: siteID},
	        dataType    : 'json',
	        encode		: true,
	        success		: function(res) {
			    // environment retrieval
	    		$.getJSON("/melis/MelisCms/Site/getSiteEnvironments", {siteId: siteID}, function(json)
	    		{
	    		    $.each(json.data, function(idx, item){
	    		    	options.append($("<option />").val(item).text(item));
	    		    });
	    		    
	    		    $('#siteEditionForm #id_sdom_env>option:eq(0)').prop('selected', true);
	    		    
				    var env = res.data;
				    
				    getSiteInfo(siteID, env);
				    
					var checkValue = setInterval(function() {
						var value = $("#siteEditionForm #id_site_name").val();
						if(value !== null || value !== "") {
							clearInterval(checkValue);
							$(".overlay-loader").remove();
						}
					},300);
	    	    });
	        }
		});
	});
	
	// Add Event to "Delete Button"
	addEvent(".btnDeleteSite", function(e) {
		var getId = $(this).parents("tr").attr("id");
		melisCoreTool.confirm(
			translations.tr_meliscore_common_yes,
			translations.tr_meliscore_common_no,
			translations.tr_meliscms_tool_site_delete_confirm_title, 
			translations.tr_meliscms_tool_site_delete_confirm, 
			function(){
				$.ajax({
			        type        : 'POST', 
			        url         : '/melis/MelisCms/Site/deleteSite',
			        data		: {id: getId},
			        dataType    : 'json',
			        encode		: true,
			        success		: function(data){
			        	melisCoreTool.pending(".btnDeleteSite");
		    	    	if(data.success) {
		    	    		melisHelper.melisOkNotification(data.textTitle, data.textMessage);
		    	    		melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
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
	
	addEvent("#btnDelEnv", function(e) {
		var isClicked = false;
		
		melisCoreTool.confirm(
			translations.tr_meliscore_common_yes,
			translations.tr_meliscore_common_no,
			translations.tr_meliscms_tool_site_env_delete_confirm_title, 
			translations.tr_meliscms_tool_site_env_delete_confirm, 
			function() {
				var dataString = new Array();
				var options = $("#siteEditionForm #id_sdom_env");
				var env = $(options).val();
				var siteID = $("#siteEditionForm #id_site_id").val();
				var site404PageId = $("#siteEditionForm #id_s404_page_id").val();
				
				dataString.push({
					name: "siteid",
					value: siteID
				});
				
				dataString.push({
					name: "env",
					value: env
				});
				
				dataString.push({
					name: "site404Page",
					value: site404PageId
				});
				
				dataString = $.param(dataString);
				$.ajax({
			        type        : 'POST', 
			        url         : '/melis/MelisCms/Site/deleteSiteById',
			        data		: dataString,
			        dataType    : 'json',
			        encode		: true,
			        success		: function(data){
		    	    	if(data.success) {
		    	    		$(options).html("");
		    	    		melisHelper.melisOkNotification(data.textTitle, data.textMessage);
		    	    		// environment retrieval
		    	    		$.getJSON("/melis/MelisCms/Site/getSiteEnvironments", {siteId: siteID}, function(json) 
		    	    		{		
		    	    		    $.each(json.data, function(idx, item) 
		    	    		    {
		    	    		    	options.append($("<option />").val(item).text(item));
		    	    		    });
		    	    		    $('#siteEditionForm #id_select_env>option:eq(0)').prop('selected', true);
		    	    		    var env = $(options).val();
		    	    		    getSiteInfo(siteID, env);
		    	    	    });	
		    	    	}
		    	    	else {
		    	    		melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, 0);
		    	    	}
		    	    	melisCore.flashMessenger();
			        }	
			    });
		});
	});
	
	// Add Event to New Site Modal Save Button
	addEvent("#btnSiteAdd", function(e){ 
		
		var dataString = $("#siteCreationForm").serializeArray();
		
		if (e.originalEvent !== undefined){
			dataString.push({
				name : 'gen_site_mod',
				value: true
			});
		}
		
		melisCoreTool.pending("#btnSiteAdd");
		
		melisCoreTool.hideAlert("#siteaddalert");
		
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/saveSite',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true
		}).done(function(data){
			if(data.success){
				$('#modal-cms-tool-site').modal('hide');
				
				newSiteConfirmation(data.siteId);
				
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}else{
				
				melisCoreTool.highlightErrors(data.success, data.errors, "siteCreationForm");
				
				if(data.textMessage === "tr_meliscms_tool_site_directory_exist"){
					
					melisCoreTool.confirm(
						translations.tr_meliscore_common_yes,
						translations.tr_meliscore_common_no,
						translations.tr_meliscms_tool_site_add, 
						translations.tr_meliscms_tool_site_directory_exist, 
						function(){
							$("#btnSiteAdd").trigger("click");
						}
					);
					
				}else{
					melisCoreTool.alertDanger("#siteaddalert", '', data.textMessage);
					melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
				}
			}
			melisCoreTool.done("#btnSiteAdd");
    		melisCore.flashMessenger();
    		
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	
	addEvent(".btnSiteUpdate", function(e){ 
		var dataString = $("#siteEditionForm").serializeArray();
		
		var siteId  = $("#siteEditionForm #id_site_id").val();
		dataString.push({
			name: "site_id",
			value: siteId,
		});
		
		melisCoreTool.pending(".btnSiteUpdate");
		
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/saveSite',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true
		}).done(function(data){
			if(data.success){
				$('#modal-cms-tool-site').modal('hide');
				
				melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
				
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}else{
				melisCoreTool.alertDanger("#siteeditalert", '', data.textMessage);
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
				melisCoreTool.highlightErrors(data.success, data.errors, "siteEditionForm");
			}
			
    		melisCore.flashMessenger();
			melisCoreTool.done(".btnSiteUpdate");
			
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	
	$("body").on("change", "#siteEditionForm #id_sdom_env", function(e){ 
		var siteId = $("#siteEditionForm #id_site_id").val();
		var siteEnv = $("#siteEditionForm #id_sdom_env").val();
		//getSiteInfo(siteId, siteEnv);
		var updateForm = "#siteEditionForm";
		melisCoreTool.resetLabels(updateForm);
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/getSiteByIdAndEnvironment',
	        data        : {siteId : siteId, siteEnv : siteEnv},
	        dataType    : 'json',
	        encode		: true
	    }).done(function(data){
	    	
	    	// set Site Domain default value
			$("#siteEditionForm #id_sdom_domain").val("");
			
    		$.each(data, function(index, value) {
    			// append data to your update form
    			$(updateForm + " input").each(function(index) {
    				var name = $(this).attr('name');

    				$(updateForm + " #" + $(this).attr('id')).val(value[name]);
    				
    				if(value["sdom_scheme"] === null) {
    					$('#siteEditionForm #id_sdom_scheme>option:eq(0)').prop('selected', true);
    				}
    			});
    			
    			if(siteEnv === "selnewsite") {
    				$("#siteEditionForm #id_sdom_env").val("");
    				$("#siteEditionForm #id_sdom_domain").val("");
    			}
    		});
    		
	    })
	});

	$("body").on("click", "#siteMainPageId span", function(){
        melisLinkTree.createInputTreeModal('#id_site_main_page_id');
	});

	$("body").on("click", "#s404PageId span", function(){
        melisLinkTree.createInputTreeModal('#id_s404_page_id');
	});
	
	function newSiteConfirmation(siteId) {
		// initialize of local variable calendar id
		siteVal_id = 'id_meliscms_tool_site_new_site_confirmation_modal';
		siteVal_melisKey = 'meliscms_tool_site_new_site_confirmation_modal';
		modalUrl = '/melis/MelisCore/MelisGenericModal/emptyGenericModal';
		// requesitng to create modal and display after
    	melisHelper.createModal(siteVal_id, siteVal_melisKey, false, {siteId: siteId}, modalUrl);
	}
	
	$("body").on('hidden.bs.modal', '#id_meliscms_tool_site_new_site_confirmation_modal_container', function (e) {
		// Reload site content
		melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
		// Reload the Root "-1" of the Page Tree
		melisCms.refreshTreeview(-1);
	});
		
	function getSiteInfo(siteId, siteEnv) {
		
		var updateForm = "#siteEditionForm";
		melisCoreTool.resetLabels(updateForm);
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/getSiteByIdAndEnvironment',
	        data        : {siteId : siteId, siteEnv : siteEnv},
	        dataType    : 'json',
	        encode		: true
	    }).done(function(data){
	    	
	    	// set Site Domain default value
			$("#siteEditionForm #id_sdom_domain").val("");
			
    		$.each(data, function(index, value) {
    			// append data to your update form
    			$(updateForm + " input, select").each(function(index) {
    				var name = $(this).attr('name');
    				$(updateForm + " #" + $(this).attr('id')).val(value[name]);
    				if(value["sdom_scheme"] === null) {
    					$('#siteEditionForm #id_sdom_scheme>option:eq(0)').prop('selected', true);
    				}
    			});
    			
    			if(siteEnv === "selnewsite") {
    				$("#siteEditionForm #id_sdom_env").val("");
    				$("#siteEditionForm #id_sdom_domain").val("");
    			}
    		});
    		
	    }).fail(function(){
	    	alert( translations.tr_meliscore_error_message );
	    });
	}
	
	function addEvent(target, fn) {
		$("body").on("click", target, fn);
	}
});
$(document).ready(function() {
	//var formAdd  = "#formplatformadd form#idformsite";
	var formEdit = "#formplatformedit form#idformlang";
	
	addEvent("#btn_cms_new_lang",function(){
		melisCoreTool.showOnlyTab('#modal-language-cms', '#id_meliscms_tool_language_modal_content_new');
		melisCoreTool.clearForm("idformlang");
	});
	
	addEvent("#btnLangCmsAdd", function() {
		
		var dataString = $("#idformlang").serialize();
		melisCoreTool.pending("#btnLangCmsAdd");
		melisCoreTool.processing();
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Language/addLanguage',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true,
	     }).success(function(data){
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
	     }).fail(function(){
				alert( translations.tr_meliscore_error_message );
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
	     }).success(function(data){
	    	 	melisCoreTool.pending(".btn");
 	    		$(formEdit + " input[type='text']").each(function(index) {
 	    			var name = $(this).attr('name');
 	    			$("input#" + $(this).attr('id')).val(data.language[name]);
 	    			$("span#platformupdateid").html(data.language['lang_cms_id']);

 	    		});
 	    		melisCoreTool.done(".btn");
	     }).error(function(){
	    	 alert( translations.tr_meliscore_error_message );
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
			translations.tr_meliscore_common_yes, 
			translations.tr_meliscore_common_no, 
			translations.tr_meliscore_tool_language, 
			translations.tr_meliscore_tool_language_delete_confirm, 
			function() {
	    		$.ajax({
	    	        type        : 'POST', 
	    	        url         : '/melis/MelisCms/Language/deleteLanguage',
	    	        data		: {id : getId},
	    	        dataType    : 'json',
	    	        encode		: true,
	    	     }).success(function(data){
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
	    	     }).error(function(){
	    	    	 alert( translations.tr_meliscore_error_message );
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

    $body.on("click", "div[aria-label='Insert/edit link']", checkBtn);
    $body.on("click", "div.mce-menu-item", checkBtn);

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
//      var inputBox = $('.melis-iframe').contents().find('#mce-link-tree').prev().val(dataUrl);
//  	 var inputBox = $('#mce-link-tree').parent().find('input').val(dataUrl);
    	var inputBox = $('.melis-iframe').contents().find('#mce-link-tree').parent().find('input').val(dataUrl);
    	$(".mce-floatpanel.mce-window").find('#mce-link-tree').parent().find('input').val(dataUrl);
  }

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

    function addTreeBtnMoxie() {
        var box = $body.find('.mce-has-open');
        box.append('<div id="mce-link-tree" class="mce-btn mce-open" style="position: absolute; right: 0; width: 32px; height: 28px;"><button><i class="icon icon-sitemap fa fa-sitemap" style="font-family: FontAwesome; position: relative; top: 2px; font-size: 16px;"></i></button></div>');
    }

    function addTreeBtn() {

    }

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
        console.log(title);
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
            encode		: true
        }).success(function(data) {
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
        }).fail(function(error) {
            console.log(error);
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
		        encode		: true
		     }).success(function(data){
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
			
		     }).error(function(){
		    	 console.log('failed');
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
			     }).success(function(data){
			    	 melisCoreTool.done(".btnDelStyle");
			    	if(data.success){				
							melisHelper.melisOkNotification( data.textTitle, data.textMessage );
							melisStyleTool.refreshTable();
					}else{
						melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);				
					}		
					melisCore.flashMessenger();	
			     }).error(function(){
			    	 console.log('failed');
			     });
			}
		);
	}
	
	function refreshTable()
	{
		melisHelper.zoneReload("id_meliscms_tool_styles_content", "meliscms_tool_styles_content");
	}
	
	return {
		
		openToolModal : openToolModal,
		saveStyleDetails : saveStyleDetails,
		deleteStyle : deleteStyle,
		refreshTable : refreshTable
		
	}
})(jQuery, window);;
$(document).ready(function() {
    $body = $("body");
    var modalUrl = "/melis/MelisCms/Sites/renderToolSitesModalContainer";

    $body.on('click', "#btn-new-meliscms-tool-sites", function () {
        melisCoreTool.pending("#btn-new-meliscms-tool-sites");
        melisHelper.createModal('id_meliscms_tool_sites_modal_container','meliscms_tool_sites_modal_add_content',true,[],modalUrl,function () {
            melisCoreTool.done("#btn-new-meliscms-tool-sites");
        });
    });

    window.createSitesModalCallback = function () {
        var   $slideGroup = $('.slide-group');
        var   current = 0;
        var   $slide = $('.slide');
        var   slidesTotal = $slide.length;

        var updateIndex = function(currentSlide) {
            current = currentSlide;

            transition(current);
        };

        var transition = function(slidePosition) {
            var slidePositionNew = (slidePosition ) * 500;
            $slideGroup.animate({
                'left': '-' + slidePositionNew + 'px'
            });
        };

        $("#btn-prev-create-meliscms-tool-sites").on("click", function () {
            var index = current - 1;
            current > 0 ? updateIndex(index) : updateIndex(current);
        });
        $("#btn-next-create-meliscms-tool-sites").on("click", function () {
            var index = current + 1;
            current < (slidesTotal - 1) ? updateIndex(index) : updateIndex(current);
        });

    }
});