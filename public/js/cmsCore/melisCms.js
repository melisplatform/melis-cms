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
							
							console.log(idPage);
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

    function disableCmsButtons()
    {
        $("div.make-switch label on, off").parent().css("z-index", -1).parents("div.make-switch").css("opacity", 0.5);
        $("div[data-melisKey='meliscms_page_actions']").find("a").attr("disabled", "disabled");
    }

    function enableCmsbuttons()
    {
        $("div[data-melisKey='meliscms_page_actions']").find("a").removeAttr("disabled");
        $("div.make-switch label on, off").parent().css("z-index", 1).parents("div.make-switch").css("opacity", 1);
    }
	
	// RELOAD THE TREEVIEW AND SET A NODE PAGE ACTIVE
	function refreshTreeview(pageNumber){
	  	$.ajax({
  	        url         : '/melis/MelisCms/TreeSites/getPageIdBreadcrumb?idPage='+pageNumber,
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
		var limit = event.data.limit;
		
		if( prevLabel.find('span').length ){
			
			if(charLength === 0){
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
		else{
			if(charLength !== 0){
				prevLabel.append("<span class='text-counter-indicator'><i class='fa fa-text-width'></i>(" + charLength + ")</span>");
				
				if( charLength > limit ){
					prevLabel.addClass('limit');
					prevLabel.find('span').addClass('limit');
				}
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
    $body.on("keyup keydown change", "form[name='pageseo'] input[name='pseo_meta_title']", { limit: 60}, charCounter);
    
    // char counter in seo description
    $body.on("keyup keydown change", "form[name='pageseo'] textarea[name='pseo_meta_description']", { limit: 160}, charCounter);
    
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
		enableCmsButtons								: 			enableCmsbuttons
	};

})();