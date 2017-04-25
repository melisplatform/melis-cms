$(document).ready(function() {
	
	// Add Event to "Add New Site" button
	addEvent("#id_meliscms_tool_site_header_add", function(e) {
		melisCoreTool.showOnlyTab('#modal-cms-tool-site', '#id_meliscms_tool_site_modal_add');
		melisCoreTool.hideAlert("#siteaddalert");
		melisCoreTool.resetLabels("#formsiteadd #idformsite");
		$("#formsiteadd #idformsite #id_site_id").hide();
		$("#formsiteadd form#idformsite select#id_select_env").hide();
		$('label[for="id_site_id"]').hide();
		$('label[for="id_sdom_env"]').hide();
	});
	
	// Add Event to "Edit Site" button
	addEvent(".btnEditSite", function(e) {
		melisCoreTool.showOnlyTab('#modal-cms-tool-site', '#id_meliscms_tool_site_modal_edit');
		var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
		$(".widget #idformsite").append(tempLoader);
		var tdParent = $(this).parents("td");
		var trParent = tdParent.parents("tr");
		var siteID = trParent.attr("id");
		console.log(siteID);
		var options = $("#formsiteedit #idformsite #id_sdom_env");

		$("#formsiteedit #idformsite #id_site_id").val(siteID);
		melisCoreTool.hideAlert("#siteeditalert");
		melisCoreTool.resetLabels("#formsiteedit #idformsite");
		$('label[for="id_site_id"]').show();
		$(options).show();
		$('label[for="id_select_env"]').show();
		melisCoreTool.clearForm("formsiteedit #idformsite");
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
	    		    $.each(json.data, function(idx, item)
	    		    {
	    		    	options.append($("<option />").val(item).text(item));
	    		    });
	    		    $('#formsiteedit #idformsite #id_sdom_env>option:eq(0)').prop('selected', true);
				    var env = res.data;
				    getSiteInfo(siteID, env);
					var checkValue = setInterval(function() {
						var value = $("#formsiteedit #idformsite #id_site_name").val();
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
			function() {
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
				//var site404PageId = $("#s404pid").html();
				var options = $("#formsiteedit #idformsite #id_sdom_env");
				var env = $(options).val();
				var siteID = $("#formsiteedit #idformsite #id_site_id").val();
				var site404PageId = $("#formsiteedit #idformsite #id_s404_page_id").val();
				
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
		    	    		    $('#formsiteedit #idformsite #id_select_env>option:eq(0)').prop('selected', true);
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
		var formId = $(this).parent();
		formId = formId.attr("id");
		
		var dataString = $("div#" + formId + " form#idformsite").serializeArray();
		
		dataString = $.param(dataString);
		melisCoreTool.pending("#btnSiteAdd");
		melisCoreTool.processing();
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/addSite',
	        data		: dataString,
	        dataType    : 'json',
	        encode		: true
		}).done(function(data) {
			if(data.success){
				$('#modal-cms-tool-site').modal('hide');
				melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
				melisHelper.zoneReload("id_meliscore_leftmenu", "meliscore_leftmenu");
				melisHelper.melisOkNotification(data.textTitle, data.textMessage);
			}else{
				melisCoreTool.alertDanger("#siteaddalert", '', data.textMessage);
				melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
				melisCoreTool.highlightErrors(data.success, data.errors, formId + " form#idformsite");
			}
			melisCoreTool.done("#btnSiteAdd");
    		melisCore.flashMessenger();
    		melisCoreTool.processDone();
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	
	addEvent(".btnSiteUpdate", function(e){ 
		var formId = $(this).parent();
		formId = formId.attr("id");
		
		var siteId  = $("div#" + formId + " form#idformsite #id_site_id").val();
		var siteEnv = $("div#" + formId + " form#idformsite #id_sdom_env").val();
		var isNew = siteEnv === "selnewsite" ? 1 : 0;
		var site404PageId = $("#s404pid").html();

		var dataString = $("div#formsiteedit form#idformsite").serializeArray();
		
		dataString.push({
			name: "siteID",
			value: siteId,
		});
		
		dataString.push({
			name: "sdom_env",
			value: siteEnv
		});
		
		dataString.push({
			name: "oldsite404pageid",
			value: site404PageId
		});

		dataString.splice(3,1);

		dataString = $.param(dataString);
		

		melisCoreTool.pending(".btnSiteUpdate");
		melisCoreTool.processing();
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/updateSite',
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
				melisCoreTool.highlightErrors(data.success, data.errors, formId + " form#idformsite");
			}
			
    		melisCore.flashMessenger();
			melisCoreTool.done(".btnSiteUpdate");
    		melisCoreTool.processDone();
		}).fail(function(){
			alert( translations.tr_meliscore_error_message );
		});
	});
	
	$("body").on("change", "#formsiteedit #idformsite #id_sdom_env", function(e){ 
		var siteId = $("#formsiteedit #idformsite #id_site_id").val();
		var siteEnv = $("#formsiteedit #idformsite #id_sdom_env").val();
		getSiteInfo(siteId, siteEnv);
	});
	
	function getSiteInfo(siteId, siteEnv) {
		//var siteEnvironment = siteEnv; // === "" ? "" : siteEnv;
		
		var updateForm = "#formsiteedit #idformsite ";
		melisCoreTool.resetLabels(updateForm);
		$.ajax({
	        type        : 'POST', 
	        url         : '/melis/MelisCms/Site/getSiteByIdAndEnvironment',
	        data        : {siteId : siteId, siteEnv : siteEnv},
	        dataType    : 'json',
	        encode		: true
	    }).done(function(data){
	    	
	    	// set Site Domain default value
			$("#formsiteedit #idformsite #id_sdom_domain").val("");
			
    		$.each(data, function(index, value) {
    			// append data to your update form
    			$(updateForm + " input, select").each(function(index) {
    				var name = $(this).attr('name');
    				$(updateForm + " #" + $(this).attr('id')).val(value[name]);
    				$("#s404pid").html(value["s404_page_id"]);
    				if(value["sdom_scheme"] === null) {
    					$('#formsiteedit #idformsite #id_sdom_scheme>option:eq(0)').prop('selected', true);
    				}
    			});
    			//$("#formsiteedit #idformsite #id_select_env").val(siteEnvironment);
    			if(siteEnv === "selnewsite") {
    				$("#formsiteedit #idformsite #id_sdom_env").val("");
    				$("#formsiteedit #idformsite #id_sdom_domain").val("");
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