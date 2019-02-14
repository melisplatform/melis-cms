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