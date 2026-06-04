var melisDragnDrop = (function ($, window) {
	var centerWindow,
		placeholderWidth,
		$body = $("body"),
		scrollBool = true,
		scrollH = window.parent.$("body")[0].scrollHeight,
		currentFrame = window.parent.$(
			"iframe[data-iframe-id='" + melisActivePageId + "']"
		);

	/* ==================================
		Binding Events
		====================================*/
	$body.on("click", "#melisPluginBtn", showPluginMenu);
	$body.on("click", ".melis-cms-filter-btn", showPlugLists);
	$body.on("click", ".melis-cms-category-btn", showCatPlugLists);

	/* ==================================
		Drag & Drop
		====================================*/
	var DND_DRAGGING_CLASS = "melis-dnd-dragging";
	var railMoveTimer = null;

	function pluginWidthClassSuffix(percent) {
		return parseFloat(percent).toFixed(2).replace(".", "-");
	}

	/**
	 * Width classes for the active preview breakpoint; other breakpoints stay full width.
	 */
	function pluginWidthClassesForFrame(percent, frameWidth) {
		var suffix = pluginWidthClassSuffix(percent);

		if (frameWidth <= 480) {
			return (
				"plugin-width-xs-" +
				suffix +
				" plugin-width-md-100-00 plugin-width-lg-100-00"
			);
		}
		if (frameWidth > 490 && frameWidth <= 980) {
			return (
				"plugin-width-xs-100-00 plugin-width-md-" +
				suffix +
				" plugin-width-lg-100-00"
			);
		}
		return (
			"plugin-width-xs-100-00 plugin-width-md-100-00 plugin-width-lg-" +
			suffix
		);
	}

	function savedWidthsForFrame(percent, frameWidth) {
		var width = parseFloat(percent);

		if (frameWidth <= 480) {
			return { mobile: width, tablet: 100, desktop: 100 };
		}
		if (frameWidth > 490 && frameWidth <= 980) {
			return { mobile: 100, tablet: width, desktop: 100 };
		}
		return { mobile: 100, tablet: 100, desktop: width };
	}

	function isDragItemFullWidth(ui) {
		var $item = $(ui.item[0]);
		var $toolBox = $item.find(".melis-plugin-tools-box").first();
		var moduleName =
			$item.attr("data-module-name") || $toolBox.data("module");
		var pluginName = $toolBox.data("plugin") || $item.data("pluginName");

		return (
			moduleName === "MelisMiniTemplate" ||
			pluginName === "MelisFrontBlockSectionPlugin"
		);
	}

	function refreshStackedZoneClasses() {
		$(".melis-dragdropzone").each(function () {
			$(this).toggleClass(
				"is-stacked",
				zoneHasOnlyFullWidthBlocks($(this))
			);
		});
	}

	function applyStackedPlaceholder(ui, $zone) {
		if (!ui.placeholder || !ui.placeholder[0]) {
			return false;
		}

		if (!zoneHasOnlyFullWidthBlocks($zone) && !isDragItemFullWidth(ui)) {
			return false;
		}

		$(ui.placeholder[0]).css({
			width: "100%",
			"min-height": "8px",
			height: "8px",
			margin: 0,
			padding: 0,
		});

		return true;
	}

	function removeInsertRails($zones) {
		$zones.find(".melis-dnd-insert-rail").remove();
	}

	function syncInsertRails($zone) {
		removeInsertRails($zone);

		if (
			!$body.hasClass(DND_DRAGGING_CLASS) ||
			!zoneHasOnlyFullWidthBlocks($zone)
		) {
			return;
		}

		var $items = getZoneSortableItems($zone).not(".ui-sortable-helper");

		$items.each(function (i) {
			$(
				'<div class="melis-dnd-insert-rail" data-insert-index="' +
					i +
					'" aria-hidden="true"></div>'
			).insertBefore(this);
		});

		$(
			'<div class="melis-dnd-insert-rail melis-dnd-insert-rail--end" data-insert-index="' +
				$items.length +
				'" aria-hidden="true"></div>'
		).appendTo($zone);
	}

	function movePlaceholderToIndex($zone, index) {
		var $placeholder = $zone.children(".ui-sortable-placeholder");

		if (!$placeholder.length) {
			return;
		}

		var $items = getZoneSortableItems($zone).not(".ui-sortable-helper");

		if (!$items.length) {
			return;
		}

		if (index <= 0) {
			$placeholder.insertBefore($items.first());
		} else if (index >= $items.length) {
			$placeholder.insertAfter($items.last());
		} else {
			$placeholder.insertBefore($items.eq(index));
		}

		if ($zone.data("ui-sortable")) {
			$zone.sortable("refreshPositions");
		}

		applyStackedPlaceholder(
			{ placeholder: $placeholder, item: $zone.find(".ui-sortable-helper") },
			$zone
		);
	}

	function beginSortableDrag($zone, ui) {
		$body.addClass(DND_DRAGGING_CLASS);
		$(".melis-dragdropzone").addClass("highlight");
		refreshStackedZoneClasses();
		applySortableAxisForZone($zone);
		syncInsertRails($zone);

		if (!applyStackedPlaceholder(ui, $zone)) {
			var placeholderWidthPct =
				(100 * parseFloat($(ui.helper[0]).css("width"))) /
					parseFloat($(ui.helper[0]).parent().css("width")) +
				"%";
			$(ui.placeholder[0]).css("width", placeholderWidthPct);
		}

		var moduleName = $(ui.helper[0]).attr("data-module-name");

		if (moduleName === "MelisMiniTemplate") {
			$(ui.helper[0]).css({
				height: "auto",
				"padding-left": "10px",
				"padding-right": "10px",
			});
		}
	}

	function endSortableDrag() {
		$body.removeClass(DND_DRAGGING_CLASS);
		$(".melis-dragdropzone").removeClass("highlight");
		removeInsertRails($(".melis-dragdropzone"));
		resetSortableAxisOnAllZones();
		refreshStackedZoneClasses();
		$(window).off("mousemove");
	}

	function beginSnippetDrag(ui) {
		$(ui.helper).find(".melis-plugin-tooltip").hide();
		$body.addClass(DND_DRAGGING_CLASS);
		$(".melis-dragdropzone").addClass("highlight").removeClass("no-content");
		refreshStackedZoneClasses();
		$(".melis-dragdropzone").each(function () {
			syncInsertRails($(this));
		});
		$(".ui-sortable-placeholder").css({
			background: "#7c3aed",
			border: "none",
			"min-height": "8px",
			height: "8px",
			width: "100%",
		});
	}

	function endSnippetDrag() {
		endSortableDrag();
		if (typeof melisPluginEdition !== "undefined") {
			melisPluginEdition.pluginDetector();
		}
	}

	function initDraggableSnippets() {
		$(".melis-cms-plugin-snippets").each(function () {
			var $snippet = $(this);
			if ($snippet.data("ui-draggable")) {
				$snippet.draggable("destroy");
			}
		});

		$(".melis-cms-plugin-snippets").draggable({
			connectWith: ".melis-draggable",
			connectToSortable: ".melis-dragdropzone",
			revert: true,
			helper: "clone",
			start: function (event, ui) {
				beginSnippetDrag(ui);
			},
			stop: function () {
				endSnippetDrag();
			},
		});
	}

	initDraggableSnippets();

	$body.on("mouseenter", ".melis-dnd-insert-rail", function () {
		if (!$body.hasClass(DND_DRAGGING_CLASS)) {
			return;
		}

		var $rail = $(this);
		var $zone = $rail.closest(".melis-dragdropzone");
		var index = parseInt($rail.data("insert-index"), 10);

		if (isNaN(index) || !$zone.length) {
			return;
		}

		$zone.find(".melis-dnd-insert-rail").removeClass(
			"melis-dnd-insert-rail--active"
		);
		$rail.addClass("melis-dnd-insert-rail--active");

		clearTimeout(railMoveTimer);
		railMoveTimer = setTimeout(function () {
			movePlaceholderToIndex($zone, index);
		}, 16);
	});

	/**
	 * Sortable items in a dropzone (direct children or inside .melis-float-plugins).
	 */
	function getZoneSortableItems($zone) {
		return $zone
			.children(".melis-ui-outlined")
			.add($zone.children(".melis-float-plugins").children(".melis-ui-outlined"));
	}

	function getActivePluginWidthPrefix(frameWidth) {
		if (frameWidth <= 480) {
			return "plugin-width-xs-";
		}
		if (frameWidth > 490 && frameWidth <= 980) {
			return "plugin-width-md-";
		}
		return "plugin-width-lg-";
	}

	function getPreviewFrameWidth() {
		if (currentFrame && currentFrame.length) {
			return currentFrame.width();
		}
		return $(window).width();
	}

	/**
	 * True when a plugin block spans ~full width of its dropzone (stacked layout).
	 */
	function isMelisUiOutlinedFullWidth($outlined, $zone) {
		var $toolBox = $outlined.find(".melis-plugin-tools-box").first();
		var pluginName =
			$toolBox.data("plugin") || $outlined.data("pluginName");
		var moduleName =
			$toolBox.data("module") || $outlined.attr("data-module-name");

		if (
			pluginName === "MelisFrontBlockSectionPlugin" ||
			moduleName === "MelisMiniTemplate"
		) {
			return true;
		}

		var zoneWidth = $zone.width();

		if (zoneWidth > 0) {
			var widthPercent = (100 * $outlined.outerWidth()) / zoneWidth;

			// Trust rendered width: sub-90% blocks are side-by-side even if stale width classes remain.
			if (widthPercent < 90) {
				return false;
			}
			if (widthPercent >= 90) {
				return true;
			}
		}

		var frameWidth = getPreviewFrameWidth();
		var activePrefix = getActivePluginWidthPrefix(frameWidth);
		var classes = $outlined.attr("class") || "";
		var activeClassMatch = classes.match(
			new RegExp(activePrefix.replace(/-/g, "\\-") + "[\\d-]+")
		);

		if (activeClassMatch) {
			return activeClassMatch[0] === activePrefix + "100-00";
		}

		var widthAttr =
			frameWidth <= 480
				? "data-plugin-width-mobile"
				: frameWidth > 490 && frameWidth <= 980
					? "data-plugin-width-tablet"
					: "data-plugin-width-desktop";
		var savedWidth = parseFloat($toolBox.attr(widthAttr));

		if (!isNaN(savedWidth)) {
			return savedWidth >= 90;
		}

		return false;
	}

	/**
	 * Vertical-only drag when every item in the zone is full-width (e.g. mini templates).
	 */
	function zoneHasOnlyFullWidthBlocks($zone) {
		var $items = getZoneSortableItems($zone);

		if (!$items.length) {
			return false;
		}

		var allFullWidth = true;
		$items.each(function () {
			if (!isMelisUiOutlinedFullWidth($(this), $zone)) {
				allFullWidth = false;
				return false;
			}
		});

		return allFullWidth;
	}

	function applySortableAxisForZone($zone) {
		$zone.sortable(
			"option",
			"axis",
			zoneHasOnlyFullWidthBlocks($zone) ? "y" : false
		);
	}

	function resetSortableAxisOnAllZones() {
		$(".melis-dragdropzone").each(function () {
			$(this).sortable("option", "axis", false);
		});
	}

	function setDragDropZone() {
		var isCrossDropzoneMove = false;

		$(".melis-dragdropzone").each(function () {
			var $zone = $(this);
			if ($zone.data("ui-sortable")) {
				$zone.sortable("destroy");
			}
		});

		$(".melis-dragdropzone").sortable({
			connectWith: ".melis-float-plugins, .melis-dragdropzone",
			connectToSortable: ".melis-float-plugins",
			handle: ".m-move-handle",
			cursor: "move",
			cursorAt: { top: 0, left: 0 },
			zIndex: 999999,
			placeholder: "ui-state-highlight",
			tolerance: "intersect", // "pointer", or "fit", 
			forcePlaceholderSize: true, // or remove
			scroll: false, // or remove
			distance: 5, // or remove
			items: ".melis-ui-outlined",
			start: function (event, ui) {
				var $zone = $(this);

				$(".melis-dragdropzone").sortable("refresh");

				// hide tinyMCE panel
				$(".mce-tinymce.mce-panel.mce-floatpanel").hide();

				$(".ui-sortable-helper").css("z-index", "9999999");
				beginSortableDrag($zone, ui);

				// detect if browser is in desktop
				if ($(window).width() >= 768) {
					$(window).on("mousemove", function (e) {
						var top;
						var frameTop =
							window.parent
								.$("#" + parent.activeTabId)
								.find(".melis-iframe")
								.offset().top + 10;

						if (window.parent.$(".sticky-pageactions")) {
							top = $(window.parent).scrollTop() - 130;
						} else {
							top = 0;
						}

						// check if there is a plugin being drag
						if (
							$(".ui-sortable-helper") &&
							$(".ui-sortable-helper").length > 0
						) {
							var bottom = $(window.parent).height();

							// hide plugin panel when dragging a plugin
							if ($(".melis-cms-dnd-box").hasClass("show")) {
								$(".melis-cms-dnd-box").removeClass("show");
							}

							if (
								e.clientY >=
								$(window.parent).scrollTop() +
									$(window.parent).height() -
									frameTop
							) {
								// detect IE8 and above, and edge
								if (document.documentMode || /Edge/.test(navigator.userAgent)) {
									// activate scrollTop on IE
									window.parent
										.$("html")
										.css({ overflow: "auto", height: "auto" });
								}

								window.parent.$("html, body").animate(
									{
										scrollTop:
											$(window.parent).scrollTop() +
											$(window.parent).height() / 2,
									},
									300
								);
							} else if (e.clientY <= top && $(window.parent).scrollTop() > 0) {
								// detect IE8 and above, and edge
								if (document.documentMode || /Edge/.test(navigator.userAgent)) {
									// activate scrollTop on IE
									window.parent
										.$("html")
										.css({ overflow: "auto", height: "auto" });
								}

								window.parent.$("html, body").animate(
									{
										scrollTop:
											$(window.parent).scrollTop() -
											$(window.parent).height() / 2,
									},
									300
								);
							} else {
								window.parent.$("html, body").stop();
							}
						} else {
							// detect IE8 and above, and edge
							if (document.documentMode || /Edge/.test(navigator.userAgent)) {
								// activate scrollTop on IE
								window.parent
									.$("html")
									.css({ overflow: "hidden", height: "100%" });
							}
						}
					});
				} else {
					$(".melis-cms-dnd-box").removeClass("show");
				}
			},
			receive: function (event, ui) {
				var tabId;
				// check if ui is from pluginMenu, new plugin from pluginMenu
				if (ui.helper && $(ui.helper).hasClass("melis-cms-plugin-snippets")) {
					// console.log(`dragndrop.js setDragDropZone() receive: function new plugin dropped...`);
					var moduleName = $(ui.helper[0]).data("module-name");
					var pluginName = $(ui.helper[0]).data("plugin-name");
					var siteModule = $(ui.helper[0]).data("plugin-site-module");
					// get id of current dragzone
					var dropzone = $(event.target).data("dragdropzone-id");
					tabId = window.parent
						.$("#" + parent.activeTabId)
						.find(".melis-iframe")
						.data("iframe-id");

					var dataKeysfromdragdropzone = $(ui.helper[0]).data(
						"melis-fromdragdropzone"
					);
					var dropLocation = ui.helper[0];
					// remove Clone
					// ui.helper[0].remove();
					setTimeout(function () {
						if (moduleName !== undefined) {
							requestPlugin(
								moduleName,
								pluginName,
								dropzone,
								tabId,
								dropLocation,
								siteModule
							);
						}
					}, 300);
				} // check ui.helper
				else if (ui.sender[0]) { // existing plugin moved between dropzones
					isCrossDropzoneMove = true;

					var dragZoneSender = ui.sender[0];
					var dragZoneSenderPluginId = $(dragZoneSender).data("plugin-id");

						// send dragndrop list, save source dropzone
						melisPluginEdition.sendDragnDropList(dragZoneSenderPluginId, tabId);

						// console.log(`receive: function, existing plugin dropped, melisPluginEdition.sendDragnDropList() called`);
						if (typeof dragZoneSenderPluginId != "undefined") {
							// console.log(`typeof dragZoneSenderPluginId: `, typeof dragZoneSenderPluginId);
							let parentOuterDnd = $(dragZoneSender).parents(".melis-dragdropzone-container:last");
								parentOuterDndPluginId = parentOuterDnd.data("pluginId");

							let currentPluginDnd = $(ui.item[0]).parents(".melis-dragdropzone-container:last");
							let currentPluginDndId = currentPluginDnd.data("pluginId");

								// saving data to session when plugin drag to another dnd, save target dropzone
								if (parentOuterDndPluginId != currentPluginDndId)
									// console.log(`parentOuterDndPluginId != currentPluginDndId true, melisPluginEdition.sendDragnDropList() called`);
									melisPluginEdition.sendDragnDropList(currentPluginDndId, tabId);
						}
				}

				// related on updated dragdropzone icon
				$(".melis-dragdropzone").parents(".no-content").removeClass("no-content").addClass("content-added");

				melisPluginEdition.pluginDetector();
				refreshStackedZoneClasses();

				// remove empty row inside .melis-dragdropzone
				removeEmptyRow();
			},
			update: function (event, ui) {
				$(".ui-sortable-helper").remove();
			},
			over: function (event, ui) {
				var $zone = $(this);

				$("body .melis-dragdropzone").not(this).removeClass("highlight");
				$zone.addClass("highlight");

				applySortableAxisForZone($zone);
				$(".melis-dragdropzone").not($zone).each(function () {
					removeInsertRails($(this));
				});
				syncInsertRails($zone);

				if (zoneHasOnlyFullWidthBlocks($zone)) {
					applyStackedPlaceholder(ui, $zone);
				} else {
					setPluginWidth(ui, $zone);
				}
			},
			out: function (event, ui) {
				$(this).removeClass("highlight");
			},
			stop: function(event, ui) {
				endSortableDrag();

				/* only save if:
				1. NOT a new plugin being dragged dropped  
				2. item was NOT moved from another sortable (already handled in receive) */
				if (!$(ui.item).hasClass("melis-cms-plugin-snippets") && !isCrossDropzoneMove) {
					// console.log(`dragndrop.js setDragDropZone() stop: function rearranging existing plugins - saving order...`);
					var $dropzone = $(this);
					var dropzoneId = $dropzone.data("dragdropzone-id");
					var tabId = window.parent.$("#" + parent.activeTabId).find(".melis-iframe").data("iframe-id");
					
					if (typeof melisPluginEdition !== "undefined" && dropzoneId) {
						melisPluginEdition.sendDragnDropList(dropzoneId, tabId);
					}
				}

				if (typeof melisPluginEdition !== "undefined") {
					melisPluginEdition.pluginDetector();
				}

				// reset the flag
    			isCrossDropzoneMove = false;
			},
			change: function (event, ui) {
				var $zone = $(this);

				if (zoneHasOnlyFullWidthBlocks($zone)) {
					applyStackedPlaceholder(ui, $zone);
				} else {
					setPluginWidth(ui, $zone);
				}
			},
		});
	}

	// remove empty row inside .melis-dragdropzone
	function removeEmptyRow() {
		const $col = $(".melis-dragdropzone").find("> .row .col-12");
			if ($col.is(":empty")) {
				$col.closest(".row").remove();
			}
	}

	setDragDropZone();
	refreshStackedZoneClasses();

	// set plugin container width by placeholder
	function setPluginWidth(ui, $zone) {
		$zone = $zone || $(ui.placeholder[0]).closest(".melis-dragdropzone");

		if ($zone.length && zoneHasOnlyFullWidthBlocks($zone)) {
			applyStackedPlaceholder(ui, $zone);
			placeholderWidth = $(ui.placeholder[0]);
			return;
		}

		if (isDragItemFullWidth(ui)) {
			$(ui.placeholder[0]).css("width", "100%");
			placeholderWidth = $(ui.placeholder[0]);
			return;
		}

		var data = $(ui.item[0]).data();
		if (data.pluginName == "MelisFrontBlockSectionPlugin") {
			$(ui.placeholder[0]).css("width", "100%");
		}

		var prevSibling = $(".ui-sortable-placeholder.ui-state-highlight").prev();
		if ($(".melis-dragdropzone .melis-ui-outlined").length == 0) {
			$(ui.placeholder[0]).css("width", "100%");
		}

		if ($(prevSibling).hasClass("melis-cms-plugin-snippets")) {
			prevSibling = $(prevSibling).prev();
		}

		var prevSiblingWidth =
			(100 * parseFloat($(prevSibling).css("width"))) /
			parseFloat($(prevSibling).parent().css("width"));
		var availableSpace;

		if (
			$(ui.item[0]).hasClass("melis-cms-plugin-snippets") &&
			prevSiblingWidth > 90
		) {
			$(ui.placeholder[0]).css("width", "100%");
		}

		if (
			$(ui.item[0]).hasClass("melis-cms-plugin-snippets") &&
			prevSiblingWidth < 90 &&
			data.pluginName != "MelisFrontBlockSectionPlugin"
		) {
			availableSpace = 100 - prevSiblingWidth - 2;
			$(ui.placeholder[0]).css("width", availableSpace + "%");
		}

		placeholderWidth = $(ui.placeholder[0]);
	}

	// jQuery Tooltip @ https://jqueryui.com/tooltip/#custom-style
	$(".melis-cms-plugin-snippets").tooltip({
		position: {
			my: "left center",
			at: "left+115% center",
			using: function (position, feedback) {
				var $this = $(this);
				$this.css(position);
				$this
					.addClass("melis-plugin-tooltip")
					.addClass(feedback.vertical)
					.addClass(feedback.horizontal)
					.appendTo(this);
			},
		},
	});

	$(".melis-cms-plugin-snippets")
		.on("mouseenter", function () {
			var $this = $(this);
			$this.children(".melis-plugin-tooltip").fadeIn();
		})
		.on("mouseleave", function () {
			var $this = $(this);
			$this.children(".melis-plugin-tooltip").fadeOut();
		});

	$body.on("click", ".melis-cms-filter-btn-sub-category", function () {
		var elem = $(this),
			next = elem.next(),
			textVal = elem.text(),
			activeMenu = elem.parent().find(".active");

		if (activeMenu.length > 0) {
			activeMenu.next().slideUp();
			activeMenu.removeClass("active");
			//activeMenu.find('.melis-plugins-icon-new-sub-child').removeClass('reverse-color');
			if (activeMenu.text() !== textVal) {
				next.slideDown();
				elem.addClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').addClass('reverse-color');
			}
		} else {
			// show menu
			if (elem.hasClass("active")) {
				next.slideUp();
				elem.removeClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').removeClass('reverse-color');
			} else {
				elem.addClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').addClass('reverse-color');
				next.slideDown();
			}
		}
	});

	$body.on("click", ".melis-cms-filter-btn-mini-tpl-category", function () {
		var elem = $(this),
			next = elem.next(),
			textVal = elem.text(),
			activeMenu = elem
				.parent()
				.find(".melis-cms-filter-btn-mini-tpl-category.active");

		if (activeMenu.length > 0) {
			activeMenu.next().slideUp();
			activeMenu.removeClass("active");
			//activeMenu.find('.melis-plugins-icon-new-sub-child').removeClass('reverse-color');
			if (activeMenu.text() !== textVal) {
				next.slideDown();
				elem.addClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').addClass('reverse-color');
			}
		} else {
			// show menu
			if (elem.hasClass("active")) {
				next.slideUp();
				elem.removeClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').removeClass('reverse-color');
			} else {
				elem.addClass("active");
				//elem.find('.melis-plugins-icon-new-sub-child').addClass('reverse-color');
				next.slideDown();
			}
		}
	});

	// $( ".melis-editable" ).resizable({ disabled: true, handles: 'e' });
	function requestPlugin(
		module,
		plugin,
		dropzone,
		pageId,
		dropLocation,
		siteModule
	) {
		// locate plugin location
		var layout = $("div[data-dragdropzone-id=" + dropzone + "]");

		// add the temp loader
		var tempLoader =
			'<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
		$(layout).addClass("melis-loader").prepend(tempLoader);

		$.ajax({
			type: "GET",
			url:
				"/melispluginrenderer?module=" +
				module +
				"&pluginName=" +
				plugin +
				"&pageId=" +
				pageId +
				"&fromDragDropZone=1&melisSite=" +
				siteModule,
		})
			.done(function (plugins) {
				var loadedPlug = false,
					$link = $("link"),
					$script = $("script"),
					el,
					elAttribute;

				// hide the loader
				// $(".loader-icon").removeClass("spinning-cog").addClass("shrinking-cog");
				$("#loader").remove();

				if (plugins.success) {
					var elType, dataPluginID, idPlugin, jsUrl;

					// iterate object
					plugin = plugins.datas;

					// get the html
					var vhtml = plugin.html,
						melisIdPage = window.parent
							.$("#" + parent.activeTabId)
							.find("iframe")
							.data("iframe-id"),
						pluginToolBox = $(vhtml).find(".melis-plugin-tools-box"),
						pluginData = pluginToolBox.data(),
						pluginHardCodedConfigEl = $(vhtml).find(".plugin-hardcoded-conf");

					// extract the data keys
					var melisPluginModuleName =
							typeof pluginToolBox.data("module") != "undefined"
								? pluginToolBox.data("module")
								: "",
						melisPluginName =
							typeof pluginToolBox.data("plugin") != "undefined"
								? pluginToolBox.data("plugin")
								: "",
						melisPluginID =
							typeof pluginToolBox.data("plugin-id") != "undefined"
								? pluginToolBox.data("plugin-id")
								: "",
						melisPluginTag =
							typeof pluginToolBox.data("melis-tag") != "undefined"
								? pluginToolBox.data("melis-tag")
								: "",
						melisSiteModule = typeof pluginToolBox.data("site-module"),
						//melisPluginHardCodedConfig  = $.trim(pluginHardCodedConfigEl.text());
						melisPluginHardCodedConfig = pluginHardCodedConfigEl.text().trim();

					// dataPluginID = pluginToolBox.next().attr("id");
					var pluginOutlined = pluginToolBox.closest(".melis-ui-outlined");

					dataPluginID = pluginOutlined
						.find("[id*='" + melisPluginID + "']")
						.attr("id");

					if (typeof dataPluginID !== "undefined") {
						// get plugin id
						idPlugin = dataPluginID;
					}

					// create array of objects
					var datastring = [],
						uiPlaceHolderWidth = placeholderWidth.css("width");

					// re set the plugin width from drop placeholder
					setTimeout(function () {
						var pluginId = "#" + $(plugin.html).attr("id");
						var frameWidth = currentFrame.width();
						var placeholderPercent = 100;

						if (
							placeholderWidth &&
							placeholderWidth.length &&
							typeof uiPlaceHolderWidth === "string" &&
							uiPlaceHolderWidth.indexOf("%") !== -1
						) {
							placeholderPercent = parseFloat(uiPlaceHolderWidth);
						}

						$(pluginId).removeClass(function (index, css) {
							return (css.match(/\bplugin-width\S+/g) || []).join(" ");
						});
						$(pluginId).addClass(
							pluginWidthClassesForFrame(placeholderPercent, frameWidth)
						);
					}, 100);

					if (
						melisPluginModuleName &&
						melisPluginName &&
						melisPluginID &&
						melisPluginHardCodedConfig != ""
					) {
						datastring.push({ name: "melisIdPage", value: melisIdPage });
						datastring.push({
							name: "melisModule",
							value: melisPluginModuleName,
						});
						datastring.push({
							name: "melisPluginName",
							value: melisPluginName,
						});
						datastring.push({ name: "melisPluginId", value: melisPluginID });
						datastring.push({ name: "melisPluginTag", value: melisPluginTag });
						(function () {
							var placeholderPercent = 100;
							if (
								placeholderWidth &&
								placeholderWidth.length &&
								typeof uiPlaceHolderWidth === "string" &&
								uiPlaceHolderWidth.indexOf("%") !== -1
							) {
								placeholderPercent = parseFloat(uiPlaceHolderWidth);
							}
							var savedWidths = savedWidthsForFrame(
								placeholderPercent,
								currentFrame.width()
							);
							datastring.push({
								name: "melisPluginMobileWidth",
								value: savedWidths.mobile,
							});
							datastring.push({
								name: "melisPluginTabletWidth",
								value: savedWidths.tablet,
							});
							datastring.push({
								name: "melisPluginDesktopWidth",
								value: savedWidths.desktop,
							});
						})();

						// pass it in savePluginUpdate
						melisPluginEdition.savePluginUpdate(datastring, siteModule);
					}

					// adding plugin in dropzone
					// working, but append() function will add the plugin, always at the bottom, prepend() will always be on top
					//$('div[data-dragdropzone-id='+ dropzone +']').append(plugin.html);

					//var dropPlugin = $(plugin.html).insertAfter(dropLocation); // not working
					$(plugin.html).insertAfter(dropLocation);

					// Processing the plugin resources and initialization
					melisPluginEdition.processPluginResources(plugin.init, idPlugin);
					// Init Resizable

					if (parent.pluginResizable == 1) {
						melisPluginEdition.initResizable();
					}

					// remove plugin
					$(dropLocation).remove();

					// send new plugin list
					melisPluginEdition.sendDragnDropList(dropzone, pageId);

					$(layout).removeClass("melis-loader");
					$("#loader").remove();

					melisPluginEdition.calcFrameHeight();
					melisPluginEdition.disableLinks("a");
					melisPluginEdition.pluginDetector();
					melisPluginEdition.moveResponsiveClass(); // disable for now
				}
			})
			.always(function () {
				$("#loader").remove();
			})
			.fail(function (xhr, textStatus, errorThrown) {
				alert(translations.tr_meliscore_error_message);
			});
	}

	function showPluginMenu() {
		$(this).parent().toggleClass("show");

		/**
		 * This will request a plugin menu content
		 */
		var _this = $("#melisPluginBtn"),
			pageId = window.parent.activeTabId.split("_")[0];

		//get the melisSite value from the iframe's src
		let queryString = window.frameElement.getAttribute("src").split("?")[1];
		let params = new URLSearchParams(queryString);
		let melisSite = params.get("melisSite");

		if (_this.closest(".melis-cms-dnd-box").hasClass("show")) {
			if (!_this.closest(".melis-cms-dnd-box").hasClass("hasCached")) {
				$.ajax({
					type: "GET",
					data: { pageId, melisSite },
					url: "/MelisCms/FrontPlugins/renderPluginsMenuContent",
					beforeSend: function () {
						window.parent.loader.addLoadingCmsPluginMenu(
							pageId + "_id_meliscms_page"
						);
					},
				}).done(function (data) {
					$("#cmsPluginsMenuContent").html(data.view);
					setTimeout(function () {
						initDraggableSnippets();
						setDragDropZone();

						_this.closest(".melis-cms-dnd-box").addClass("hasCached");
					}, 1000);

					window.parent.loader.removeLoadingCmsPluginMenu(
						pageId + "_id_meliscms_page"
					);
				});
			}
		}
	}

	function showPlugLists() {
		var $this = $(this);
		if ($this.hasClass("active")) {
			$this
				.siblings(".melis-cms-plugin-snippets-box")
				.find(".melis-cms-category-btn.active");
			// $(this).find('.melis-plugins-icon-new-parent').removeClass('reverse-color');
			$this
				.removeClass("active")
				.siblings(".melis-cms-plugin-snippets-box")
				.slideUp();
			$this
				.siblings(".melis-cms-plugin-snippets-box")
				.find(".melis-cms-category-btn.active")
				.removeClass("active")
				.siblings(".melis-cms-category-plugins-box")
				.slideUp();
		} else {
			//$(".melis-cms-filter-btn.active").find('.melis-plugins-icon-new-parent').removeClass('reverse-color');
			$(".melis-cms-filter-btn.active")
				.removeClass("active")
				.siblings(".melis-cms-plugin-snippets-box")
				.slideUp();
			$this.addClass("active");
			//$(this).find('.melis-plugins-icon-new-parent').addClass('reverse-color');
			$(".melis-cms-filter-btn.active")
				.siblings(".melis-cms-plugin-snippets-box")
				.slideDown();
		}
	}

	function showCatPlugLists() {
		var $this = $(this);

		if ($this.hasClass("active")) {
			$this
				.removeClass("active")
				.siblings(".melis-cms-category-plugins-box")
				.slideUp();
			//$(this).find('.melis-plugins-icon-new-child').removeClass('reverse-color');
		} else {
			//$(".melis-cms-category-btn.active").find('.melis-plugins-icon-new-child').removeClass('reverse-color');
			$(".melis-cms-category-btn.active")
				.removeClass("active")
				.siblings(".melis-cms-category-plugins-box")
				.slideUp();
			$this.addClass("active");
			//$(this).find('.melis-plugins-icon-new-child').addClass('reverse-color');
			$(".melis-cms-category-btn.active")
				.siblings(".melis-cms-category-plugins-box")
				.slideDown();
		}
	}

	function pluginScrollPos() {
		// if( $(currentFrame).length ) {
		var dndHeight = $(window.parent).height() - currentFrame.offset().top - 5,
			stickyHead = window.parent
				.$("#" + melisActivePageId + "_id_meliscms_page")
				.find(".bg-white.innerAll"),
			widgetHeight = window.parent
				.$("#" + melisActivePageId + "_id_meliscms_page")
				.find(".widget-head.nav");

		$(".melis-cms-dnd-box").css("height", "100vh"); // default height

		// Chrome, Firefox etc browser
		$(window.parent).on("scroll", function () {
			if (
				stickyHead.offset().top + stickyHead.height() + 30 >=
				currentFrame.offset().top
			) {
				$(".melis-cms-dnd-box").css(
					"top",
					stickyHead.offset().top -
						currentFrame.offset().top +
						stickyHead.height() +
						30
				);
				dndHeight =
					$(window.parent).height() -
					stickyHead.height() -
					widgetHeight.height() -
					15;

				$(".melis-cms-dnd-box").height(dndHeight);
			} else {
				if ($(window).width() <= 767) {
					var mobileHeader =
						mobileHeader !== "undefined"
							? window.parent.$("body").find("#id_meliscore_header")
							: "";
					if ($(mobileHeader).length) {
						$(".melis-cms-dnd-box").css("height", "100vh");
						var topPosition =
							window.parent.$("body").find("#id_meliscore_header").offset()
								.top -
							currentFrame.offset().top +
							window.parent.$("body").find("#id_meliscore_header").height();
						if (topPosition > 0) {
							$(".melis-cms-dnd-box").css("top", topPosition);
							$(".melis-cms-dnd-box").height(
								$(window.parent).height() -
									window.parent
										.$("body")
										.find("#id_meliscore_header")
										.height() -
									5
							);
						}
					}
				} else {
					dndHeight = $(window.parent).height() - currentFrame.offset().top - 5;
					$(".melis-cms-dnd-box").css("top", 0);
					$(".melis-cms-dnd-box").height(dndHeight);
				}
			}
		});

		// For IE scroll giving different value
		if (window.parent) {
			window.parent.$("body").on("scroll", function () {
				if (
					stickyHead.offset().top + stickyHead.height() + 30 >=
					currentFrame.offset().top
				) {
					$(".melis-cms-dnd-box").css(
						"top",
						stickyHead.offset().top -
							currentFrame.offset().top +
							stickyHead.height() +
							30
					);
				} else {
					$(".melis-cms-dnd-box").css({
						top: 0,
						height:
							$(window.parent).height() -
							stickyHead.height() -
							widgetHeight.height() -
							15,
					});
				}
			});
		}

		$(".melis-cms-dnd-box").height(dndHeight);
	}

	if ($(currentFrame).length) {
		pluginScrollPos();
	}

	/**
	 * Pin a sample .ui-sortable-placeholder in the page so you can select
	 * and edit it in DevTools. Toggle off when done.
	 * @param {boolean} enable
	 */
	function debugPlaceholder(enable) {
		var debugClass = "melis-dnd-debug-placeholder";
		var sampleClass = "melis-dnd-debug-placeholder-sample";

		if (!enable) {
			$body.removeClass(debugClass);
			$("." + sampleClass).remove();
			return;
		}

		$body.addClass(debugClass);
		$("." + sampleClass).remove();

		var $zone = $(".melis-dragdropzone").first();
		if (!$zone.length) {
			console.warn(
				"[melisDragnDrop] No .melis-dragdropzone found — open a page with drag-drop content first."
			);
			return;
		}

		$zone.prepend(
			'<div class="ui-sortable-placeholder ui-state-highlight ' +
				sampleClass +
				'"></div>'
		);

		console.info(
			"[melisDragnDrop] Debug placeholder pinned. Inspect .ui-sortable-placeholder.ui-state-highlight in Elements, edit rules in dragndrop.css, then melisDragnDrop.debugPlaceholder(false)."
		);
	}

	return {
		requestPlugin: requestPlugin,
		showPluginMenu: showPluginMenu,
		pluginScrollPos: pluginScrollPos,
		setDragDropZone: setDragDropZone,
		refreshStackedZoneClasses: refreshStackedZoneClasses,
		debugPlaceholder: debugPlaceholder,
	};
})(jQuery, window);

