$(function () {
	let $body = $("body"),
        indicatorHoverTimeout;

        $body
            .on("mouseenter", ".melis-dragdropzone-container > .dnd-layout-wrapper", function() {
                $(this).find(".dnd-layout-indicator").css("opacity", 1);
                $(this).find(".dnd-layout-indicator").css("pointer-events", "auto");
            })
            .on("mouseleave", ".melis-dragdropzone-container > .dnd-layout-wrapper", function() {
                $(this).find(".dnd-layout-indicator").css("opacity", 0);
                $(this).find(".dnd-layout-indicator").css("pointer-events", "none");
                
                //mouseLeaveDndLayoutButtons( $(this).find(".dnd-layout-buttons") );
            });

        $body
            .on("mouseenter", ".melis-dragdropzone-container > .dnd-layout-wrapper > .dnd-layout-indicator", function() {
                clearTimeout(indicatorHoverTimeout);

                let $thisEnter              = $(this),
                    $dndLayoutWrapperEnter  = $thisEnter.closest(".dnd-layout-wrapper"),
                    $zoneEnter              = $dndLayoutWrapperEnter.find(".melis-dragdropzone"),
                    $uiOutlinedFirstEnter   = $zoneEnter.find(".melis-ui-outlined").first(),
                    $toolBoxEnter           = $uiOutlinedFirstEnter.find(".melis-plugin-tools-box");

                    mouseEnterDndLayoutButtons($thisEnter.next(".dnd-layout-buttons"));

                    $thisEnter.addClass("hovering");
            })
            .on("mouseleave", ".melis-dragdropzone-container > .dnd-layout-wrapper > .dnd-layout-indicator", function() {
                indicatorHoverTimeout = setTimeout(() => {
                    let $thisLeave              = $(this),
                        $dndLayoutWrapperLeave  = $thisLeave.closest(".dnd-layout-wrapper"),
                        $zoneLeave              = $dndLayoutWrapperLeave.find(".melis-dragdropzone"),
                        $uiOutlinedFirstLeave   = $zoneLeave.find(".melis-ui-outlined").first(),
                        $toolBoxLeave           = $uiOutlinedFirstLeave.find(".melis-plugin-tools-box");

                        mouseLeaveDndLayoutButtons($thisLeave.next(".dnd-layout-buttons"));

                        $thisLeave.removeClass("hovering");
                }, 100); // delay for a probably prevents instant flicker
            });

        $body
            .on("mouseenter", ".melis-dragdropzone-container > .dnd-layout-wrapper > .dnd-layout-buttons", function() {
                mouseEnterDndLayoutButtons($(this));
            })
            .on("mouseleave", ".melis-dragdropzone-container > .dnd-layout-wrapper > .dnd-layout-buttons", function() {
                mouseLeaveDndLayoutButtons($(this));
            });

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function () {
            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            // var tempLoader =
                // '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';

            let dndContainer = $(this).closest(".melis-dragdropzone-container");

            let pluginReferer = dndContainer.data("pluginReferer");

            let dndLayout = dndContainer.find(".melis-dragdropzone");
            let dndCtr = dndLayout.length;

			let tempLoader = dndContainer.find("#loader");
            $(this).closest(".melis-dragdropzone-container").prepend(tempLoader.removeClass("hidden"));

            $.get("/dnd-layout", {
                pageId,
                dndId,
                dndTpl,
                melisSite,
            })
            .done((res) => {
                if (res.success) {
                    dndContainer.find("#loader").remove();

                    let newLayout = $(res.html);
                    let newLayoutDnd = newLayout.find(".melis-dragdropzone");
                    let newLayoutDndCtr = newLayoutDnd.length;

                    newLayout.data("pluginReferer", pluginReferer);

                    // if (dndCtr != newLayoutDndCtr) {
                    if (dndCtr == 1) {
                        let dndContent = dndContainer.find(".melis-dragdropzone");

                        $.each(dndContent.children(), (i, v) => {
                            // filter dnd with contents
                            if ($(v).text().trim() !== "") {
                                // move and append to new dnd layout
                                $(v).appendTo(newLayout.find(".melis-dragdropzone:first"));
                            }
                        });

                        // replacing new layout
                        $(
                            "body .melis-dragdropzone-container[data-plugin-id='" +
                                dndId +
                                "']"
                        ).replaceWith(newLayout);
                    } else {
                        // comparing dnds to the new dnd layout
                        $.each(dndLayout, (i, v) => {
                            // checking if the plugin id not exist in the new layout
                            let pluginExist = newLayout.find(
                                "div.melis-dragdropzone[data-plugin-id='" +
                                    $(v).data("pluginId") +
                                    "']"
                            );
                            // all plugin no exist in the new layout will append to the last dnd in the new layout
                            if (!pluginExist.length) {
                                let dndContents = $(v).children();

                                $.each(dndContents, (di, dv) => {
                                    // filter dnd with contents
                                    if ($(dv).text().trim() !== "") {
                                        // move and append to new dnd layout
                                        $(dv).appendTo(newLayout.find(".melis-dragdropzone:last"));
                                    }
                                });
                            }
                        });

                        // replacing new layout
                        $(
                            "body .melis-dragdropzone-container[data-plugin-id='" +
                                dndId +
                                "']"
                        ).replaceWith(newLayout);
                    }
                    // }

                    melisPluginEdition.moveResponsiveClass();
                    melisPluginEdition.pluginDetector();
                    melisPluginEdition.initResizable();
                    melisDragnDrop.setDragDropZone();

                    // added to update iframe height
                    melisPluginEdition.calcFrameHeight();

                    // save change to session
                    melisPluginEdition.sendDragnDropList(dndId, pageId);

                    // re-position .dnd-layout-buttons after remove dnd
                    topPositionLayoutButtons();

                    // reinitialize tinymce
                    waitForTagHtmlInit();
                    waitForTagTextareaInit();
                    waitForTagMediaInit();
                }
            })
            .always(() => {
                dndContainer.find("#loader").remove();
            });
        });

		// add new d&d
		$body.on("click", ".dnd-plus-button", function () {

			let btn = $(this);
			let pageId = $(this).data("pageId");
			let dndId = $(this).data("dndId");

			let melisSite = $(this).data("melisSite");
			let dndTpl = "default";
			let pluginContent = btn.closest(".dnd-plugins-content");

			btn.attr("disabled", true);

            let dndContainer = $(this).closest(".melis-dragdropzone-container");
			let pluginReferer = dndContainer.data("pluginReferer");

			if (pluginReferer)
				dndId = pluginReferer;

			// let tempLoader = dndContainer.find("#dnd-loader");
            // $(this).closest(".melis-dragdropzone-container").prepend(tempLoader.removeClass("hidden"));

			$.get("/dnd-layout", {
				pageId,
				dndId,
				dndTpl,
				melisSite,
				addAction: true
			}).done((res) => {

				btn.attr("disabled", false);

				if (res.success) {

					let newDnd = $(res.html);
					// newDnd.appendTo(pluginContent);

					// prepare animate 
					newDnd.css("opacity", ".25");

					// add to next position
					let originDnd = dndContainer.closest(".row").after(newDnd);

					// animate show
					originDnd.next().animate({
						opacity: 1,
					}, 1500);

					// $('html, body').animate({
					//     scrollTop: newDnd.offset().top
					// }, 2000);

					// TODO 
					let boHeader = 47;
					let pageEditionHeader = 72;
					let newDndTop = newDnd.offset().top;
					let xtra = 100;

					$(parent.document).scrollTop(newDndTop + xtra);

					let newDndId = newDnd.find(".melis-dragdropzone-container").data("pluginId");

					// update dnd orders
					updateDndOrder(pageId);

					melisPluginEdition.moveResponsiveClass();
					melisPluginEdition.pluginDetector();
					melisPluginEdition.initResizable();
					melisDragnDrop.setDragDropZone();

                    // added to update iframe height
                    melisPluginEdition.calcFrameHeight();

					// save change to session
					melisPluginEdition.sendDragnDropList(newDndId, pageId);

				} else {
					// dndContainer.find("#loader").remove();
				}

                // re-position .dnd-layout-buttons after remove dnd
                topPositionLayoutButtons();

                // check display of arrow buttons after plus
                handleDisplayRemoveArrowButtons();

			})
			.always(() => {
				// dndContainer.find("#loader").remove();
			});
		});

		// remove d&d
		$body.on("click", ".dnd-remove-button", function () {
			let btn = $(this);
			let pageId = $(this).data("pageId");
			let dndId = $(this).data("dndId");

			// let dndContainer = btn.closest(".melis-dragdropzone-container");

			let dndContainer = btn.parents(".row:first");

			// check number of dnds, should should remain atleast 1 dnd active 
			let dnds = dndContainer.parents(".dnd-plugins-content").find("> div > div > div.melis-dragdropzone-container");

			if (dnds.length > 1) {

				btn.attr("disabled", true);

				$.post("/dnd-remove", {
					pageId,
					dndId,
				}).done((res) => {
					// modal confirmation
					window.parent.melisCoreTool.confirm(
						translations.tr_meliscms_common_yes,
						translations.tr_meliscms_common_no,
						translations.tr_meliscms_drag_and_drop_delete_modal_title, // title
						translations.tr_meliscms_drag_and_drop_delete_modal_content, // message
						function() {
							dndContainer.remove();
	
							// update dnd orders
							updateDndOrder(pageId)
							
							// added to update iframe height
							melisPluginEdition.calcFrameHeight();
							
							// re-position .dnd-layout-buttons after remove dnd
							topPositionLayoutButtons();

                            // check display of arrow buttons after remove
                            handleDisplayRemoveArrowButtons();
						});
				}).always(() => {
					btn.attr("disabled", false);
				});
			}
		});

		$body.on("click", ".dnd-arrow-up", function() {
			let btn = $(this);
			let pageId = btn.data("pageId");
			let currentContent = btn.parents(".row:first");

			if (currentContent.prev().length) {
				currentContent.prev().before(currentContent);
	
				btn.attr("disabled", true);
	
				updateDndOrder(pageId, function() {
					btn.attr("disabled", false);
				});

                // check display of arrow buttons after arrow up
                handleDisplayRemoveArrowButtons();

                // for .dnd-layout-buttons on the sub tools buttons
                adjustLayoutButtonMargins();
			}
		});

		$body.on("click", ".dnd-arrow-down", function() {

			let btn = $(this);
			let pageId = btn.data("pageId");
			let currentContent = btn.parents(".row:first");

			if (currentContent.next().length) {
				currentContent.next().after(currentContent);
	
				btn.attr("disabled", true);
	
				updateDndOrder(pageId, function() {
					//console.log(btn);
					btn.attr("disabled", false);
				});

                // check display of arrow buttons after arrow down
                handleDisplayRemoveArrowButtons();

                // for .dnd-layout-buttons on the sub tools buttons
                adjustLayoutButtonMargins();
			}
		});

        window.parent.$body.on("click", ".tab-element", function() {
            let $tabElement = $(this),
                melisKey    = $tabElement.closest(".nav-item").data("tool-meliskey");

                if (melisKey === "meliscms_page") {
                    // call to this function as it re-initialized top position of .dnd-layout-buttons
                    topPositionLayoutButtons();
                }
        });

        function waitForTagTextareaInit(retries = 10) {
            if (typeof melistagTEXTAREA_init === "function") {
                melistagTEXTAREA_init();
            } else if (retries > 0) {
                setTimeout(() => waitForTagTextareaInit(retries - 1), 100);
            }
        }

        function waitForTagHtmlInit(retries = 10) {
            if (typeof melistagHTML_init === "function") {
                melistagHTML_init();
            } else if (retries > 0) {
                setTimeout(() => waitForTagHtmlInit(retries - 1), 100);
            }
        }

        function waitForTagMediaInit(retries = 10) {
            if (typeof melistagMEDIA_init === "function") {
                melistagMEDIA_init();
            } else if (retries > 0) {
                setTimeout(() => waitForTagMediaInit(retries - 1), 100);
            }
        }

        function mouseEnterDndLayoutButtons($element) {
            const $el = $element;
                //console.log("mouseenter: cancel fade");

                const timeoutId = $el.data("fadeTimeout");
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    $el.removeData("fadeTimeout");
                }

                $el.css("opacity", 1);
                $el.css("pointer-events", "auto");
                // $el.show();

                // connected to dynamic-dragndrop.css .melis-dragdropzone-container > .dnd-layout-wrapper
                $el.closest(".dnd-layout-wrapper").css("z-index", "unset"); // so that .dnd-layout-buttons are clickable
        }

        function mouseLeaveDndLayoutButtons($element) {
            const $el = $element;
                //console.log("mouseleave: starting 3s delay");

                const timeoutId = setTimeout(() => {
                    //console.log("3s passed, fading out");
                    $el.css("opacity", 0);
                    $el.css("pointer-events", "none");

                    // connected to dynamic-dragndrop.css .melis-dragdropzone-container > .dnd-layout-wrapper
                    $el.closest(".dnd-layout-wrapper").removeAttr("style");

                    //$el.hide();
                    $el.removeData("fadeTimeout");
                }, 3000);

                $el.data("fadeTimeout", timeoutId);
        }

        /**
         *
         * @param id
         * @param row
         * @param pageId
         * @param parent
         * @param sourceId
         */
        function requestDND(id, row, pageId, parent, sourceId) {
            $.ajax({
                'url' : '/dnd-layout',
                'data' : {id : id},
                'type' : 'GET'
            }).done(function(data){
                var newCol = $('<div class="col-md-12"></div>').append($(data['html']));
                row.append(newCol);

                let root = $('[data-dragdropzone-id="'+sourceId+'"]').first();
                updateNestedDragdropIds(root, sourceId);

                // Save the session
                melisPluginEdition.sendDragnDropList(id, pageId, parent);
            });
        }

        function updateNestedDragdropIds($element, baseId) {
            let index = 1;

            // Find only the FIRST nested .melis-dragdropzone-container for each branch
            $element.find('.melis-dragdropzone-container').each(function () {
                const $child = $(this);

                // Skip if it's already been renamed in a deeper recursion
                if ($child.data('renamed')) return;

                const newId = `${baseId}_${index}`;

                // Update all attributes
                $child.attr('data-dragdropzone-id', newId);
                if ($child.attr('data-plugin-id')) $child.attr('data-plugin-id', newId);
                if ($child.attr('data-tag-id')) $child.attr('data-tag-id', newId);
                if ($child.attr('id')) $child.attr('id', newId);

                // Mark as renamed to avoid renaming again in deeper recursions
                $child.data('renamed', true);

                // Recurse on this child to rename its descendants
                updateNestedDragdropIds($child, newId);

                index++;
            });
        }

        function getNextPluginIndex(sourceContainer) {
            let max = 0;
            $(sourceContainer).find('[data-plugin-id]').each(function () {
                let el = $(this);
                if($(this).parent().hasClass('melis-ui-outlined')){
                    let pluginId = el.attr('data-plugin-id');
                    let match = pluginId.match(/^(.*?)(\d+)$/);

                    if(match){
                        var tagId = parseInt(match[2], 10);
                        if(max < tagId){
                            max = tagId;
                        }

                    }
                }
            });
            return max;
        }

        // for easy top position based on .dnd-layout-buttons height
        topPositionLayoutButtons();

		function updateDndOrder(pageId, callback) {

			let dndIds = [];
			$("body .dnd-plugins-content").each((i, v) => {
				let dnd = $(v).find("> div > div > div.melis-dragdropzone-container");
				let dnds = [];

				dnd.each((k, data) => {
					dnds.push($(data).data("dragdropzoneId"));
				});

				dndIds.push(dnds)
			});
            
			if (dndIds)
				$.post("/dnd-update-order", {dndIds, pageId}).always(() => {
					// updated
					if (typeof callback == "function")
						callback();

					return;
				});

			return;
		}

        /* 
         * handle the display of arrow buttons
         * first row - up arrow not displayed
         * last row - arrow down not displayed 
         **/
        function handleDisplayRemoveArrowButtons() {
            let $dndPluginsContent  = $(".dnd-plugins-content");
                if ($dndPluginsContent.length) {
                    $dndPluginsContent.each((i, v) => {
                        let $dndPluginContent   = $(v),
                            $dndRow             = $dndPluginContent.find("> .row");

                            $dndRow.each((i, v) => {
                                let $row = $(v);
                                    //$row.find(".dnd-arrow-down").toggle(i !== $dndRow.length - 1);

                                    //console.log(`$dndRow.length: `, $dndRow.length);
                                    if ($dndRow.length === 1) {
                                        // show/hide button based on position
                                        $row.find(".dnd-arrow-up").toggle(i !== 0);
                                        $row.find(".dnd-arrow-down").toggle(i !== 0);

                                        $row.find(".dnd-remove-button").prop("disabled", true);
                                        $row.find(".dnd-remove-button").prepend(`<i class="fa fa-ban"></i>`);
                                    }
                                    else {
                                        $row.find(".dnd-arrow-down").toggle(i !== $dndRow.length - 1);

                                        $row.find(".dnd-remove-button").prop("disabled", false);
                                        $row.find(".dnd-remove-button .fa-ban").remove();
                                    }
                            });
                    });
                }
        }
        
        // function call
        handleDisplayRemoveArrowButtons();
});

