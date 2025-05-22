$(function() {
    let $document   = $(document),
        $body       = $("body"),
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
        /* $body
            .on("mouseenter", ".column-icon", function() {
                $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
            })
            .on("mouseleave", ".column-icon", function() {
                $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
            }); */

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

        $('[data-bs-toggle="popover"]').each(function() {
            let $trigger    = $(this),
                contentId   = $trigger.data("bs-content-id"),
                content     = $("#"+contentId).html();

                console.log({contentId});

                $trigger.closest(".dnd-layout-buttons").show();

                $trigger.popover({
                    html: true,
                    sanitize: false,
                    content: content,
                    trigger: "manual",
                    container: $trigger.closest(".dnd-layout-buttons"),
                    template: '<div class="popover dnd-layout-buttons-popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                });

                // Disable trigger button when popover is shown
                $trigger.on("shown.bs.popover", function() {
                    $trigger.prop("disabled", true);
                });

                // Re-enable trigger button when popover is hidden
                $trigger.on("hidden.bs.popover", function() {
                    $trigger.prop("disabled", false);
                });

                // Toggle popover on click
                $trigger.on("click", function() {
                    console.log(`popover clicked !!!`);
                    console.log($trigger.prop("disabled"));
                    if ($trigger.prop("disabled")) {
                        $trigger.popover("hide");
                    } else {
                        $('[data-bs-toggle="popover"]').popover("hide"); // Hide other popovers
                        $trigger.popover("show");
                    }
                });
        });

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function() {
            let $this       = $(this),
                dndId       = $this.data("dndId"),
                dndTpl      = $this.data("dndTpl"),
                pageId      = $this.data("pageId"),
                melisSite   = $this.data("melisSite"),
                tempLoader  = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';

                $this.closest(".melis-dragdropzone-container").prepend(tempLoader);
                
                $.get("/dnd-layout", {
                    pageId,
                    dndId,
                    dndTpl,
                    melisSite
                }).done((res) => {
                    console.log({res});
                    if (res.success) {
                        console.log({dndId});
                        console.log($(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']"));

                        $(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']").replaceWith(res.html);

                        //$('[data-bs-toggle="popover"]').popover("hide");

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

                        $(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']").find("#loader").remove();
                    }
                }).always(() => {
                    $(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']").find("#loader").remove();
                }).fail(() => {
                    alert(translations.tr_meliscore_error_message);
                });
        });

        $document.on("click", ".popover-body .close-btn", function() {
            let $popoverTrigger = $(this).closest(".popover").prevAll('[data-bs-toggle="popover"]').first();
                $popoverTrigger.popover("hide");
        });

        $document.on('click', function(e) {
            if (!$(e.target).closest('.popover').length && !$(e.target).is('[data-bs-toggle="popover"]')) {
                $('[data-bs-toggle="popover"]').popover('hide');
            }
        });
});