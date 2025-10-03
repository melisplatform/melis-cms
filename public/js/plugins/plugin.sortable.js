var handleLocation;
var melisPluginSortable = (function($, window) {
	var $body = $("body");

    /* ==================================
            Initialized
    ====================================*/
    $(".melis-dragdropzone").sortable({
    	// when changing plugins order
    	stop: function(event, ui) {
            $(".melis-dragdropzone").removeClass("highlight");
            if ( ui.item[0] ) { 
            	// reset items index
	            $(ui.item[0]).css({"z-index": "1"});

            	var plugin 			= $(ui.item[0]).find(".melis-plugin-tools-box"),
                	moduleName 		= plugin.data("module"),
                	pluginName 		= plugin.data("plugin"),
                	pluginId 		= plugin.data("plugin-id"),
                	melisTag 		= plugin.data("melis-tag"),
                	pluginContainer = plugin.closest(".melis-float-plugins"),
                	pageId 			= window.parent.$("#"+parent.activeTabId).find(".melis-iframe").data("iframe-id"),
                	dropzone 		= plugin.closest(".melis-dragdropzone"),
                	dropzoneData 	= dropzone.data("plugin-id");

                // check plugin if wrap
				if( $(pluginContainer).length <= 0 ) {
                    $(plugin).data("plugin-container", "");
				}
				
                // melisPluginEdition.pluginContainerChecker(); // disable for now
				melisPluginEdition.sendDragnDropList(dropzoneData, pageId);
                melisPluginEdition.pluginDetector();
            }
    	},
    });

	$body.on("click", ".m-options-handle", function() {
		var $this = $("this");

			window.fromdragdropzone = $this.closest(".melis-plugin-tools-box").data("melis-fromdragdropzone");

		// Need a callback to load showModalSlider
		setTimeout(function(){ checkedModalSlider(); }, 2000);
	});

	var modalUl,
		ulPos,
		maxPosNext,
		modalContainerWidth,
		maxLiWidth = 0,
		ulEndPoint = false,
		maxPosPrev = 25;

	function checkedModalSlider() {
		modalUl = window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs");
	    modalContainerWidth = window.parent.$("#id_meliscms_plugin_modal").outerWidth();

	    var modalNavContainer 	= 0,
	    	minSize 			= 150,
	    	maxSize 			= 225,
	    	modalTabs 			= window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs li"),
	    	tabLength 			= modalTabs.length,
	    	tabSize 			= tabLength * maxSize;

	    maxPosNext = tabSize / tabLength;

	    window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs li").each(function() {
	    	var $this = $(this);
	        modalNavContainer += $this.outerWidth();
	        var el = $this.outerWidth();
	        maxLiWidth = Math.max(maxLiWidth, el);
	    });

	    if ( modalNavContainer > modalContainerWidth ) {
	    	modalTabs.width(minSize);
			window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs").width(tabSize);
			window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box").width(modalContainerWidth - 50);
			window.parent.$("body .widget-melis-tabprev").addClass("active");
			window.parent.$("body .widget-melis-tabnext").addClass("active");
	    } else {
            window.parent.$("body .widget-melis-tabprev, .widget-melis-tabnext").removeClass("active");
	    	if ( modalNavContainer == 0 ) {
                window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box").css({"width": "auto"});
            } else {
                window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box").width(modalContainerWidth);
			}
	    }
	}

	function modalSlideNext() {
    	if ( modalUl ) {
 	    	var posLeft = Math.abs(modalUl.position().left);
		    	if ( posLeft >= 25 && posLeft <= maxPosNext + 50 ) {
		    		modalUl.finish().animate({
			    		'left': '-=150',
			    	}, 300);
		    	}
    	}
	}

	function modalSlidePrev() {
    	if ( modalUl ) {
	    	var posLeft = Math.abs(modalUl.position().left);
		    	if ( posLeft >= 125 ) {
		    		modalUl.finish().animate({
			    		'left': '+=150',
			    	}, 300);
		    	}
    	}
	}


    //PREV 
    window.parent.$("body").on("click", ".widget-melis-tabprev", modalSlidePrev);

    //NEXT 
    window.parent.$("body").on("click", ".widget-melis-tabnext", modalSlideNext);


	$(window.parent).on("resize", function () {
	    checkedModalSlider();
	});

    return {
    	checkedModalSlider 			: 		checkedModalSlider
    }
 })(jQuery, window);