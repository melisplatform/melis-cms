var melisPluginEdition = (function($, window) {

    /* ==================================
            Cache DOM
    ====================================*/
    // body tag overall
    var $body                   = window.parent.$("body"), // outmost parent window
        fromdragdropzone        = window.fromdragdropzone,
        iframe                  = window.parent.$("#"+parent.activeTabId).find(".melis-iframe"),
        $_body                  = $("body"), // inside iframe's body tag
        pluginHardcodedConfig,
        editableHoverTimeout;

    /* ==================================
            Binding Events
    ====================================*/
    $_body.on("click", ".m-options-handle", createPluginModal);

    $_body.on("click", ".m-trash-handle", removePlugins);

    $_body.on("focus", ".melis-ui-outlined .melis-editable", function() {
        let $this               = $(this),
            $melisUiOutlined    = $this.closest(".melis-ui-outlined");

            $melisUiOutlined.addClass("melis-focus");
    });

    $_body.on("blur", ".melis-ui-outlined.melis-focus", function() {
        $(this).removeClass("melis-focus");
    });

    /* $_body.on("mouseover", ".melis-editable", function() {
        let $thisOver               = $(this),
            $dndLayoutWrapperOver   = $thisOver.closest(".dnd-layout-wrapper");
            $dndLayoutIndiOver      = $dndLayoutWrapperOver.find(".dnd-layout-indicator"),
            $firstColOver           = $thisOver.parents(".dnd-layout-wrapper").last().find(".row .col-12").first(),
            $zoneOver               = $firstColOver.find(".melis-dragdropzone"),
            $melisUiOutlinedOver    = $zoneOver.find(".melis-ui-outlined").first(),
            $toolsBoxOver           = $melisUiOutlinedOver.find(".melis-plugin-tools-box");

            setTimeout(() => {
                if($dndLayoutIndiOver.css("opacity") === "1") {
                    $toolsBoxOver.css("left", "12px");
                }
            }, 500);
    }); */

    /* $_body.on("mouseenter", ".melis-editable", function() {
        clearTimeout(editableHoverTimeout);

        let $editableEnter  = $(this),
            $toolsBoxEnter  = $editableEnter.closest(".melis-ui-outlined").find(".melis-plugin-tools-box");

            if($toolsBoxEnter.length) {
                $editableEnter.addClass("hovering");
                // $toolsBoxEnter.addClass("height-auto");
                $toolsBoxEnter.css("top", -$toolsBoxEnter.outerHeight());
            }
    }).on("mouseleave", ".melis-editable", function() {
        editableHoverTimeout = setTimeout(() => {
            let $editableLeave = $(this),
                $toolsBoxLeave = $editableLeave.closest(".melis-ui-outlined").find(".melis-plugin-tools-box");

                if($toolsBoxLeave.length) {
                    $editableLeave.removeClass("hovering");
                    // $toolsBoxLeave.removeClass("height-auto");
                    //$toolsBoxLeave.css("top", -40); // revert to default top value
                }
        }, 10); // delay for a probably prevents instant flicker
    }); */

    /* $_body.on("mouseenter", ".melis-plugin-tools-box", function() {
        let $toolsBoxHeightAutoEnter = $(this),
            height = $toolsBoxHeightAutoEnter.outerHeight();

            if ($toolsBoxHeightAutoEnter.length) {
                if (!$toolsBoxHeightAutoEnter.hasClass("height-auto")) {
                    $toolsBoxHeightAutoEnter.addClass("height-auto");
                }
                
                $toolsBoxHeightAutoEnter.css("top", -height);
            }
    }).on("mouseleave", ".melis-plugin-tools-box", function() {
        let $toolsBoxHeightAutoLeave = $(this);
            if ($toolsBoxHeightAutoLeave.length) {
                if ($toolsBoxHeightAutoLeave.hasClass("height-auto")) {
                    $toolsBoxHeightAutoLeave.removeClass("height-auto");
                }
                $toolsBoxHeightAutoLeave.css("margin-left", 0);
                //$toolsBoxHeightAutoLeave.css("top", -40); // revert to default top value
            }
    }); */
    
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
        } catch(error) {
            console.log(e);
        }
    });

    // $body because it is modal and it's located in parent
    $body.on("click", "button[id='pluginModalBtnApply'][data-page-id='"+melisActivePageId+"']", submitPluginForms); 

    $body.on("click", ".meliscms-plugin-modal-cancel-btn", function(e) {
        e.preventDefault();

        removeParentBodyPropStyle();
    });

    // window.parent.$body.prop("style")
    function removeParentBodyPropStyle() {
        setTimeout(function() {
            if ( $body.prop("style", "overflow: hidden").length ) {
                window.parent.melisCoreTool.removeOverflowHidden();
            }
        }, 500);
    }

    // run when .melis-ui-outlined loses focus and run getPluginData() to include the width related data
    //$_body.on("blur", ".melis-ui-outlined", melisUiOutlinedLosesFocus);

    // $("body").on("dblclick", ".ui-resizable-e", changeWidth); // disable for now

    /* function melisUiOutlinedLosesFocus() {
        var $this = $(this);

    } */

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

            pluginHardcodedConfig = $this.closest("#id_meliscms_plugin_modal_container").find(".plugin-hardcoded-conf").text().trim();

            // Construct data string
            var datastring = dataString.serializeArray();

            //add to datastring the unchecked checkbox fields
            $this.closest('.modal-content').find("form input:checkbox").each(function(){
                if (!this.checked) {
                    datastring.push({name: this.name, value: 0});
                }                
            });

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
            
            // window.parent.$body.prop("style");
            // removeParentBodyPropStyle();
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

                    // window.parent.$("#id_meliscms_plugin_modal_container").modal('hide');
                    window.parent.melisCoreTool.hideModal("id_meliscms_plugin_modal_container");
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
                        } catch(e) {
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
    function sendDragnDropList(dropzone, pageId, reference) {

        reference = (typeof reference !== 'undefined') ? reference : '';

        var $dropzone = $('div[data-dragdropzone-id=' + dropzone + ']'),
            // parentDragndropContainer= $dropzone.closest(".melis-dragdropzone-container"),
            parentDragndropContainer = $dropzone.parents(".melis-dragdropzone-container").last(),

            // dragdropzoneModule      = $dropzone.data("module"),
            // dragdropzonePlugin      = $dropzone.data("plugin"),
            // dragdropzonePluginId    = $dropzone.data("plugin-id"),
            // dragdropzoneMelisTag    = $dropzone.data("melis-tag"),
            siteModule = $dropzone.data("site-module"),
            dropzoneList = parentDragndropContainer.find(".melis-dragdropzone"),
            // pluginListEl            = parentDragndropContainer.find(".melis-ui-outlined"),
            dragzone = [],
            pluginListContainer = new Object();

        // pluginListContainer['melisIdPage'] = pageId;
        // pluginListContainer['melisModule'] = $(parentDragndropContainer).data("module");
        // pluginListContainer['melisPluginName'] = $(parentDragndropContainer).data("plugin");
        // pluginListContainer['melisPluginId'] = $(parentDragndropContainer).data("plugin-id");
        // pluginListContainer['melisPluginTag'] = $(parentDragndropContainer).data("melis-tag");
        // pluginListContainer['children'] = new Object();


        // var dndContainer = parentDragndropContainer.find(".melis-dragdropzone-container");
        var list = new Object();
        // pluginListContainer['melisDragDropZoneListPlugin'] = getPluginLists($(parentDragndropContainer).find(".dnd-layout-wrapper").children(".melis-dragdropzone"));
        // pluginListContainer['children'] = getDNDList(parentDragndropContainer, pageId);

        // var ctr = 0;
        // $.each(dropzoneList, function (k, v) {
        //     var pluginListEl = $(v).find(".melis-ui-outlined");
        //     if ($(pluginListEl).length) {
        //         pluginListContainer['children'][ctr] = new Object();
        //         var pluginList = new Object();
        //
        //         // var toolBox = $('div[data-dragdropzone-id=' + dropzone + ']');
        //         // var toolBox = $(pluginListEl).find(".melis-plugin-tools-box");
        //
        //         pluginList['melisIdPage'] = pageId;
        //         pluginList['melisModule'] = $(v).data("module");
        //         pluginList['melisPluginName'] = $(v).data("plugin");
        //         pluginList['melisPluginId'] = $(v).data("plugin-id");
        //         pluginList['melisPluginTag'] = $(v).data("melis-tag");
        //         pluginList['melisDragDropZoneListPlugin'] = new Object();
        //
        //         // loop all plugins in dropzone
        //         $.each(pluginListEl, function (key, value) {
        //             pluginList['melisDragDropZoneListPlugin'][key] = new Object();
        //
        //             var plugins = $(value).find(".melis-plugin-tools-box"),
        //                 melisPluginModuleName = $(plugins).data("module"),
        //                 melisPluginName = $(plugins).data("plugin"),
        //                 melisPluginID = $(plugins).data("plugin-id"),
        //                 melisPluginTag = $(plugins).data("melis-tag"),
        //                 melisPluginContainer = $(plugins).data("plugin-container"),
        //                 melisPluginContainerId = $(pluginListEl).attr("id");
        //
        //             pluginList['melisDragDropZoneListPlugin'][key]['melisModule'] = melisPluginModuleName;
        //             pluginList['melisDragDropZoneListPlugin'][key]['melisPluginName'] = melisPluginName;
        //             pluginList['melisDragDropZoneListPlugin'][key]['melisPluginId'] = melisPluginID;
        //             pluginList['melisDragDropZoneListPlugin'][key]['melisPluginTag'] = melisPluginTag;
        //
        //             if ($("#" + melisPluginContainerId).length) {
        //                 pluginList['melisDragDropZoneListPlugin'][key]['melisPluginContainer'] = melisPluginContainer;
        //             } else {
        //                 pluginList['melisDragDropZoneListPlugin'][key]['melisPluginContainer'] = " ";
        //             }
        //         });
        //
        //         pluginListContainer['children'][ctr] = pluginList;
        //         ctr++;
        //     }
        // });

        pluginListContainer = extractContainers(parentDragndropContainer, pageId, reference);
        if (typeof siteModule !== "undefined") {
            savePluginUpdate(pluginListContainer, siteModule);
        }
    }

    function extractContainers($container, pageId, reference) {
        const result = {
            melisIdPage: pageId,
            melisModule: $container.data("module"),
            melisPluginName: $container.data("plugin"),
            melisPluginId: $container.data("plugin-id"),
            melisPluginTag: $container.data("melis-tag"),
            reference: reference,
            // melisDragDropZoneListPlugin: getPluginLists($($container).children(".melis-dragdropzone")),
        };

        if ($container.data('layout-template')) {
            result.dndLayout = $container.data('layout-template');
        }

		if ($container.data("layout-template")) {
			result.pluginReferer = $container.data("plugin-referer");
		}

        // Include plugin_list from this container only (not nested ones)
        // const pluginBoxes = $container.find('.melis-plugin-tools-box').filter(function () {
        //     return $(this).closest('.melis-dragdropzone-container').is($container);
        // });

        const $pluginBoxes = [];
        $container.find('.melis-plugin-tools-box').each(function(i, el) {
            const $this = $(this);
            const uiOutlined = $this.parents('.melis-ui-outlined').last();
            const plList = uiOutlined.find('.melis-ui-outlined');

            if (plList.length > 0) {
                const topBox = uiOutlined.children('.melis-plugin-tools-box').first();
                console.log(topBox);
                if (topBox.length) {
                    $pluginBoxes.push(topBox);
                }
            } else {
                $pluginBoxes.push($this);
            }
        });
        const pluginBoxes = $($.map($pluginBoxes, el => el.get(0))); // unified jQuery object

        // if (pluginBoxes.length) {
        //     result.melisDragDropZoneListPlugin = pluginBoxes.map(function () {
        //         return {
        //             melisModule: $(this).data("module"),
        //             melisPluginName: $(this).data("plugin"),
        //             melisPluginId: $(this).data("plugin-id"),
        //             melisPluginTag: $(this).data("melis-tag"),
        //         };
        //     }).get();
        // }

        if (pluginBoxes.length) {
            const seenIds = new Set();
            const seenTags = new Set();

            result.melisDragDropZoneListPlugin = pluginBoxes.map(function () {
                const $el = $(this);
                return {
                    melisModule: $el.data("module"),
                    melisPluginName: $el.data("plugin"),
                    melisPluginId: $el.data("plugin-id"),
                    melisPluginTag: $el.data("melis-tag"),
                };
            }).get().filter(function (plugin) {
                const isIdUnique = !seenIds.has(plugin.melisPluginId);
                const isTagUnique = !seenTags.has(plugin.melisPluginTag);

                if (isIdUnique && isTagUnique) {
                    seenIds.add(plugin.melisPluginId);
                    seenTags.add(plugin.melisPluginTag);
                    return true;
                }
                // duplicate found, skip
                return false;
            });
        }

        // Get only direct nested .melis-dragdropzone-container children, skipping deeper ones
        const childContainers = $container.children().find('.melis-dragdropzone-container').filter(function () {
            // Only include if parent .melis-dragdropzone-container is the current one
            return $(this).parents('.melis-dragdropzone-container').first().is($container);
        });

        if (childContainers.length) {
            result.children = [];
            childContainers.each(function () {
                result.children.push(extractContainers($(this)));
            });
        }

        return result;
    }

    /* function getDNDList(dndContainer, pageId)
    {
        const result = [];
    
        // dndContainer = $(dndContainer).children(".dnd-layout-wrapper");
    
        // Find all immediate children (anywhere under this node, but only one level down)
        dndContainer.children().each(function () {
            const $child = $(this);
    
            // Check if this child is a melis-dragdropzone-container
            if ($child.hasClass('melis-dragdropzone-container')) {
                result.push({
                    melisIdPage: pageId,
                    melisModule: $($child).data("module"),
                    melisPluginName: $($child).data("plugin"),
                    melisPluginId: $($child).data("plugin-id"),
                    melisPluginTag: $($child).data("melis-tag"),
                    melisDragDropZoneListPlugin: getPluginLists($($child).children(".melis-dragdropzone")),
                    children: getDNDList($child)
                });
            } else {
                // Otherwise, look inside it for immediate children
                $child.children('.melis-dragdropzone-container').each(function () {
                    const $subChild = $(this);
                    result.push({
                        mmelisIdPage: pageId,
                        melisModule: $($subChild).data("module"),
                        melisPluginName: $($subChild).data("plugin"),
                        melisPluginId: $($subChild).data("plugin-id"),
                        melisPluginTag: $($subChild).data("melis-tag"),
                        melisDragDropZoneListPlugin: getPluginLists($($subChild).children(".melis-dragdropzone")),
                        children: getDNDList($subChild)
                    });
                });
            }
        });
    
        return result;
    }
    
    function getPluginLists(dropzoneList)
    {
        var list = new Object();
        var ctr = 0;
        $.each(dropzoneList, function (k, v) {
            var pluginListEl = $(v).find(".melis-ui-outlined");
            if ($(pluginListEl).length) {
                // list = new Object();
                var pluginList = new Object();
                // loop all plugins in dropzone
                $.each(pluginListEl, function (key, value) {
                    pluginList[key] = new Object();
    
                    var plugins = $(value).find(".melis-plugin-tools-box"),
                        melisPluginModuleName = $(plugins).data("module"),
                        melisPluginName = $(plugins).data("plugin"),
                        melisPluginID = $(plugins).data("plugin-id"),
                        melisPluginTag = $(plugins).data("melis-tag"),
                        melisPluginContainer = $(plugins).data("plugin-container"),
                        melisPluginContainerId = $(pluginListEl).attr("id");
    
                    pluginList[key]['melisModule'] = melisPluginModuleName;
                    pluginList[key]['melisPluginName'] = melisPluginName;
                    pluginList[key]['melisPluginId'] = melisPluginID;
                    pluginList[key]['melisPluginTag'] = melisPluginTag;
    
                    if ($("#" + melisPluginContainerId).length) {
                        pluginList[key]['melisPluginContainer'] = melisPluginContainer;
                    } else {
                        pluginList[key]['melisPluginContainer'] = " ";
                    }
                });
    
                list = pluginList;
                ctr++;
            }
        });
        return list;
    } */

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

            // modal confirmation
            window.parent.melisCoreTool.confirm(
                translations.tr_meliscms_common_yes,
                translations.tr_meliscms_common_no,
                translations.tr_meliscms_delete_cms_plugin_modal_title,
                translations.tr_meliscms_delete_cms_plugin_modal_content,
                function() {
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
            );
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
        // frameHeight = window.parent.$("#"+ parent.activeTabId).find(".melis-iframe").contents().find("body").height()
        // window.parent.document.body.find('[id="'+parent.activeTabId+'"] .melis-iframe')
        // window.parent.$("#"+ parent.activeTabId).find(".melis-iframe")
        
        setTimeout(function() {
            // Uses the document.body.scrollHeight for tinymce plugins used at the bottom will be cut off if "body".height is used.
            var frameHeight = document.body.scrollHeight,
                $frame      = window.parent.$("#"+ parent.activeTabId).find(".melis-iframe");

                // $frame.height(frameHeight);

                /*
                * Added iframe.length for fixing issue: Uncaught TypeError: Cannot read property 'calcFrameHeight' of undefined
                * Added by: Junry 22/05/2019
                */
                if ( $frame.length ) {
                    $frame.height(frameHeight);
                }
        }, 2000);
    }

    function disableLinks(e) {
        $(e).on("click", function(event) { event.preventDefault(); });
    }

    function createPluginModal() {
        var $this               = $(this),
            toolBox             = $this.closest(".melis-plugin-tools-box"),
            pluginContainer     = toolBox.parent(".melis-ui-outlined"),
            pluginFrontConfig   = pluginContainer.find(".plugin-hardcoded-conf").text().trim(),
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
            iframe = window.parent.$("#"+ parent.activeTabId).find('iframe'),
            $melisUiOutlined = $(".melis-dragdropzone .melis-ui-outlined"); // need to check this part

            // check if melis-ui-outlined element is available in preview page
            if( $melisUiOutlined.length ) {
                $melisUiOutlined.each(function() {
                    $(this).resizable({
                        containment: 'parent',
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
                        $(editable).trigger("focus").removeClass("mce-edit-focus");

                    // hide tinymce option while resizing
                    var inst = tinyMCE.EditorManager.get(data.pluginId);
                        if (inst) {
                            inst.fire("blur");
                        }
                        else {
                            console.error('No editor found for ID:', data.pluginId);
                        }

                        iframe.trigger("blur");

                        $(editable).map(function() {
                            var $this = $(this);

                                pluginList['tagType']   =   $this.data("tag-type");
                                pluginList['tagId']     =   $this.data("tag-id");
                                pluginList['tagValue']  =   tinyMCE.activeEditor?.getContent({format : 'html'});
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
    /* window.parent.$("#"+ parent.activeTabId).find('iframe').on("resize", function() {
        $(this).contents().find(".melis-dragdropzone .melis-ui-outlined").each(function() {
            $(this).css("width", "");
        });
    }); */

    // init resize
    if ( parent.pluginResizable == 1 ) {
        // initResizable(); // disable for now

        // waitForTinyMCELoadedAndReady(function(allLoaded) {
        //     if (allLoaded) {
        //         console.log('awerawer');
        //         initResizable();
        //     }
        //     else {
        //         console.warn("TinyMCE editors did not load within the expected time.");
        //     }
        // });

        // const interval = setInterval(function () {
        //     if ($.active === 0) {
        //         clearInterval(interval);
        //         initResizable();
        //     }
        // }, 100); // Check every 100ms

        onParentFullyLoaded(() => {
            // console.log('✅ Parent page is fully loaded');
            initResizable();
        });
    }

    function onParentFullyLoaded(callback) {
        if (!window.parent || window.parent === window) {
            // Not inside an iframe
            return;
        }

        const parentDoc = window.parent.document;

        if (parentDoc.readyState === 'complete') {
            // Parent is already fully loaded
            callback();
        } else {
            // Wait until the parent is fully loaded
            window.parent.addEventListener('load', () => {
                callback();
            });
        }
    }

    function browserDetect() {
        var $html   = $("html"),
            ua      = navigator.userAgent;

                /* MSIE used to detect old browsers and Trident used to newer ones, Edge for Microsoft Edge */
                if ( ua.indexOf("MSIE ") > -1 || ua.indexOf("Trident/") > -1 || ua.indexOf("Edge/") > -1 ) {
                    $html.addClass("ie_edge");
                } else if ( ua.indexOf("Chrome/") > -1 ) {
                    $html.addClass("chrome");
                } else if ( ua.indexOf("Safari/") > -1 ) {
                    $html.addClass("safari");
                } else if ( ua.indexOf("Firefox/") > -1 ) {
                    $html.addClass("firefox");
                }

            var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

                if ( isOpera ) {
                    $html.addClass("opera");
                }
    }

    /* function allTinyMCEEditorsLoaded(callback) {
        var checkInterval = 100; // milliseconds
        var maxWaitTime = 10000; // 10 seconds
        var waited = 0;
    
        var interval = setInterval(function () {
            // Check if tinymce and tinymce.editors are defined
            if (window.tinymce && Array.isArray(tinymce.editors)) {
                var editors = tinymce.editors;
    
                // Check if all editors are initialized
                var allReady = editors.length > 0 && editors.every(function (editor) {
                    return editor.initialized && !editor.removed;
                });
    
                if (allReady) {
                    clearInterval(interval);
                    callback(true);
                    return;
                }
            }
    
            // Stop checking after max wait time
            waited += checkInterval;
            if (waited >= maxWaitTime) {
                clearInterval(interval);
                callback(false);
            }
    
        }, checkInterval);
    } */

    /* function waitForTinyMCELoadedAndReady(callback) {
        var checkTinyMCEInterval = setInterval(function () {
            if (window.tinymce && typeof tinymce.init === 'function') {
                clearInterval(checkTinyMCEInterval);
                allTinyMCEEditorsLoaded(callback);
            }
        }, 100);
    } */

    moveResponsiveClass();
    pluginDetector();
    browserDetect();
    
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

/** 
 * Transferred from inside melisPluginEdition, jQuery migration 3.7.1, jQuery(window).on('load'...) called after load event occurred
 * https://stackoverflow.com/questions/38585373/why-is-my-load-event-function-not-beeing-executed-after-switching-to-jquery-3
 * https://github.com/jquery/jquery/issues/3194
 * Try $(window.parent)
 */
$(window.parent).on("load", function() {
    melisPluginEdition.calcFrameHeight();
});

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
