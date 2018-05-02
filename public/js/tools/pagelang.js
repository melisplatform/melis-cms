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