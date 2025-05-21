$(function() {
    let $body = $("body"),
        $dndButtons = $('.dnd-layout-buttons, .dnd-bottom-buttons');

        // .dnd-layout-wrapper
        $body
            .on("mouseenter", ".dnd-layout-wrapper", function(e) {
                // e.stopPropagation();

                //$dndButtons.removeClass("show-buttons");
                $dndButtons.hide();

                //$(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
                $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").show();

                /* $(this).children(".dnd-bottom-buttons").css({
                    "opacity" : "1",
                    "visibility" : "visible"
                }); */
            })
            .on("mouseleave", ".dnd-layout-wrapper", function() {
                //$(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").removeClass("show-buttons");
                $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").hide();

                /* $(this).children(".dnd-bottom-buttons").css({
                    "opacity" : "0",
                    "visibility" : "hidden"
                }); */
            });

        // .column-icons, button tag
        $body
            .on("mouseenter", ".column-icon", function() {
                $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
            })
            .on("mouseleave", ".column-icon", function() {
                $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
            });

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function() {
            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            
            var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';

            $(this).closest(".melis-dragdropzone-container").prepend(tempLoader);

            $.get("/dnd-layout", {
                pageId,
                dndId,
                dndTpl,
                melisSite
            }).done((res) => {
                //console.log({res});
                if (res.success) {
                    $(this).find("#loader").remove();

                    $(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']").replaceWith(res.html);

                    if (res.pluginsInitFiles) {

                        setTimeout(() => {
                            $.each(res.pluginsInitFiles, (i, v) => {
                                // reinitialize plugins
                                // melisPluginEdition.processPluginResources(v, i);
                            });

                            melisPluginEdition.moveResponsiveClass();
                            melisPluginEdition.pluginDetector();
                            melisPluginEdition.initResizable();

                            melisPluginEdition.sendDragnDropList(dndId, pageId);

                            // TODO 
                            window.melistagHTML_init();

                        }, 1000)
                    }

                }
            }).always(() => {
                $(this).find("#loader").remove();
            });
        });

        $body.on("click", ".dnd-plus-button", function() {
            let _this = $(this);
            let pluginId = _this.data("plugin-id");
            let parentDND = $(_this).parents(".melis-dragdropzone-container").last();
            let pageId = _this.data("page-id");

            var parent = $(parentDND).attr("data-plugin-id");
            // The source class you want to clone
            var sourceId = pluginId;

            // Find the main container

            var container = $('[data-dragdropzone-id^="' + parent + '"]');
            var children = container.find('[data-dragdropzone-id^="' + parent + '_"]');

            // Find max index suffix used in dragdropzone IDs
            var lastIndex = 0;
            children.each(function () {
                var id = $(this).attr('data-dragdropzone-id');
                var match = id.match(new RegExp(parent + '_(\\d+)$'));
                if (match) {
                    lastIndex = Math.max(lastIndex, parseInt(match[1]));
                }
            });

            var sourceDOM = $('.melis-dragdropzone-container[data-dragdropzone-id="' + sourceId + '"]');

            var source = sourceDOM.first();

            if (!source.length) {
                console.warn("Source element '" + sourceId + "' not found.");
                return;
            }

            var newIndex = lastIndex + 1;
            var newId = parent + '_' + newIndex;
            var newDNDId = newId;

            var clone = source.clone(true, true);
            clone.attr('data-dragdropzone-id', newId);
            clone.attr('data-plugin-id', newId);
            clone.attr('data-tag-id', newId);
            clone.attr('id', newId);

            clone.find("button.dnd-plus-button").attr('data-plugin-id', newId);

            var originalId = sourceId;  // e.g. "centered_dragdrop_html_1"
            // Update nested duplicates of the same data-dragdropzone-id
            var ctr = 1;
            clone.find('[data-dragdropzone-id="' + originalId + '"]').each(function(i) {
                // var nestedNewId = newId + '_' + (i + 1);
                $(this).attr('data-dragdropzone-id', newId);
                $(this).attr('data-plugin-id', newId);
                $(this).attr('data-tag-id', newId);
                $(this).attr('id', newId);

                $(this).find('[data-plugin="MelisFrontDragDropZonePlugin"]').each(function(i, el) {
                    var nestedNewId = newId + '_' + (i + 1);
                    $(el).attr('data-dragdropzone-id', nestedNewId);
                    $(el).attr('data-plugin-id', nestedNewId);
                    $(el).attr('data-tag-id', nestedNewId);
                    $(el).attr('id', nestedNewId);
                });

                ctr = i;
            });

            // Increment numeric suffix of any data-plugin-id by 1
            clone.find('.melis-plugin-tools-box, .mce-content-body').each(function () {
                var oldId = $(this).attr('data-plugin-id');
                // Match prefix + numeric suffix at the end (digits)
                var match = oldId.match(/^(.*?)(\d+)$/);
                if (match) {
                    var prefix = match[1]; // anything before the digits
                    var num = parseInt(match[2], 10);
                    var newNum = num + 1;

                    // Preserve zero-padding length of the original number
                    var numStr = match[2];
                    var newNumStr = newNum.toString().padStart(numStr.length, '0');

                    var newId = prefix + newNumStr;
                    $(this).attr('data-plugin-id', newId);
                    $(this).attr('data-tag-id', newId);
                    $(this).attr('id', newId);
                }
            });

            children.last().css("background", "green");
            children.last().closest(".melis-dragdropzone-container").css("background", "blue");

            // children.last().closest(".melis-dragdropzone-container").after(clone);
            // children.last().after(clone);
            // parentDND.after(clone);
            // container.parents('.melis-dragdropzone-container').last().parent().append(clone);

            sourceDOM.last().parent().append(clone);

            //save sessions
            melisPluginEdition.sendDragnDropList(newDNDId, pageId, parent);
        });
});