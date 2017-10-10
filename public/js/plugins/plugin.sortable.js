var handleLocation;
var melisPluginSortable = (function($, window) {

    /* ==================================
            Initialized
    ====================================*/
    $(".melis-dragdropzone").sortable({
    	// when changing plugins order
    	stop: function(event, ui) {
            $(".melis-dragdropzone").removeClass("highlight");

            if(ui.item[0]) { 
            	// reset items index
	            $(ui.item[0]).css({"z-index": "1"});

            	var plugin = $(ui.item[0]).find(".melis-plugin-tools-box");
                var moduleName = plugin.data("module");
                var pluginName = plugin.data("plugin");
                var pluginId = plugin.data("plugin-id");
                var melisTag = plugin.data("melis-tag");
                var pluginContainer = plugin.closest(".melis-float-plugins");
                var pageId = window.parent.$("#"+parent.activeTabId).find(".melis-iframe").data("iframe-id");
                var dropzone = plugin.closest(".melis-dragdropzone");
                var dropzoneData = dropzone.data("plugin-id");

                // check plugin if wrap
				if( $(pluginContainer).length <= 0 ) {
                    $(plugin).data("plugin-container", "");
				}
				
                melisPluginEdition.pluginContainerChecker();
				melisPluginEdition.sendDragnDropList(dropzoneData, pageId);
            }
    	},
    });

	$("body").on("click", ".m-options-handle", function() {
		window.fromdragdropzone = $(this).closest(".melis-plugin-tools-box").data("melis-fromdragdropzone");
		// Need a callback to load showModalSlider
		setTimeout(function(){ checkedModalSlider(); }, 2000);

	});

	var modalUl;
	var maxLiWidth = 0;
	var ulPos;
	var modalContainerWidth;
	var ulEndPoint = false;


	var maxPosPrev = 25;
	var maxPosNext;

	function checkedModalSlider() {
		modalUl = window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs");
	    var modalNavContainer = 0;
	    modalContainerWidth = window.parent.$("#id_meliscms_plugin_modal").outerWidth();

	    var minSize = 150;
	    var maxSize = 225;
	    var modalTabs = window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs li");
	    var tabLength = modalTabs.length;
	    var tabSize = tabLength * maxSize;
	    maxPosNext = tabSize / tabLength;

	    window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs li").each(function() {
	        modalNavContainer += $(this).outerWidth();
	        var el = $(this).outerWidth();
	        maxLiWidth = Math.max(maxLiWidth, el);
	    });
	    if(modalNavContainer > modalContainerWidth) {
	    	modalTabs.width(minSize);
			window.parent. $("body #id_meliscms_plugin_modal .melis-whead-box .nav.nav-tabs").width(tabSize);
			window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box").width(modalContainerWidth - 50);
			window.parent.$("body .widget-melis-tabprev").addClass("active");
			window.parent.$("body .widget-melis-tabnext").addClass("active");

	    } else {
			window.parent.$("body .widget-melis-tabprev, .widget-melis-tabnext").removeClass("active");
			window.parent.$("body #id_meliscms_plugin_modal .melis-whead-box").width(modalContainerWidth);
	    }

	}

	function modalSlideNext() {
    	if(modalUl) {
 	    	var posLeft = Math.abs(modalUl.position().left);
	    	if(posLeft >= 25 && posLeft <= maxPosNext + 50) {
	    		modalUl.finish().animate({
		    		'left': '-=150',
		    	}, 300);
	    	}
    	}
	}

	function modalSlidePrev() {
    	if(modalUl) {
	    	var posLeft = Math.abs(modalUl.position().left);
	    	if(posLeft >= 125) {
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


	$(window.parent).resize(function () {
	    checkedModalSlider();
	});

    return {
    	checkedModalSlider 			: 		checkedModalSlider
    }
 })(jQuery, window);