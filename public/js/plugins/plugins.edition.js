var melisPluginEdition = (function($, window) {

    /* ==================================
            Cache DOM
    ====================================*/
    // body tag overall
    var $body                   = window.parent.$("body"),
        fromdragdropzone        = window.fromdragdropzone,
        iframe                  = window.parent.$("#"+parent.activeTabId).find(".melis-iframe"),
        // inside iframe's body tag
        $_body                  = $("body"),
        pluginHardcodedConfig;

    /* ==================================
            Binding Events
    ====================================*/
    $_body.on("click", ".m-options-handle", createPluginModal);

    $_body.on("click", ".m-trash-handle", removePlugins);

    
    /* 
     * Checking parent body events handler to avoid multiple events of the button
     * $body.data("events").click,
     * In jQuery 1.8, it is no longer possible to access the element's events using .data('events') http://bugs.jquery.com/ticket/10589.
     */
    $.each( $._data($body.get(0), "events"), function( i, val ) {
        try {
            if (val.selector == "#pluginModalBtnApply") {
                $body.off("click", "#pluginModalBtnApply");
            }
        } catch(error) {}
    });

    $body.on("click", "button[id='pluginModalBtnApply'][data-page-id='"+melisActivePageId+"']", submitPluginForms); // $body because it is modal and it's located in parent

    $_body.on("focus", ".melis-ui-outlined .melis-editable", function() {
        $(this).closest(".melis-ui-outlined").addClass("melis-focus");
    });

    $_body.on("blur", ".melis-ui-outlined.melis-focus", function() {
        $(this).removeClass("melis-focus");
    });

    // run when .melis-ui-outlined loses focus and run getPluginData() to include the width related data
    //$_body.on("blur", ".melis-ui-outlined", melisUiOutlinedLosesFocus);

    // $("body").on("dblclick", ".ui-resizable-e", changeWidth); // disable for now

    function melisUiOutlinedLosesFocus() {
        var $this = $(this);

    }

    // Submit form in modal
    function submitPluginForms(e) {
        e.preventDefault();

        // Assign in function for bug issue in closing and opening tabs
        var $this               = $(this),
            melisIdPage         = $this.closest("#id_meliscms_plugin_modal_container").data("melis-id-page"),
            melisPluginModule   = $this.closest("#id_meliscms_plugin_modal_container").data("melis-plugin-module"),
            melisPluginName     = $this.closest("#id_meliscms_plugin_modal_container").data("melis-plugin-name"),
            melisPluginId       = $this.closest("#id_meliscms_plugin_modal_container").data("melis-plugin-id"),
            melisPluginTag      = $this.closest("#id_meliscms_plugin_modal_container").data("melis-plugin-tag"),
            melisSiteModule     = $this.closest("#id_meliscms_plugin_modal_container").data("melis-site-module"),
            dataString          = $this.closest('.modal-content').find("form");

            pluginHardcodedConfig = $.trim($this.closest("#id_meliscms_plugin_modal_container").find(".plugin-hardcoded-conf").text());

        // Construct data string
        var datastring = dataString.serializeArray();
            datastring.push({name: "melisIdPage", value: melisIdPage});
            datastring.push({name: "melisModule", value: melisPluginModule});
            datastring.push({name: "melisPluginName", value: melisPluginName});
            datastring.push({name: "melisPluginId", value: melisPluginId});
            datastring.push({name: "melisPluginTag", value: melisPluginTag});

            try {
                validateModal(melisIdPage, melisPluginModule, melisPluginName, melisPluginId, melisPluginTag, datastring, melisSiteModule);
            }
            catch (e) {
               console.log(e);
            }
    }

    // Validate form in modal
    function validateModal(melisIdPage, melisPluginModule, melisPluginName, melisPluginId, melisPluginTag, datastring, siteModule) {
       $.ajax({
            type: 'POST',
            url: "/melis/MelisCms/FrontPlugins/validatePluginModal?validate&melisSite="+siteModule,
            data: datastring,
            dataType: 'json'
        }).done(function(data) {
            if(data.success) {
                savePluginUpdate(datastring, siteModule);

                // Plugin inside Drag and Drop zone
                if ($("div[data-plugin-id='"+ melisPluginId +"']").parents(".melis-dragdropzone").length) {
                    window.fromdragdropzone = 1;
                }

                setTimeout(function() {
                    pluginRenderer(melisPluginModule, melisPluginName, melisIdPage, melisPluginId, false, window.fromdragdropzone, siteModule);
                    setTimeout(function(){
                        checkToolSize();
                    }, 300);
                    window.parent.$("#id_meliscms_plugin_modal_container").modal('hide');

                }, 300);
            } else {
                melisCmsFormHelper.melisMultiKoNotification(data.errors);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            alert( translations.tr_meliscore_error_message );
        });
    }

    // commented disrupt slider in mozilla
    // setTimeout(function(){ checkToolSize(); }, 300);
    function checkToolSize() {
        $(".melis-ui-outlined").each(function() {
            var $this       = $(this),
                parentSize  = $this.outerWidth(),
                totalChild  = $this.find($(".melis-plugin-tools-box")).outerWidth() + $this.find($(".melis-plugin-title-box")).outerWidth();

            if ( totalChild > parentSize ) {
                $this.width(totalChild + 30);
            }
        });
    }

    // jQuery last dragdropzone for crossbrowser
    $("div.melis-dragdropzone").last().css({"margin-bottom": "35px"});

    // Saving Plugin
    function savePluginUpdate(data, siteModule){
        $.ajax({
            type: "POST",
            url: "/melis/MelisCms/PageEdition/savePageSessionPlugin?idPage=" + melisActivePageId + "&melisSite="+siteModule,
            data: data,
            dataType: 'json'
        }).done(function(response) {
            if(!response.success) {
                window.parent.melisHelper.melisKoNotification(response.textTitle, response.textMessage, []);
            }
        });
    }

    // Render Plugin After Saving
    function pluginRenderer(module, plugin, pageId, pluginId, encapsulatedPlugin, fromdragdropzone, siteModule) {
        // declaring parameters variable for old / cross browser compatability
        if(typeof encapsulatedPlugin === "undefined") encapsulatedPlugin = true;
        if(typeof fromdragdropzone === "undefined") fromdragdropzone = '';

        // locate plugin location
        var layout          = $('[data-plugin-id="'+ pluginId +'"]').closest(".melis-ui-outlined"),
            layoutParent    = layout.parent(),
            layoutId        = '#'+layout.attr('id');

        // add the temp loader
        var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
            $(layout).addClass("melis-loader").prepend(tempLoader);

        var layoutHeight = $(layout).outerHeight();
            $(layout).height(layoutHeight);

            $.ajax({
                type: 'POST',
                url: "/melispluginrenderer?module="+module+"&pluginName="+plugin+"&pageId="+pageId+"&pluginId="+pluginId+"&encapsulatedPlugin="+encapsulatedPlugin+"&fromDragDropZone="+fromdragdropzone+"&melisSite="+siteModule,
                data: {pluginHardcodedConfig : pluginHardcodedConfig},
                dataType: 'json'
            }).done(function(data) {
                setTimeout(function() {
                    if(data.success) {
                        var elType,
                            jsUrl,
                            idPlugin,
                            plugin = data.datas;

                        // remove old plugin
                        $(layoutId).children().not("#loader.overlay-loader").remove();

                        // add new plugin
                        $(layout).prepend(plugin.html);

                        // get the html
                        var pluginToolBox = $(layout).find(".melis-plugin-tools-box");
                        var melisPluginID = typeof pluginToolBox.data("plugin-id") != "undefined" ? pluginToolBox.data("plugin-id") : '';

                        // dataPluginID = pluginToolBox.next().attr("id");
                        var pluginOutlined = pluginToolBox.closest(".melis-ui-outlined");
                        dataPluginID = pluginOutlined.find("[id*='"+melisPluginID+"']").attr("id");

                        // remove plugin container width class
                        $(pluginOutlined).children('[class^=plugin-width]').removeClass();

                        if(typeof dataPluginID !== "undefined") {
                            // get plugin id
                            idPlugin = dataPluginID;
                        }

                        // Processing the plugin resources and initialization
                        processPluginResources(plugin.init, idPlugin);

                        // hide the loader
                        $(layout).removeClass("melis-loader");
                        $('.loader-icon').removeClass('spinning-cog').addClass('shrinking-cog');
                        $("#loader.overlay-loader").remove();
                        $(layout).height('auto');

                        calcFrameHeight();
                        disableLinks('a');

                        // re init resize
                        var uiOutlined = $(".melis-dragdropzone .melis-ui-outlined");
                        try {
                            uiOutlined.resizable("destroy"); // disable for now
                        }catch(e){
                            uiOutlined.resizable();
                        }

                        if (parent.pluginResizable == 1){
                            initResizable();
                        }
                    }
                }, 300);
            }).always(function() {
                // hide the loader
                $(layout).removeClass("melis-loader");
                $('.loader-icon').removeClass('spinning-cog').addClass('shrinking-cog');
                $("#loader.overlay-loader").remove();
            }).fail(function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            });
    }

    /**
     * This method will process plugin resources "js and css"
     * and plugin initialization
     */
    function processPluginResources(pluginResources, pluginId){
        // Plugins URL's handler variable
    	var jsUrl,
            pluginJs = new Array,
            pluginJsInitFunction = new Array;

            $.each(pluginResources, function(i, pluginVal) {
                // check resources css / js
                if(pluginVal.ressources) {
                    $.each(pluginVal.ressources, function(i, value) {
                        // check if css / js
                        if(i == "css") {
                            elType = "css";
                        } else if (i == "js") {
                            elType = "js";
                        }

                        // loop css and js
                        $.each(value, function(key, val) {
                            var pluginLink = val;

                            if(pluginLink.length) {
                                if(elType == "css") {
                                    checkLinkExists(pluginLink);
                                } else if(elType == "js") {
                                    jsUrl =  pluginLink;
                                    /**
                                     * Preparing js url's to append/add to the wondie body
                                     */
                                    if($.inArray(pluginLink, pluginJs) == -1){
                                    	pluginJs.push(pluginLink);
                                    }

                                    if(pluginLink.indexOf(".init") !== -1 && $.inArray(pluginLink, pluginJsInitFunction) == -1){
                                    	pluginJsInitFunction.push(pluginLink);
                                    }
                                }
                            }
                        });
                    });
                }
            });

        var loadedJs        = new Array,
            ctr             = 0,
            curUrlxmlReq    = null;

            $tmp = setInterval(function(){
            	if(pluginJs[ctr] !== 'undefined'){

            		var url = pluginJs[ctr];
            		if(ctr == pluginJs.length){

            			// Clearing the time interval to exit
                    	clearInterval($tmp);

                        calcFrameHeight();
                        disableLinks('a');

                    	$.each(pluginJsInitFunction, function(i, val){
                        	// Execution of the plugin js function initialization
                            var urlStr = val.substr(val.indexOf(".") + 1);
                            var functionName = urlStr.substr(0, urlStr.indexOf(".init")) + "_init";

                            checkFunctionExists(eval(functionName), pluginId);
                            calcFrameHeight();
                        });

                    }else{
                    	/**
                    	 * Checking if the Js url is already loaded,
                    	 * else this will try to loop and try again until the js url
                    	 * is successfully loaded
                    	 */
                    	if($.inArray(url, loadedJs) == -1){

                    		/**
                    		 * Checking if the Js url is arleady exist,
                    		 * else this will procced to the next js url
                    		 */
                    		if($(document.body).find("script[src='"+url+"']").length){
                    			ctr++;
                    		}else{

                    			if(curUrlxmlReq != url){

                    				curUrlxmlReq = url;

                        			// Creates an object which can read files from the server
                        	        var reader = new XMLHttpRequest();

                        	        // Opens the file and specifies the method (get)
                        	        // Asynchronous is true
                        	        reader.open('get', url, true);

                        	        //check each time the ready state changes
                        	        //to see if the object is ready
                        	        reader.onreadystatechange = checkReadyState;

                        	        function checkReadyState() {

                        	            if (reader.readyState === 4) {

                        	                //check to see whether request for the file failed or succeeded
                        	                if ((reader.status == 200) || (reader.status == 0)) {

                        	                	// Adding the Js element to the body
                                        		var el = document.createElement('script');
                                                el.src = url;
                                                el.type = "text/javascript";
                                                document.body.appendChild(el);
                                                // Adding the Js url to added url's handler variable
                                                loadedJs.push(url);

                                                /**
                                                 * Event of Js DOM once this successfully load to the body
                                                 */
                                                $(document.body).find("script[src='"+url+"']").on("load", function(){
                                                	/**
                                                	 * For debugging
                                                	 * To determine the time JS finaly loaded
                                                	 *
                                                	 * var d = new Date();
                                                	 * console.log(url+" : "+d.getMinutes()+":"+d.getSeconds()+":"+d.getMilliseconds());
                                                	 */

                                    	        	/**
                                    	        	 * Inceamenting Counter variable in-order to process
                                    	        	 * the next js
                                    	        	 */
                                    	        	ctr++;
                                    	        });

                        	                }else{
                        	                	console.log("Javascript Url \""+url+"\" does not exist, please make sure javascript url is accessible");
                        	                	// Clearing the time interval to exit
                                        		clearInterval($tmp);
                        	                }
                        	            }//end of if (reader.readyState === 4)
                        	        }// end of checkReadyState()

                        	        // Sends the request for the file data to the server
                        	        // Use null for "get" mode
                        	        reader.send(null);
                    			}
                    		}
                    	}
                    }
            	}else{
            		// Clearing the time interval to exit
            		clearInterval($tmp);
            	}
            }, 500);
    }

    // Send the list of plugin inside DragnDropzone
    function sendDragnDropList(dropzone, pageId) {
        var $dropzone               = $('div[data-dragdropzone-id='+ dropzone +']'),
            dragdropzoneModule      = $dropzone.data("module"),
            dragdropzonePlugin      = $dropzone.data("plugin"),
            dragdropzonePluginId    = $dropzone.data("plugin-id"),
            dragdropzoneMelisTag    = $dropzone.data("melis-tag"),
            siteModule              = $dropzone.data("site-module"),
            pluginListEl            = $dropzone.children(".melis-ui-outlined"),
            dragzone                = [],
            pluginList              = new Object();

            pluginList['melisIdPage']                   = pageId;
            pluginList['melisModule']                   = dragdropzoneModule;
            pluginList['melisPluginName']               = dragdropzonePlugin;
            pluginList['melisPluginId']                 = dragdropzonePluginId;
            pluginList['melisPluginTag']                = dragdropzoneMelisTag;
            pluginList['melisDragDropZoneListPlugin']   = new Object();

            // loop all plugins in dropzone
            $.each(pluginListEl, function(key, value) {

                pluginList['melisDragDropZoneListPlugin'][key] = new Object();

                var plugins                 = $(value).find(".melis-plugin-tools-box"),
                    melisPluginModuleName   = $(plugins).data("module"),
                    melisPluginName         = $(plugins).data("plugin"),
                    melisPluginID           = $(plugins).data("plugin-id"),
                    melisPluginTag          = $(plugins).data("melis-tag"),
                    melisPluginContainer    = $(plugins).data("plugin-container"),
                    melisPluginContainerId  = $(pluginListEl).attr("id");

                    pluginList['melisDragDropZoneListPlugin'][key]['melisModule']       = melisPluginModuleName;
                    pluginList['melisDragDropZoneListPlugin'][key]['melisPluginName']   = melisPluginName;
                    pluginList['melisDragDropZoneListPlugin'][key]['melisPluginId']     = melisPluginID;
                    pluginList['melisDragDropZoneListPlugin'][key]['melisPluginTag']    = melisPluginTag;

                    if ( $("#"+melisPluginContainerId).length ) {
                        pluginList['melisDragDropZoneListPlugin'][key]['melisPluginContainer'] = melisPluginContainer;
                    } else {
                        pluginList['melisDragDropZoneListPlugin'][key]['melisPluginContainer'] = " ";
                    }
            });

            if (typeof siteModule !== "undefined"){
                savePluginUpdate(pluginList, siteModule);
            }
    }

    function checkFunctionExists(functionName, idPlugin) {
        if (typeof functionName === 'function') {
    		functionName(idPlugin);
        } else {
            console.log('not a function');
        }
    }

    function checkLinkExists(url) {
        var link        = $("link"),
            generate    = true;

            $.each(link, function(i, val){
                if ( $(val).attr("href") == url ) {
                    generate = false;
                }
            });

            if ( generate ) {
                generateLink(url);
            }
    }

    function checkScriptExists(url) {
        var script      = $("script"),
            generate    = true;

            $.each(script, function(i, val){
                if ( $(val).attr("src") == url ) {
                    generate = false;
                }
            });

            if ( generate ) {
                generateScript(url);
            }
    }

    function generateLink(url) {
        var el          = document.createElement('link');

            el.href     = url;
            el.rel      = "stylesheet";
            el.media    = "screen";
            el.type     = "text/css";

            document.head.appendChild(el);
    }

    function generateScript(url) {
        var el      = document.createElement('script');

            el.src  = url;
            el.type = "text/javascript";

            document.body.appendChild(el);
    }

    function removePlugins() {
        var $this                   = $(this),
            pluginContainer         = $this.closest(".melis-ui-outlined"),
            dragndropContainer      = pluginContainer.closest(".melis-dragdropzone"),
            dropzone                = dragndropContainer.data("plugin-id"),
            pluginsToolsBox         = pluginContainer.find('.melis-plugin-tools-box'),
            // plugin tools data keys
            melisPluginModuleName   = pluginsToolsBox.data("module"),
            melisPluginName         = pluginsToolsBox.data("plugin"),
            melisPluginID           = pluginsToolsBox.data("plugin-id"),
            melisPluginTag          = pluginsToolsBox.data("melis-tag"),
            melisIdPage             = window.parent.$("#"+parent.activeTabId).find(".melis-iframe").data("iframe-id");

            $.ajax({
                type: 'GET',
                url: "/melis/MelisCms/PageEdition/removePageSessionPlugin?module="+ melisPluginModuleName+"&pluginName="+ melisPluginName +"&pageId="+ melisIdPage +"&pluginId="+ melisPluginID +"&pluginTag="+ melisPluginTag
            }).done(function(data) {
                if(data.success) {
                    pluginContainer.remove();
                    calcFrameHeight();
                    sendDragnDropList(dropzone, melisIdPage);
                    pluginContainerChecker();
                    pluginDetector();
                } else {
                    melisCmsFormHelper.melisMultiKoNotification(data.errors);
                }
            }).fail(function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            });
    }

    function pluginDetector() {
        $(".melis-dragdropzone").each(function() {
            var $this           = $(this),
                checkContent    = $this.find(".melis-ui-outlined");

                if ( $(checkContent).length ) {
                    $this.removeClass("no-content");
                } else {
                    $this.addClass("no-content");
                }
        });
    }

    function pluginContainerChecker() {
        $(".melis-float-plugins").each(function() {
            var $this       = $(this),
                pluginLists = $this.children(".melis-ui-outlined");
                
                if( $(pluginLists).length <= 0 ) {
                    $this.remove();
                }
        });
    }

    function calcFrameHeight() {
        // recalculate frame height
        var //frameHeight = window.parent.$("#"+ parent.activeTabId).find(".melis-iframe").contents().find("body").height(),
            frameHeight = document.body.scrollHeight,
            frame       = window.parent.$("#"+ parent.activeTabId).find(".melis-iframe");

            frame.height(frameHeight);

            /*
             * Added iframe.length for fixing issue: Uncaught TypeError: Cannot read property 'calcFrameHeight' of undefined
             * Added by: Junry 22/05/2019
             */
            /* if ( frame.length ) {
                frame.height(frameHeight);
            } */
    }

    function disableLinks(e) {
        $(e).click(function(event) { event.preventDefault(); });
    }

    function createPluginModal() {
        var $this               = $(this),
            toolBox             = $this.closest(".melis-plugin-tools-box"),
            pluginContainer     = toolBox.parent(".melis-ui-outlined"),
            pluginFrontConfig   = $.trim(pluginContainer.find(".plugin-hardcoded-conf").text()),
            module              = toolBox.data("module"),
            pluginName          = toolBox.data("plugin"),
            pluginId            = toolBox.data("plugin-id"),
            siteModule          = toolBox.data("site-module"),
            optionHandleT       = $this.offset().top;

            // initialation of local variable
            zoneId = 'id_meliscms_plugin_modal_interface';
            melisKey = 'meliscms_plugin_modal_interface';
            modalUrl = '/melis/MelisCms/FrontPlugins/renderPluginModal?melisSite='+siteModule;

            var modalParams = {
                pluginFrontConfig : pluginFrontConfig,
                module: module,
                pluginName: pluginName,
                pluginId : pluginId,
                melisActivePageId : melisActivePageId,
                siteModule : siteModule
            };

            // requesitng to create modal and display after
            window.parent.melisHelper.createModal(zoneId, melisKey, false, modalParams, modalUrl, function() {
                var $pageIdRoot = window.parent.$("#pageIdRootMenu");

                    // Check if it is in IE scrollTop when open modal
                    if (document.documentMode || /Edge/.test(navigator.userAgent)) {
                        window.parent.$('html').css({'height': 'auto'});
                        window.parent.$('body').css('overflow', 'hidden');
                        setTimeout(function() {
                            window.parent.$('html, body').scrollTop(optionHandleT);
                        }, 300);
                    }

                    setTimeout(function() {
                        if ( $pageIdRoot.length ) {
                            $pageIdRoot.parents(".form-group").find("label").addClass("d-flex flex-row justify-content-between");
                        }
                    }, 0);
                    
                    setTimeout(function() {
                        melisPluginSortable.checkedModalSlider();
                    }, 300);
            });
    }

    //  Resize Plugin
    function initResizable() {
        var totalWidth, parentWidth,
            percentTotalWidth,
            iframe = window.parent.$("#"+ parent.activeTabId).find('iframe');

            // check if melis-ui-outlined element is available in preview page
            if( $(".melis-dragdropzone .melis-ui-outlined").length ) {
                $(".melis-dragdropzone .melis-ui-outlined").resizable({
                    containment: ".melis-dragdropzone",
                    start: function(event, ui){
                        var widthIndicator =  '<div class="ui-resize-indicator"></div>';
                        parentWidth = ui.originalElement.parent().outerWidth();
                        // width indicator
                        $(ui.originalElement).append(widthIndicator);

                        if($(ui.originalElement).find(".ui-resize-input")) {
                            $(ui.originalElement).find(".ui-resize-input").remove();
                        }
                    },
                    resize: function(event, ui) {
                        totalWidth = ui.size.width;
                        ui.originalElement.css('height', 'auto');
                        // convert px to percent
                        percentTotalWidth = (100 * totalWidth / parentWidth);
                        percentTotalWidth = percentTotalWidth.toFixed(2);
                        ui.originalElement.css('width', percentTotalWidth + '%');
                        $(ui.originalElement).find(".ui-resize-indicator").text(percentTotalWidth + " %");
                    },
                    stop: function(event, ui) {
                        // get all data attributes
                        var toolBox = $(ui.originalElement).children(".melis-plugin-tools-box");

                        getPluginData(toolBox, percentTotalWidth);
                        // get the function
                        var owlCheck = $(ui.originalElement).find(".owl-carousel");
                        if( $(owlCheck).length ) {
                            $(owlCheck).trigger('refresh.owl.carousel');
                        }

                        // remove indicator
                        $(ui.originalElement).find(".ui-resize-indicator").remove();
                    }
                });
            }
    }

    function getPluginData(el, percentTotalWidth) {
        var toolBox         = el,
            mobileWidth, tabletWidth, desktopWidth, currentClass, newClass,
            iframe          = window.parent.$("#"+ parent.activeTabId).find('iframe'),
            parentOutlined  = $(toolBox).closest(".melis-ui-outlined"),
            classes         = parentOutlined.attr("class").split(" "),
            editable        = parentOutlined.find(".melis-editable");

        if ( toolBox ) {
            var pluginList = new Object();
                // get data first load
                $( toolBox ).map(function() {
                    var $this = $(this);

                        pluginList['melisIdPage']       = window.parent.$("#"+parent.activeTabId).find(".melis-iframe").data("iframe-id");
                        pluginList['melisModule']       = $this.data("module");
                        pluginList['melisPluginName']   = $this.data("plugin");
                        pluginList['melisPluginId']     = $this.data("plugin-id");
                        pluginList['melisPluginTag']    = $this.data("melis-tag");
                        mobileWidth                     = $this.attr("data-plugin-width-mobile");
                        tabletWidth                     = $this.attr("data-plugin-width-tablet");
                        desktopWidth                    = $this.attr("data-plugin-width-desktop");
                });

                // custom action check if plugin tags
                if( $(editable).length ) {
                    // trigger focus to saveSession
                    var data = $(editable).data();
                        $(editable).focus().removeClass("mce-edit-focus");

                    // hide tinymce option while resizing
                    var inst = tinyMCE.EditorManager.get(data.pluginId);
                        inst.fire("blur");
                        iframe.blur();

                        $(editable).map(function() {
                            var $this = $(this);

                                pluginList['tagType']   =   $this.data("tag-type");
                                pluginList['tagId']     =   $this.data("tag-id");
                                pluginList['tagValue']  =   tinyMCE.activeEditor.getContent({format : 'html'});
                        });
                }

                // check if resize in mobile
                if(iframe.width() <= 480) {
                    mobileWidth  = percentTotalWidth;
                    // update DOM data attribute
                    $(toolBox).attr("data-plugin-width-mobile", mobileWidth);
                    currentClass = "plugin-width-xs-";

                    var strPercentTotalWidth = percentTotalWidth;
                     // newClass = "plugin-width-xs-"+Math.floor(percentTotalWidth); // removed when css is ready
                    newClass = "plugin-width-xs-"+strPercentTotalWidth.replace(".", "-"); // removed when css is ready
                    $.each(classes, function(key, value) {
                        if( value.indexOf(currentClass) != -1 ) {
                            parentOutlined.removeClass(value).addClass(newClass);
                        }
                    });
                }
                // check if resize in tablet
                if(iframe.width() > 490 && iframe.width() <= 980) {
                    tabletWidth = percentTotalWidth;
                    $(toolBox).attr("data-plugin-width-tablet", tabletWidth);
                    currentClass = "plugin-width-md-";
                    var strPercentTotalWidth = percentTotalWidth;
                    // newClass = "plugin-width-md-"+Math.floor(percentTotalWidth); // removed when css is ready
                    newClass = "plugin-width-lg-"+strPercentTotalWidth.replace(".", "-"); // removed when css is ready
                    $.each(classes, function(key, value) {
                        if( value.indexOf(currentClass) != -1 ) {
                            parentOutlined.removeClass(value).addClass(newClass);
                        }
                    });
                }
                // check if resize in desktop
                if(iframe.width() >= 981) {
                    desktopWidth = percentTotalWidth;
                    $(toolBox).attr("data-plugin-width-desktop", desktopWidth);
                    currentClass = "plugin-width-lg-";
                    var strPercentTotalWidth = percentTotalWidth;
                    // newClass = "plugin-width-lg-"+Math.floor(percentTotalWidth); // removed when css is ready
                    newClass = "plugin-width-lg-" + strPercentTotalWidth.replace(".", "-"); // removed when css is ready
                    $.each(classes, function(key, value) {
                        if( value.indexOf(currentClass) != -1 ) {
                            parentOutlined.removeClass(value).addClass(newClass);
                        }
                    });
                }

                // set data attribute for width
                pluginList['melisPluginMobileWidth'] = mobileWidth;
                pluginList['melisPluginTabletWidth'] = tabletWidth;
                pluginList['melisPluginDesktopWidth'] = desktopWidth;
                pluginList['resize'] = true;
                // pass is to savePageSession
                savePluginUpdate(pluginList, toolBox.data("site-module"));

                // get plugin ID and re init
                // check if owl re init
                var owlCheck = $(parentOutlined).find(".owl-carousel");
                    if( $(owlCheck).length ) {
                        // setTimeout to re init, conflict with transition need to timeout
                        setTimeout(function() {
                            $(owlCheck).trigger('refresh.owl.carousel');
                        }, 500);
                    }
        }
    }

    // get plugin container id
    function getPluginContainerId() {
        var pluginId = "";
            $.ajax({
                type: 'GET',
                async: false,
                url: "/melis/MelisCms/PageEdition/getContainerUniqueId"
            }).done(function(data) {
                pluginId = data.id;
            }).fail(function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            });

            return pluginId;
    }

    // on load iframe remove child plugin class responsive
    function moveResponsiveClass() {
        $(".melis-dragdropzone .melis-ui-outlined").map(function() {
            var $this           = $(this),
                pluginClasses,
                melisTag        = $this.find(".melis-editable");

                if( $(melisTag).length ) {
                    var data            = $(melisTag).data(),
                        pluginContainer = $('div[data-melis-plugin-tag-id="'+ data.pluginId +'"]');

                        // remove div
                        $(pluginContainer).contents().unwrap();
                        $this.addClass($(pluginContainer).attr("class"));
                }

                $this.children('[class^=plugin-width]').removeClass(function(index, classes) {
                    var matches = classes.match(/\bplugin-width\S+/ig);
                        pluginClasses = classes;
                        return (matches) ? matches.join(' ') : '';
                });

                $this.addClass(pluginClasses);

                var owlCheck = $this.find(".owl-carousel");
                    if( $(owlCheck).length ) {
                        $(owlCheck).trigger('refresh.owl.carousel');
                    }
        });
    }

    // remove inline width when changing viewport
    // window.parent.$("#"+ parent.activeTabId).find('iframe').on("resize", function() {
    //     $(this).contents().find(".melis-dragdropzone .melis-ui-outlined").each(function() {
    //         $(this).css("width", "");
    //     });
    // });

    // init resize
    if ( parent.pluginResizable == 1 ) {
        initResizable(); // disable for now
    }

    moveResponsiveClass();
    pluginDetector();

    $(window).on("load", function() {
        calcFrameHeight();
    });
    
    return {
        submitPluginForms       :       submitPluginForms,
        pluginRenderer          :       pluginRenderer,
        checkFunctionExists     :       checkFunctionExists,
        checkLinkExists         :       checkLinkExists,
        checkScriptExists       :       checkScriptExists,
        calcFrameHeight         :       calcFrameHeight,
        savePluginUpdate        :       savePluginUpdate,
        sendDragnDropList       :       sendDragnDropList,
        removePlugins           :       removePlugins,
        checkToolSize           :       checkToolSize,
        disableLinks            :       disableLinks,
        processPluginResources  :       processPluginResources,
        pluginDetector          :       pluginDetector,
        initResizable           :       initResizable,
        moveResponsiveClass     :       moveResponsiveClass,
        pluginContainerChecker  :       pluginContainerChecker, // disable for now
    }

})(jQuery, window);

