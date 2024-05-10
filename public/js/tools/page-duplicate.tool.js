$(function() {
	var $body = $("body");

	$body.on("click", "a.melis-pageduplicate", function() {
		var pagenumber = $(this).data().pagenumber;
			
			$.ajax({
				type: "POST",
				url: "/melis/MelisCms/PageDuplication/duplicate-page",
				data: { id: pagenumber },
				dataType: "json",
				encode: true,
			})
			.done(function(data) {
				if (data.success) {
					//console.log(`page-duplicate.tool.js data.response.pageId: `, data.response.pageId);
					melisCms.refreshTreeview(data.response.pageId);
					if (data.response.openPageAfterDuplicate) {
						// open page
						var pageID = data.response.pageId + " - " + data.response.name;

						melisHelper.tabOpen(
							pageID,
							data.response.icon,
							data.response.pageId + "_id_meliscms_page",
							"meliscms_page",
							{ idPage: data.response.pageId },
							null,
							() => {
								// show page loader
								loader.addActivePageEditionLoading(
									data.response.pageId + "_id_meliscms_page"
								);
							}
						);
					}
					melisHelper.melisOkNotification(data.textTitle, data.textMessage);
				} else {
					melisHelper.melisKoNotification(
						data.textTitle,
						data.textMessage,
						data.errors,
						0
					);
				}
				melisCore.flashMessenger();
			})
			.fail(function(xhr, textStatus, errorThrown) {
				alert(translations.tr_meliscore_error_message);
			});
	});
});
