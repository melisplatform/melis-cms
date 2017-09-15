 var melisDragnDrop = (function($, window) {

    // cache DOM
/*    var currentFrame,
        dndHeight,
        stickyHead;*/

    var scrollBool = true;
    var centerWindow;
    var scrollH = window.parent.$("body")[0].scrollHeight;
    
    /* ==================================
            Binding Events
    ====================================*/
    $("body").on("click", "#melisPluginBtn", showPluginMenu);
    $("body").on("click", ".melis-cms-filter-btn", showPlugLists);
    $("body").on("click", ".melis-cms-category-btn", showCatPlugLists);
    /* ==================================
            Drag & Drop
    ====================================*/

    $( ".melis-cms-plugin-snippets" ).draggable({
        start: function(event, ui) {             
            $(ui.helper).find('.melis-plugin-tooltip').hide();            
        }, 
        connectWith: ".melis-draggable",
        connectToSortable: ".melis-dragdropzone",
        revert: true, 
        helper: "clone",
    });

    $(".melis-dragdropzone").sortable({
        connectWith: ".melis-draggable, .melis-dragdropzone .melis-float-plugins",
        handle: ".m-move-handle",
        forcePlaceholderSize: false,
        cursor: "move",
        cursorAt: { top: 0, left: 0 },
        zIndex: 999999,
        placeholder: "ui-state-highlight",
        tolerance: "pointer",
        start: function(event, ui) {
            $(".melis-dragdropzone").sortable("refresh");
            // hide tinyMCE panel
            $(".mce-tinymce.mce-panel.mce-floatpanel").hide();

            $(window).mousemove(function (e) {
                var top;
                var frameTop = window.parent.$("#"+parent.activeTabId).find(".melis-iframe").offset().top + 10;

                if(window.parent.$(".sticky-pageactions")) {
                    top = $(window.parent).scrollTop() - 130;      
                } else {
                    top = 0;
                }
                
                // check if there is a plugin being drag
                if ($('.ui-sortable-helper') && $('.ui-sortable-helper').length > 0) {
                    var bottom = $(window.parent).height();

                    // hide plugin panel when dragging a plugin
                    if($(".melis-cms-dnd-box").hasClass("show")) {
                        $(".melis-cms-dnd-box").removeClass("show");
                    }

                    
                    if (e.clientY >= ($(window.parent).scrollTop() + $(window.parent).height() - frameTop) ) {
                        // detect IE8 and above, and edge
                        if (document.documentMode || /Edge/.test(navigator.userAgent)) {
                            // activate scrollTop on IE
                            window.parent.$('html').css({'overflow': 'auto', 'height': 'auto'});
                        }

                        window.parent.$('html, body').animate({
                            scrollTop: $(window.parent).scrollTop() + ($(window.parent).height() / 2)
                        }, 300);
                    }
                    else if (e.clientY <= top && $(window.parent).scrollTop() > 0) {
                        // detect IE8 and above, and edge
                        if (document.documentMode || /Edge/.test(navigator.userAgent)) {
                            // activate scrollTop on IE
                            window.parent.$('html').css({'overflow': 'auto', 'height': 'auto'});
                        }

                        window.parent.$('html, body').animate({
                            scrollTop: $(window.parent).scrollTop() - ($(window.parent).height() / 2)
                        }, 300);

                    } else {
                        window.parent.$('html, body').stop();
                    }
                } else {
                    // detect IE8 and above, and edge
                    if (document.documentMode || /Edge/.test(navigator.userAgent)) {
                        // activate scrollTop on IE
                        window.parent.$('html').css({'overflow': 'hidden', 'height': '100%'});
                    }
                }
            });
        },
        receive: function( event, ui ) {

            if(ui.helper) { 
                var moduleName = $(ui.helper[0]).data("module-name");
                var pluginName = $(ui.helper[0]).data("plugin-name");
                var siteModule = $(ui.helper[0]).data("plugin-site-module");
                // get id of current dragzone 
                var dropzone = $(event.target).data("dragdropzone-id");
                var tabId = window.parent.$("#"+parent.activeTabId).find(".melis-iframe").data("iframe-id");

                var dataKeysfromdragdropzone = $(ui.helper[0]).data("melis-fromdragdropzone");
                var dropLocation = ui.helper[0];
                // remove Clone
                // ui.helper[0].remove();
                setTimeout(function() {
                    if(moduleName !== undefined) {
                        requestPlugin(moduleName, pluginName, dropzone, tabId, dropLocation, siteModule);
                    }

                }, 300);
            }

            if(ui.sender[0]) {
                var dragZoneSender = ui.sender[0];
                var dragZoneSenderPluginId = $(dragZoneSender).data("plugin-id");
                // send dragndrop list
                melisPluginEdition.sendDragnDropList(dragZoneSenderPluginId, tabId)
            }
        },
        
        update: function( event, ui ) {
            $(".ui-sortable-helper").remove();
        }

    });

    // Tooltip
    $(".melis-cms-plugin-snippets").tooltip({
    	position: {
	        my: "left center",
	        at: "left+110% center",
	        using: function( position, feedback ) {
	        	$( this ).css( position );
	        	$(this)
	            	.addClass( "melis-plugin-tooltip" )
	            	.addClass( feedback.vertical )
	            	.addClass( feedback.horizontal )
	            	.appendTo( this );
	        }
      	},
    });

    $(".melis-cms-plugin-snippets").hover(function() {
        $(this).children(".melis-plugin-tooltip").fadeIn();
    });

    // $( ".melis-editable" ).resizable({ disabled: true, handles: 'e' });

    function requestPlugin(module, plugin, dropzone, pageId, dropLocation, siteModule) {

        // locate plugin location
        var layout = $('div[data-dragdropzone-id='+ dropzone +']');

        // add the temp loader
        var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
        $(layout).addClass("melis-loader").prepend(tempLoader);

        $.ajax({
            type: 'GET',
            url: "/melispluginrenderer?module="+module+"&pluginName="+plugin+"&pageId="+pageId+"&fromDragDropZone=1&melisSite="+siteModule,
            success: function(plugins) {
                var loadedPlug = false,
                    $link = $("link"),
                    $script = $("script"),
                    el,
                    elAttribute;
                // hide the loader
                $('.loader-icon').removeClass('spinning-cog').addClass('shrinking-cog');
                
                if(plugins.success) {
                	
                    var elType;
                    var dataPluginID;
                    var idPlugin;
                    var jsUrl;
                    
                    // iterate object
                	plugin = plugins.datas;
                	
                    // get the html
                    var vhtml = plugin.html;
                    var melisIdPage = window.parent.$("#"+ parent.activeTabId).find('iframe').data('iframe-id');
                    var pluginToolBox = $(vhtml).find(".melis-plugin-tools-box");
                    var pluginHardCodedConfigEl = $(vhtml).find(".plugin-hardcoded-conf");
                    
                    // extract the data keys
                    var melisPluginModuleName = typeof pluginToolBox.data("module") != "undefined" ? pluginToolBox.data("module") : '';
                    var melisPluginName = typeof pluginToolBox.data("plugin") != "undefined" ? pluginToolBox.data("plugin") : '';
                    var melisPluginID = typeof pluginToolBox.data("plugin-id") != "undefined" ? pluginToolBox.data("plugin-id") : '';
                    var melisPluginTag = typeof pluginToolBox.data("melis-tag") != "undefined" ? pluginToolBox.data("melis-tag") : '';
                    var melisSiteModule = typeof pluginToolBox.data("site-module");
                    var melisPluginHardCodedConfig = $.trim(pluginHardCodedConfigEl.text());

                    // dataPluginID = pluginToolBox.next().attr("id");
                    var pluginOutlined = pluginToolBox.closest(".melis-ui-outlined");
                    dataPluginID = pluginOutlined.find("[id*='"+melisPluginID+"']").attr("id");

                    if(typeof dataPluginID !== "undefined") {
                        // get plugin id 
                        idPlugin = dataPluginID;
                    }

                    // create array of objects
                    var datastring = [];

                    if(melisPluginModuleName && melisPluginName && melisPluginID && melisPluginHardCodedConfig != "") {
                        datastring.push({name: "melisIdPage", value: melisIdPage});
                        datastring.push({name: "melisModule", value: melisPluginModuleName});
                        datastring.push({name: "melisPluginName", value: melisPluginName});
                        datastring.push({name: "melisPluginId", value: melisPluginID});
                        datastring.push({name: "melisPluginTag", value: melisPluginTag});

                        // pass it in savePluginUpdate
                        melisPluginEdition.savePluginUpdate(datastring);
                    }

                    // adding plugin in dropzone
                    // $('div[data-dragdropzone-id='+ dropzone +']').append(plugin.html);
                    $(plugin.html).insertAfter(dropLocation);
                    
                    // Processing the plugin resources and initialization
                    melisPluginEdition.processPluginResources(plugin.init, idPlugin);
                    // Init Resizable
                    melisPluginEdition.initResizable();
                    // remove plugin
                    $(dropLocation).remove();
                    // send new plugin list
                    melisPluginEdition.sendDragnDropList(dropzone, pageId);
                    
                    $(layout).removeClass("melis-loader");
                    $("#loader").remove();
                    
                    melisPluginEdition.calcFrameHeight()
                    melisPluginEdition.disableLinks('a');
                }
            },
            error: function() {
                console.log("Something went wrong");
            }
        });
    }

    function showPluginMenu() {
        $(this).parent().toggleClass("show");
    }

    function showPlugLists() {
        if($(this).hasClass("active")) {
            $(this).removeClass("active").siblings(".melis-cms-plugin-snippets-box").slideUp();
            $(this).siblings(".melis-cms-plugin-snippets-box").find(".melis-cms-category-btn.active").removeClass("active").siblings(".melis-cms-category-plugins-box").slideUp();
        } else {
            $(".melis-cms-filter-btn.active").removeClass("active").siblings(".melis-cms-plugin-snippets-box").slideUp();
            $(this).addClass("active");
            $(".melis-cms-filter-btn.active").siblings(".melis-cms-plugin-snippets-box").slideDown(); 
        }
    }

    function showCatPlugLists() {
        if($(this).hasClass("active")) {
            $(this).removeClass("active").siblings(".melis-cms-category-plugins-box").slideUp();
        } else {
            $(".melis-cms-category-btn.active").removeClass("active").siblings(".melis-cms-category-plugins-box").slideUp();
            $(this).addClass("active");
            $(".melis-cms-category-btn.active").siblings(".melis-cms-category-plugins-box").slideDown(); 
        }
    }
    function pluginScrollPos() {
        var currentFrame = window.parent.$("#"+parent.activeTabId).find(".melis-iframe");
        var dndHeight = $(window.parent).height() - currentFrame.offset().top - 5;
        var stickyHead = window.parent.$("#"+parent.activeTabId).find(".bg-white.innerAll");
        var widgetHeight = window.parent.$("#"+parent.activeTabId).find(".widget-head.nav");

        // Chrome, Firefox etc browser
        $(window.parent).scroll(function() {
            if( (stickyHead.offset().top + stickyHead.height() + 30) >= currentFrame.offset().top ) {
                $(".melis-cms-dnd-box").css("top", stickyHead.offset().top - currentFrame.offset().top + stickyHead.height() + 30);
                dndHeight = $(window.parent).height() - stickyHead.height() - widgetHeight.height() - 15;
                $(".melis-cms-dnd-box").height(dndHeight);

            } else {
                dndHeight = $(window.parent).height() - currentFrame.offset().top - 5;
                $(".melis-cms-dnd-box").css("top", 0);
                $(".melis-cms-dnd-box").height(dndHeight);
            }

        });

        // For IE scroll giving different value
        if (window.parent) {
            window.parent.$("body").scroll(function() {
               if( (stickyHead.offset().top + stickyHead.height() + 30) >= currentFrame.offset().top ) {
                    $(".melis-cms-dnd-box").css("top", stickyHead.offset().top - currentFrame.offset().top + stickyHead.height() + 30);
                } else {
                    $(".melis-cms-dnd-box").css({"top": 0, "height": $(window.parent).height() - stickyHead.height() - widgetHeight.height() - 15});

                }
            });
        }

        $(".melis-cms-dnd-box").height(dndHeight);

    }

    pluginScrollPos();

    return {
        requestPlugin           :       requestPlugin,
        showPluginMenu          :       showPluginMenu,
        pluginScrollPos         :       pluginScrollPos,
    }

 })(jQuery, window);

