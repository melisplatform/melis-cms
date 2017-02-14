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
		
		var oldUrl = $("#tableToolSite301 tr#"+s301Id+" td:nth-child(2)").text();
		if(oldUrl !== ''){
			window.open(oldUrl,"_blank");
		}else{
			alert( translations.tr_meliscore_error_message );
		}
	});
});

// Site Redirect mdoal form
window.createSite301Modal = function(s301Id = 0){
	zoneId = 'id_meliscms_tool_site_301_generic_form';
	melisKey = 'meliscms_tool_site_301_generic_form';
	modalUrl = '/melis/MelisCms/SiteRedirect/renderToolSiteRedirectModal';
	melisHelper.createModal(zoneId, melisKey, false, {s301Id: s301Id}, modalUrl);
}