// Global helper (works from CMS iframe console and parent Melis back-office console)
(function () {
	var fn = function (enable) {
		if (
			typeof melisDragnDrop === "undefined" ||
			typeof melisDragnDrop.debugPlaceholder !== "function"
		) {
			console.warn(
				"[melisDndDebugPlaceholder] dragndrop.js is stale or not loaded. Hard refresh (Ctrl+F5), then retry."
			);
			return;
		}
		return melisDragnDrop.debugPlaceholder(enable);
	};

	window.melisDndDebugPlaceholder = fn;

	try {
		if (window.parent && window.parent !== window) {
			window.parent.melisDndDebugPlaceholder = fn;
		}
	} catch (e) {
		// cross-origin parent
	}
})();

$(function () {
	var $pluginToolsBox = $(".melis-plugin-tools-box").not(".dnd-layout-buttons"),
		$optionsHandle = $pluginToolsBox.find(
			".m-plugin-sub-tools .m-options-handle"
		),
		$pluginSubTools = $pluginToolsBox.find(
			".m-plugin-sub-tools"
		);

		if ($optionsHandle.length) {
			$optionsHandle.closest(".melis-plugin-tools-box").removeClass("d-none");
		} else {
			if (!$pluginSubTools.length || $pluginSubTools.html().trim() === "") {
				$pluginToolsBox.addClass("d-none");
			} else {
				$pluginToolsBox.removeClass("d-none");
			}
		}
});
