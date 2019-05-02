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

    // Add Event to "Minify Button"
    addEvent(".btnMinifyAssets", function(e) {
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