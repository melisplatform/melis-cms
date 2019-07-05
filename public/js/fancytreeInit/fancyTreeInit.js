(function($, window, document) {

	// On Load
	$(window).on('load', function() {
			window.mainTree = function(completeEvent) {
					var melisExtensions;
					var $tabArrowTop = $("#tab-arrow-top");

					if (melisCore.screenSize <= 767) {
							melisExtensions = ['contextMenu', 'filter', 'glyph'];
					} else {
							melisExtensions = ['contextMenu', 'dnd', 'filter', 'glyph'];
					}

					$('#id-mod-menu-dynatree').fancytree({
							extensions: melisExtensions,
							glyph: {
								map: {
									loading: "fa fa-spinner fa-pulse"
									//loading: "glyphicon-refresh fancytree-helper-spin" // edited by junry
								}
							},
							activeVisible: false,
							debugLevel: 0,
							autoScroll: true,
							generateIds: true,
							idPrefix: "mt_",
							tabindex: "",
							toggleEffect: {
									height: "toggle",
									duration: 250
							},
							source: {
									url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id',
									cache: true
							},
							contextMenu: {
									menu: {
											'new': {
													'name': translations.tr_meliscms_menu_new,
													'icon': 'paste'
											},
											'edit': {
													'name': translations.tr_meliscms_menu_edit,
													'icon': 'edit'
											},
											'delete': {
													'name': translations.tr_meliscms_menu_delete,
													'icon': 'delete'
											},
											'dupe': {
													'name': translations.tr_meliscms_menu_dupe,
													'icon': 'copy'
											},
											'export': {
													'name': 'Export pages',
													'icon': 'copy'
											},
										    'import': {
													'name': translations.tr_melis_cms_page_tree_import,
													'icon': 'copy'
											}
									},
									actions: function(node, action, options) {
											if (action === 'new') {
													var data = node.data;

													//close page creation tab and open new one (in case if its already open - updated parent ID)
													melisHelper.tabClose('0_id_meliscms_page');
													melisHelper.tabOpen(translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation', {
															idPage: 0,
															idFatherPage: data.melisData.page_id
													});
											}
											if (action === 'edit') {
													var data = node.data;
													melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
															idPage: data.melisData.page_id
													});
											}
											if (action === 'delete') {

													var data = node.data;
													var zoneId = data.melisData.item_zoneid;
													var idPage = data.melisData.page_id;
													var parentNode = (node.getParent().key == 'root_1') ? -1 : node.getParent().key;
													// var parentNode = ( node.key == 'root_1') ? -1 : node.getParent().key;	

													// check if page to be delete is open or not
													var openedOrNot = $(".tabsbar a[data-id='" + zoneId + "']").parent("li");

													// delete page confirmation 
													melisCoreTool.confirm(
															translations.tr_meliscms_menu_delete,
															translations.tr_meliscms_menu_cancel,
															translations.tr_meliscms_delete_confirmation,
															translations.tr_meliscms_delete_confirmation_msg,
															function() {
																	// reload and expand the treeview
																	melisCms.refreshTreeview(parentNode, 1);

																	// check if node has children if TRUE then cannot be deleted
																	$.ajax({
																			url: '/melis/MelisCms/Page/deletePage?idPage=' + idPage,
																			encode: true
																	}).success(function(data) {
																			if (data.success === 1) {
																					//close the page if its open. do nothing if its not open
																					if (openedOrNot.length === 1) {
																							melisHelper.tabClose(zoneId);
																					}

																					// notify deleted page
																					melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46');

																					// update flash messenger values
																					melisCore.flashMessenger();

																			} else {
																					melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, '#000');
																			}
																	}).error(function(xhr, textStatus, errorThrown) {
																			alert(translations.tr_meliscore_error_message);
																	});
															});
											}
											if (action === 'dupe') {
													var data = node.data;
													// melisHelper.tabOpen( data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey,  { sourcePageId: data.melisData.page_id } ); 

													// initialation of local variable
													zoneId = 'id_meliscms_tools_tree_modal_form_handler';
													melisKey = 'meliscms_tools_tree_modal_form_handler';
													modalUrl = 'melis/MelisCms/TreeSites/renderTreeSitesModalContainer';
													// requesitng to create modal and display after
													melisHelper.createModal(zoneId, melisKey, false, {
															'sourcePageId': data.melisData.page_id
													}, modalUrl, function() {});
											}
											if(action === 'export' || action === 'import'){
												var modalUrl = "/melis/MelisCms/Page/renderPageExportImportModalHandler";
												var data = node.data;
												if(action === 'export') {
													melisHelper.createModal('id_meliscms_page_export_modal', 'meliscms_page_export_modal', true, {'pageId':data.melisData.page_id}, modalUrl, function () {});
												}else{
													melisHelper.createModal('id_meliscms_page_import_modal','meliscms_page_import_modal',true,[],modalUrl,function () {

													});
												}
											}
									}
							},
							lazyLoad: function(event, data) {
									// get the page ID and pass it to lazyload
									var pageId = data.node.data.melisData.page_id;
									data.result = {
											url: '/melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId=' + pageId,
											data: {
													mode: 'children',
													parent: data.node.key
											},
											cache: false,
									}

							},
							create: function(event, data) {
									melisHelper.loadingZone($('#treeview-container'));
							},
							init: function(event, data, flag) {
									melisHelper.removeLoadingZone($('#treeview-container'));
									// focus search box
									$("input[name=left_tree_search]").focus();

									var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

									if (tree.count() === 0) {

											$(".meliscms-search-box.sidebar-treeview-search").hide();
											// Checking if the user has a Page rights to access
											// -1 is the value for creating new page right
											$.get('/melis/MelisCms/TreeSites/checkUserPageTreeAccress', {
													idPage: -1
											}, function(res) {
													if (res.isAccessible) {
															$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>" + translations.tr_meliscms_create_page + "</span></div>");
													}
											});

									} else {
											$(".meliscms-search-box.sidebar-treeview-search").show();
											$("#id-mod-menu-dynatree .create-newpage").remove();
									}
							},
							click: function(event, data) {
									targetType = data.targetType;
									if (targetType === "title") {
											data.node.setExpanded();

											// open page on click on mobile . desktop is double click
											if (melisCore.screenSize <= 1024) {
													var data = data.node.data;
													melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
															idPage: data.melisData.page_id
													}, null, melisCms.pageTabOpenCallback(data.melisData.page_id));
											}
									}
									$('.hasNiceScroll').getNiceScroll().resize();

									if ( $tabArrowTop.length ) {
											$tabArrowTop.removeClass("hide-arrow");
									}
							},
							dblclick: function(event, data) {
									// get eventType to know what was clicked the 'expander (+-)' or the title
									//targetType = data.targetType;

									// open tab and page
									var data = data.node.data;
									melisHelper.tabOpen(data.melisData.page_title, data.iconTab, data.melisData.item_zoneid, data.melisData.item_melisKey, {
											idPage: data.melisData.page_id
									}, null, melisCms.pageTabOpenCallback(data.melisData.page_id));

									$('.hasNiceScroll').getNiceScroll().resize();

									return false;
							},
							loadChildren: function(event, data) {
									//RUNS ONLY ONCE

									// if there is no/empty pages in the treeview
									var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

									// // PAGE ACCESS user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/TreeSites/canEditPages',
									//     encode		: true
									// }).success(function(data){
									// 	// has no access
									// 	if(data.edit === 0){
									// 	 	$(".meliscms-search-box.sidebar-treeview-search").hide();
									// 		$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='no-access'>" + translations.tr_meliscms_no_access + "</span></div>");
									// 	}
									// 	// has access
									// 	else{
									// 		 if(tree.count() === 0){
									// 		$(".meliscms-search-box.sidebar-treeview-search").hide();
									// 		$("#id-mod-menu-dynatree").prepend("<div class='create-newpage'><span class='btn btn-success'>"+ translations.tr_meliscms_create_page +"</span></div>");
									// 		 }
									// 		 else{
									// 		$(".meliscms-search-box.sidebar-treeview-search").show();
									// 			$("#id-mod-menu-dynatree .create-newpage").remove();
									// 		 }
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });


									// // SAVE user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/Page/isActionActive?actionwanted=save',
									//     encode		: true
									// }).success(function(data){
									// 	if(data.active === 0){
									// 		$("body").addClass('disable-create');
									// 	}
									// 	else{
									// 		$("body").removeClass('disable-create');
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });
									//
									// // DELETE user rights checking
									// $.ajax({
									//     url         : '/melis/MelisCms/Page/isActionActive?actionwanted=delete',
									//     encode		: true
									// }).success(function(data){
									// 	if(data.active === 0){
									// 		$("body").addClass('disable-delete');
									// 	}
									// 	else{
									// 		$("body").removeClass('disable-delete');
									// 	}
									// }).error(function(xhr, textStatus, errorThrown){
									// 	alert( translations.tr_meliscore_error_message );
									// });
							},
							renderNode: function(event, data) {
									// removed .fancytree-icon class and replace it with font-awesome icons
									$(data.node.span).find('.fancytree-icon').addClass(data.node.data.iconTab).removeClass('fancytree-icon');

									if (data.node.statusNodeType !== 'loading') {

											if (data.node.data.melisData.page_is_online === 0) {
													$(data.node.span).find('.fancytree-title, .fa').css("color", "#686868");
											}

											if (data.node.data.melisData.page_has_saved_version === 1) {
													//check if it has already 'unpublish' circle - avoid duplicate circle bug
													if ($(data.node.span).children("span").hasClass("unpublish") == false) {
															$(data.node.span).find('.fancytree-title').before("<span class='unpublish'></span>");
													}
											}
									}
							},
							dnd: {
									autoExpandMS: 400,
									smartRevert: true,
									refreshPositions: true,
									draggable: {
											zIndex: 1000,
											scroll: false,
											appendTo: "body",
									},
									dragStart: function(node, data) {
											// disable drag & drop if its on mobile
											if (melisCore.screenSize >= 1024) {
													// determine if the node is draggable or not
													if (!data.node.data.dragdrop) {
															return false;
													} else {
															return true;
													}
											} else {
													return false;
											}
									},
									dragEnter: function(node, data) {
											return true;
									},
									dragOver: function(node, data) {

									},
									dragLeave: function(node, data) {

									},
									dragStop: function(node, data) {

									},
									dragDrop: function(node, data) {
											node.setExpanded(true).always(function() {
													// This function MUST be defined to enable dropping of items on the tree.
													// data.hitMode is 'before', 'after', or 'over'.
													// We could for example move the source to the new target:

													// catch if its 'root_*' parent
													var isRootOldParentId = data.otherNode.getParent().key.toString();
													var oldParentId = (isRootOldParentId.includes('root')) ? -1 : data.otherNode.getParent().key;

													// move the node to drag parent ------------------------------------------------

													data.otherNode.moveTo(node, data.hitMode);

													var tree = $("#id-mod-menu-dynatree").fancytree("getTree");

													var draggedPage = data.otherNode.key

													// catch if its 'root_*' parent
													var isRootNewParentId = node.getParent().key.toString();
													var newParentId = (isRootNewParentId.includes('root')) ? -1 : node.getParent().key;

													if (data.hitMode == 'over') {
															newParentId = data.node.key;
													}

													var newIndexPosition = data.otherNode.getIndex() + 1;

													//send data to apply new position of the dragged node
													var datastring = {
															idPage: draggedPage,
															oldFatherIdPage: oldParentId,
															newFatherIdPage: newParentId,
															newPositionIdPage: newIndexPosition
													};

													$.ajax({
															url: '/melis/MelisCms/Page/movePage',
															data: datastring,
															encode: true
													}).success(function(data) {}).error(function(xhr, textStatus, errorThrown) {
															alert(translations.tr_meliscore_error_message);
													});
											});
											// end
									}
							},
					});
			}

			// initialize the tree
			mainTree();
	});

	// create page if treeview page is empty
	$("body").on("click", "#id-mod-menu-dynatree .create-newpage .btn", function() {
			melisHelper.tabOpen(translations.tr_meliscms_page_creation, 'fa-file-text-o', '0_id_meliscms_page', 'meliscms_page_creation', {
					idPage: 0,
					idFatherPage: '-1'
			});
	});

	$("body").on("click", '#sourcePageIdFindPageTree span', function() {
			melisLinkTree.createInputTreeModal('#sourcePageId');
	});

	$("body").on("click", '#destinationPageIdFindPageTree span', function() {
			melisLinkTree.createInputTreeModal('#destinationPageId');
	});

	$("body").on("click", 'button[data-inputid="#destinationPageId"]', function() {
			$('[name="use_root"]').each(function() {
					if ($(this).is(':checked')) {
							$(this).prop("checked", false);
					}
			})
			$('.remember-me-cont .cbmask-inner').removeClass('cb-active');
			$("#destinationPageId").prop('disabled', false);
	});

	$("body").on('change', '[name="use_root"]', function() {

			if ($('[name="use_root"]:checked').length) {

					$("#destinationPageId").val("");
					$("#destinationPageId").prop('disabled', true);
			} else {

					$("#destinationPageId").prop('disabled', false);
			}
	})

	// use this callback to re-initialize the tree when its zoneReloaded
	window.treeCallBack = function() {
			if ($("#id-mod-menu-dynatree").children().length == 0) {
					mainTree();
			}
	}

	$("body").on("click", "#duplicatePageTree", function() {
			var dataString = $('#duplicatePageTreeForm').serializeArray();
			var parentNode = $('#duplicatePageTreeForm input[name="destinationPageId"]').val();
			melisCoreTool.pending("#duplicatePageTree");
			$("#duplicatePageTree").find('i').removeClass();
			$("#duplicatePageTree").find('i').addClass('fa fa-spinner fa-spin');
			$.ajax({
					type: 'POST',
					url: '/melis/MelisCms/TreeSites/duplicateTreePage',
					data: dataString,
					dataType: 'json',
					encode: true
			}).done(function(data) {

					if (data.success) {
							$('#id_meliscms_tools_tree_modal_form_handler_container').modal('hide');
							melisCms.refreshTreeview(parentNode, 1);
							// clear Add Form
							melisHelper.melisOkNotification(data.textTitle, data.textMessage);
					} else {
							melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
							melisCoreTool.highlightErrors(data.success, data.errors, "stylesForm");
					}
					melisCore.flashMessenger();
					melisCoreTool.done("#duplicatePageTree");
					$("#duplicatePageTree").find('i').removeClass();
					$("#duplicatePageTree").find('i').addClass('fa fa-save');
			}).fail(function() {
					alert(translations.tr_meliscore_error_message);
			});
	})

})(jQuery, window);