var melisCmsFormHelper = (function($, window) {
    var $body = window.parent.$("body");
        /**
         * KO NOTIFICATION for Multiple Form
         */
        function melisMultiKoNotification(errors, closeByButtonOnly) {
            if (!closeByButtonOnly) closeByButtonOnly = true;

            var closeByButtonOnly   = ( closeByButtonOnly !== true ) ?  'overlay-hideonclick' : '',
                errorTexts          = '<div class="row">';

                // remove red color for correctly inputted fields
                $body.find("#id_meliscms_plugin_modal .form-group label").css("color", "inherit");

                $.each(errors, function(idx, errorData) {
                    if ( errorData['success'] === false ) {
                        errorTexts += '<h3>'+ (errorData['name']) +'</h3>';
                        if (errorData['message'] != "") {
                            errorTexts +='<h4>'+ (errorData['message']) +'</h4>';
                        }
 
                        // Highlighting errors fields
                        highlightMultiErrors(errorData['success'], errorData['errors']);

                        $.each( errorData['errors'], function( key, error ) {
                            if ( key !== 'label' ) {
                                errorTexts += '<div class="col-xs-12 col-sm-5">';
                                errorTexts += '  <b>'+ (( error['label'] == undefined ) ? ((error['label']== undefined) ? key : errors['label'] ) : error['label'] ) +'</b>';
                                errorTexts += '</div>';
                                errorTexts += '<div class="col-xs-12 col-sm-7">';
                                errorTexts += ' <div class="modal-error-container">';
                                // catch error level of object
                                try {
                                    $.each( error, function( key, value ) {
                                        if(key !== 'label' && key !== 'form'){
                                            $errMsg = '';
                                            if(value instanceof Object){
                                                $errMsg = value[0];
                                            }else{
                                                $errMsg = value;
                                            }
                                            if($errMsg != '') {
                                                errorTexts += '<span class="tets error-list"><i class="fa fa-circle"></i>'+ $errMsg + '</span><br/>';
                                            }
                                        }
                                    });
                                } catch(e) {
                                    if(key !== 'label' && key !== 'form') {
                                        errorTexts +=  '<span class="hoy error-list"><i class="fa fa-circle"></i>'+ error + '</span>';
                                    }
                                }
                            }
                            errorTexts += '</div></div>';
                        });
                    }
                });

                errorTexts += '</div>';
                var div = '<div class="melis-modaloverlay '+ closeByButtonOnly +'"></div>';
                    div += '<div class="melis-modal-cont KOnotif page-edition-multi-ko">  <div class="modal-content error">'+ errorTexts +' <span class="btn btn-block btn-primary">' + translations.tr_meliscore_notification_modal_Close +'</span></div> </div>';

                $body.append(div);
        }

        function highlightMultiErrors(success, errors){
            // if all form fields are error color them red
            if ( !success ) {
                $.each( errors, function( key, error ) {
                    $body.find("#id_meliscms_plugin_modal .form-control[name='"+key +"']").parents(".form-group").find("label").css("color","red");
                });
            }
        }

    return {
        melisMultiKoNotification : melisMultiKoNotification
    }

})(jQuery, window);
