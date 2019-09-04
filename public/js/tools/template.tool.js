/**
 *  Here, you will be using your javacript codes to interact with
 *  the server specially on your tool actions from your tool controller
 */
$(document).ready(function() {
	var $body = $("body");

		// for edit button
		$body.on("click", '.btnEditTemplates', function() {
			var id = $(this).parents("tr").attr("id");

				melisCoreTool.hideAlert("#templateupdateformalert");
				melisCoreTool.showOnlyTab('#modal-template-manager-actions', '#id_modal_tool_template_edit');
				toolTemplate.retrieveTemplateData(id);
		});
		
		$body.on("click", '.btnDelTemplate', function() {
			var id = $(this).parents("tr").attr("id");

				toolTemplate.deleteTemplate(id);
		});
		
		$body.on("click", "#id_meliscms_tool_templates_header_add", function() {
			melisCoreTool.hideAlert("#templateaddformalert");
			melisCoreTool.resetLabels("#id_modal_tool_template_add #id_tool_template_generic_form");
		});
		
		$body.on("click", "#btnTemplateUpdate", function() {
			toolTemplate.updateTemplate();
		});

		$body.on("click", ".btnMelisTemplatesExport", function() {
			var searched 	= $("input[type='search'][aria-controls='tableToolTemplateManager']").val(),
				siteId 		= $("#templatesSiteSelect").val();

			if ( !melisCoreTool.isTableEmpty("tableToolTemplateManager") ) {
				melisCoreTool.exportData('/melis/MelisCms/ToolTemplate/exportToCsv?filter='+searched+'&site='+siteId);
			}
		});
		
		$body.on("change", "#templatesSiteSelect", function() {
			var tableId = $(this).parents().eq(6).find('table').attr('id');
				$("#"+tableId).DataTable().ajax.reload();
		});
});

/** THESE ARE THE MAIN FUNCTIONS THAT WILL BE USED IN YOUR PLUGIN TOOL **/
var toolTemplate = { 
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
		    	if ( data.success ) {
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
	}
};

window.initTemplateList = function(data, tblSettings) {
	var $tempSiteSelect = $('#templatesSiteSelect');
		if ( $tempSiteSelect.length ) {
			data.tpl_site_id = $tempSiteSelect.val();
		}	
}

window.paginateDataTables = function() {
	// paginate dataTables data
	melisCore.paginateDataTables();
}