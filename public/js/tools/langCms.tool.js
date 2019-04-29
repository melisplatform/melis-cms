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