$(function(){
	var $body = $("body");
	
		$body.on("click", ".btnCmsPlatfomEdit", function() {
			var pId = $(this).parents("tr").attr("id");

				// initialation of local variable
				modalId = 'platform_tool_modal';
				platform_modal_content = 'meliscms_tool_platform_ids_modal_content';
				modalUrl = '/melis/MelisCms/Platform/renderPlatformModal';

				// requesitng to create modal and display after
		    	melisHelper.createModal(modalId, platform_modal_content, false, {id:pId}, modalUrl);
		});
		
		$body.on("click", ".btnSavePlatfomrRange", function() {
			var pId 		= $(this).data("id"),
				dataString 	= $('#idformplatform').serializeArray();
			
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
	    		if ( data.success ) {
					$('#platform_tool_modal_container').modal('hide');
					melisHelper.zoneReload("id_meliscms_tool_platform_ids", "meliscms_tool_platform_ids");
					melisHelper.melisOkNotification(data.textTitle, data.textMessage);
				} else {
					melisCoreTool.alertDanger("#cmsPlatformAlert", '', data.textMessage);
					melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
				}
	    		melisCore.flashMessenger();
	    		melisCoreTool.highlightErrors(data.success, data.errors, "idformplatform");
	    	}).fail(function(xhr, textStatus, errorThrown) {
	    		alert("ERROR !! Status = "+ textStatus + "\n Error = "+ errorThrown + "\n xhr = "+ xhr.statusText);
	    	});
		});
		
		$body.on("click","#id_meliscms_tool_platform_ids_add_button", function() {
			// initialation of local variable
			modalId = 'platform_tool_modal';
			platform_modal_content = 'meliscms_tool_platform_ids_modal_content';
			modalUrl = '/melis/MelisCms/Platform/renderPlatformModal';

			// requesitng to create modal and display after
	    	melisHelper.createModal(modalId, platform_modal_content, false, null, modalUrl);
		});
		
		$body.on("click", ".btnCmsPlatformIdsDelete", function() {
			var pid_id 		= $(this).parents("tr").attr("id"),
				dataString 	= new Array;
			
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