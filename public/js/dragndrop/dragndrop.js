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
	$(".melis-cms-plugin-snippets").draggable({
		connectWith: ".melis-draggable",
		connectToSortable: ".melis-dragdropzone",
		revert: true,
		helper: "clone",
		start: function (event, ui) {
			$(ui.helper).find(".melis-plugin-tooltip").hide();
			$(".melis-dragdropzone").addClass("highlight").removeClass("no-content");
			$(".ui-sortable-placeholder").css("background", "#fff");
		},
		stop: function (event, ui) {
			$(".melis-dragdropzone").removeClass("highlight");
			melisPluginEdition.pluginDetector();
		},
	});

	function setDragDropZone() {
		$(".melis-dragdropzone").sortable({
			connectWith: ".melis-float-plugins, .melis-dragdropzone",
			connectToSortable: ".melis-float-plugins",
			handle: ".m-move-handle",
			cursor: "move",
			cursorAt: { top: 0, left: 0 },
			zIndex: 999999,
			placeholder: "ui-state-highlight",
			tolerance: "pointer",
			items: ".melis-ui-outlined",
			start: function (event, ui) {

				$(".melis-dragdropzone").sortable("refresh");

				// hide tinyMCE panel
				$(".mce-tinymce.mce-panel.mce-floatpanel").hide();

				// highlight dragdropzone
				$(".melis-dragdropzone").addClass("highlight");
				$(".ui-sortable-helper").css("z-index", "9999999");

				// get item percentage width
				var placeholderWidth =
					(100 * parseFloat($(ui.helper[0]).css("width"))) /
						parseFloat($(ui.helper[0]).parent().css("width")) +
					"%";
				$(ui.placeholder[0]).css("width", placeholderWidth);

				// change its css to fit for theme design specific for the melis minitemplate
				var ddn = $(ui.helper[0]).attr("data-module-name");

				if (ddn == "MelisMiniTemplate") {
					$(ui.helper[0]).css({
						height: "auto",
						"padding-left": "10px",
						"padding-right": "10px",
					});
				}

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
				// check if ui is from pluginMenu
				if (ui.helper && $(ui.helper).hasClass("melis-cms-plugin-snippets")) {
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

				if (ui.sender[0]) {
					var dragZoneSender = ui.sender[0];
					var dragZoneSenderPluginId = $(dragZoneSender).data("plugin-id");

					// send dragndrop list
					melisPluginEdition.sendDragnDropList(dragZoneSenderPluginId, tabId);

					if (typeof dragZoneSenderPluginId != "undefined") {
						let parentOuterDnd = $(dragZoneSender).parents(".melis-dragdropzone-container:last");
						parentOuterDndPluginId = parentOuterDnd.data("pluginId");

						let currentPluginDnd = $(ui.item[0]).parents(".melis-dragdropzone-container:last");
						let currentPluginDndId = currentPluginDnd.data("pluginId");

						// saving data to session when plugin drag to another dnd
						if (parentOuterDndPluginId != currentPluginDndId)
							melisPluginEdition.sendDragnDropList(currentPluginDndId, tabId);

					}
				}

				$(".melis-dragdropzone").removeClass("highlight");

				// related on updated dragdropzone icon
				$(".melis-dragdropzone").parents(".no-content").removeClass("no-content").addClass("content-added");

				melisPluginEdition.pluginDetector();

				// remove empty row inside .melis-dragdropzone
				removeEmptyRow();
			},
			update: function (event, ui) {
				$(".ui-sortable-helper").remove();
			},
			over: function (event, ui) {
				$("body .melis-dragdropzone").removeClass("highlight");
      			$(this).addClass("highlight");

				setPluginWidth(ui);
				melisPluginEdition.pluginDetector();
			},
			out: function(event, ui) {
				$(this).removeClass("highlight");

				// related on updated dragdropzone icon
				$(".melis-dragdropzone").parents(".content-added").removeClass("content-added").addClass("no-content");
			},
			stop: function(event, ui) {
				$("body .melis-dragdropzone").removeClass("highlight");
			},
			change: function (event, ui) {
				setPluginWidth(ui);
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

	// set plugin container width by placeholder
	function setPluginWidth(ui) {
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

					// re set the plugin width
					setTimeout(function () {
						var pluginId = "#" + $(plugin.html).attr("id");
						$(pluginId).removeClass(function (index, css) {
							return (css.match(/\bplugin-width\S+/g) || []).join(" "); // removes anything that starts with "plugin-width-"
						});
						var pluginClass = "." + $(pluginId).attr("class");
						uiPlaceHolderWidth = uiPlaceHolderWidth.slice(0, -1);
						var strPlaceholderWidth = parseFloat(uiPlaceHolderWidth).toFixed(2);
						// check if dragndrop mobile, tablet, desktop
						// check if resize in mobile
						if (currentFrame.width() <= 480) {
							$(pluginId).addClass(
								" plugin-width-md-100-00 plugin-width-lg-100-00 plugin-width-xs-100-00"
							); //uiPlaceHolderWidth
						}
						// check if resize in tablet
						if (currentFrame.width() > 490 && currentFrame.width() <= 980) {
							$(pluginId).addClass(
								" plugin-width-xs-100-00 plugin-width-lg-100-00 plugin-width-md-100-00"
							); //uiPlaceHolderWidth
						}
						// check if resize in desktop
						if (currentFrame.width() >= 981) {
							$(pluginId).addClass(
								" plugin-width-xs-100-00 plugin-width-md-100-00 plugin-width-lg-100-00"
							); //uiPlaceHolderWidth
						}
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
						datastring.push({ name: "melisPluginMobileWidth", value: 100 });
						datastring.push({ name: "melisPluginTabletWidth", value: 100 });
						datastring.push({ name: "melisPluginDesktopWidth", value: 100 });

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
						$(".melis-cms-plugin-snippets").draggable({
							connectWith: ".melis-draggable",
							connectToSortable: ".melis-dragdropzone",
							revert: true,
							helper: "clone",
							start: function (event, ui) {
								$(ui.helper).find(".melis-plugin-tooltip").hide();
								$(".melis-dragdropzone")
									.addClass("highlight")
									.removeClass("no-content");
								$(".ui-sortable-placeholder").css("background", "#fff");
							},
							stop: function (event, ui) {
								$(".melis-dragdropzone").removeClass("highlight");
								melisPluginEdition.pluginDetector();
							},
						});

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

	return {
		requestPlugin: requestPlugin,
		showPluginMenu: showPluginMenu,
		pluginScrollPos: pluginScrollPos,
		setDragDropZone: setDragDropZone,
	};
})(jQuery, window);

$(function () {
	var $pluginToolsBox = $(".melis-plugin-tools-box"),
		$optionsHandle = $pluginToolsBox.find(
			".m-plugin-sub-tools .m-options-handle"
		);

	if ($optionsHandle.length) {
		$optionsHandle.closest(".melis-plugin-tools-box").removeClass("d-none");
	} else {
		$pluginToolsBox.addClass("d-none");
	}
});