// .dnd-layout-buttons offset top on .dnd-layout-wrapper and other elements manipulation
window.topPositionLayoutButtons = function() {
    let $layoutButtons  = $(".dnd-layout-buttons"),
        $zones          = $(".melis-dragdropzone.ui-sortable");

        $layoutButtons.each(function() {
            let $layoutButton           = $(this),
                layoutButtonHeight      = $layoutButton.outerHeight(),
                $pluginTitleSubTools    = $layoutButton.find(".dnd-plugin-title-and-sub-tools"),
                $subToolsWrapped        = $layoutButton.find(".dnd-plugin-sub-tools"),
                subToolsWrappedWidth    = $subToolsWrapped.outerWidth(),
                $subTools               = $layoutButton.find(".dnd-plugin-sub-tools.layout-buttons-wrapped"),
                subToolsWidth           = $subTools.outerWidth(),
                $pluginTitleBox         = $pluginTitleSubTools.find(".melis-plugin-title-box"),
                pluginTitleBoxWidth     = $pluginTitleBox.outerWidth(),
                $removeButton           = $subTools.find(".dnd-remove-button");
                
                $layoutButton.css("top", -(layoutButtonHeight - 4)); // - 4 to make sure it overlaps the .dnd-layout-buttons hoverable space

                //$pluginTitleSubTools.css("height", layoutButtonHeight - 8); // 8 for padding top 4px and bottom 4px

                // check .dnd-plugin-sub-tools width if .dnd-remove-button is present
                if ($removeButton.length) {
                    $pluginTitleSubTools.addClass("has-remove-button");
                }
                else {
                    $pluginTitleSubTools.removeClass("has-remove-button");
                }
                
                // add min-width on .dnd-plugin-sub-tools
                if (subToolsWidth < subToolsWrappedWidth) {
                    $pluginTitleSubTools.css("min-width", subToolsWidth + pluginTitleBoxWidth + 23);
                }
                else {
                    $pluginTitleSubTools.css("min-width", subToolsWrappedWidth + pluginTitleBoxWidth + 23);
                }
        });

        // check dragdropzone with empty row, related on updated dragdropzone icon
        $zones.each(function() {
            let $zone = $(this),
                $col = $zone.find(".row .col-12");

                if ($col.is(":empty")) {
                    $col.closest(".row").remove();
                }

                if ($zone.parents(".no-content").length) {
                    $zone.parents(".no-content").removeClass("no-content").addClass("content-added");
                }
                else {
                    $zone.parents(".content-added").removeClass("content-added").addClass("no-content");
                }
        });
};

function adjustLayoutButtonMargins() {
    const $buttons  = $(".dnd-layout-buttons .column-icons .dnd-column-layout"),
        $subTools   = $(".dnd-layout-buttons .dnd-plugin-sub-tools");

        if ($buttons.length === 0) return;

        // clear previous states
        $subTools.removeClass("layout-buttons-wrapped");

        const buttonTops = new Set();
            $buttons.each(function() {
                //const top = $(this).offset().top;
                const top = Math.round($(this).position().top);
                    //console.log({top});
                    buttonTops.add(top);
            });

            //console.log({buttonTops});
            if (buttonTops.size > 1) {
                //console.log('Buttons have wrapped to a second line.');

                // Optional: add class to the container or take some action
                $subTools.addClass('layout-buttons-wrapped');
            } else {
                //console.log('All buttons are on a single line.');
                $subTools.removeClass("layout-buttons-wrapped");
            }
        // for .dnd-layout-buttons top positioning
        topPositionLayoutButtons();
}

// run on load and resize
$(window).on('load resize', adjustLayoutButtonMargins);