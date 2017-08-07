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