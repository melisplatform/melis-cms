$(function() {
    let $body       = $("body"),
        $dndButtons = $('.dnd-layout-buttons, .dnd-bottom-buttons');

        // .dnd-layout-wrapper
        $body
            .on("mouseenter", ".dnd-layout-wrapper", function(e) {
                // e.stopPropagation();

                //$dndButtons.removeClass("show-buttons");
                $dndButtons.hide();

                //$(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
                $(this).children(".dnd-layout-buttons").show();

                $(this).children(".dnd-bottom-buttons").css({
                    "opacity" : "1",
                    "visibility" : "visible"
                });
            })
            .on("mouseleave", ".dnd-layout-wrapper", function() {
                //$(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").removeClass("show-buttons");
                $(this).children(".dnd-layout-buttons").hide();

                $(this).children(".dnd-bottom-buttons").css({
                    "opacity" : "0",
                    "visibility" : "hidden"
                });
            });
        
        // .column-icons, button tag
        $body
            .on("mouseenter", ".column-icon", function() {
                $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
            })
            .on("mouseleave", ".column-icon", function() {
                $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
            });

        // .dnd-plus-button
        /* $body.on("click", ".dnd-plus-button", function() {          
            let _this = $(this);
            let pluginId = _this.data("pluginId");
            let parentDNDId = _this.closest("melis-dragdropzone-container").last();
        }); */

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function() {
            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            /* console.log({dndId});
            console.log({dndTpl});
            console.log({pageId});
            console.log({melisSite}); */
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
                            melistagHTML_init();

                        }, 1000)
                    }

                }
            }).always(() => {
                $(this).find("#loader").remove();
            });
        });
});