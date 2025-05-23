$(function() {
    let $document   = $(document),
        $body       = $("body"),
        $dndButtons = $('.dnd-layout-buttons');

        // .dnd-layout-wrapper
        $body
            .on("mouseenter", ".dnd-layout-wrapper", function (e) {
                // e.stopPropagation();

                    $dndButtons.hide();

                    $(this).children(".dnd-layout-buttons").show();
                })
                .on("mouseleave", ".dnd-layout-wrapper", function () {
                    $(this).children(".dnd-layout-buttons").hide();
                });

        // .column-icons, button tag
        /* $body
                .on("mouseenter", ".column-icon", function () {
                    $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
                })
                .on("mouseleave", ".column-icon", function () {
                    $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
                }); */

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function () {
            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            var tempLoader =
                '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';

            $(this).closest(".melis-dragdropzone-container").prepend(tempLoader);

            let dndContainer = $(this).closest(".melis-dragdropzone-container");

            let dndLayout = dndContainer.find(".melis-dragdropzone");
            let dndCtr = dndLayout.length;

            console.log({ dndCtr });

            $.get("/dnd-layout", {
                pageId,
                dndId,
                dndTpl,
                melisSite,
            })
                .done((res) => {
                    if (res.success) {
                        dndContainer.find("#loader").remove();

                        let newLayout = $(res.html);
                        let newLayoutDnd = newLayout.find(".melis-dragdropzone");
                        let newLayoutDndCtr = newLayoutDnd.length;

                        console.log({ newLayoutDndCtr });

                        // if (dndCtr != newLayoutDndCtr) {
                        if (dndCtr == 1) {
                            let dndContent = dndContainer.find(".melis-dragdropzone");

                            $.each(dndContent.children(), (i, v) => {
                                // filter dnd with contents
                                if ($(v).text().trim() !== "") {
                                    // move and append to new dnd layout
                                    $(v).appendTo(newLayout.find(".melis-dragdropzone:first"));
                                }
                            });

                            // replacing new layout
                            $(
                                "body .melis-dragdropzone-container[data-plugin-id='" +
                                    dndId +
                                    "']"
                            ).replaceWith(newLayout);
                        } else {
                            // comparing dnds to the new dnd layout
                            $.each(dndLayout, (i, v) => {
                                // checking if the plugin id not exist in the new layout
                                let pluginExist = newLayout.find(
                                    "div.melis-dragdropzone[data-plugin-id='" +
                                        $(v).data("pluginId") +
                                        "']"
                                );
                                // all plugin no exist in the new layout will append to the last dnd in the new layout
                                if (!pluginExist.length) {
                                    let dndContents = $(v).children();

                                    $.each(dndContents, (di, dv) => {
                                        // filter dnd with contents
                                        if ($(dv).text().trim() !== "") {
                                            // move and append to new dnd layout
                                            $(dv).appendTo(newLayout.find(".melis-dragdropzone:last"));
                                        }
                                    });
                                }
                            });

                            // replacing new layout
                            $(
                                "body .melis-dragdropzone-container[data-plugin-id='" +
                                    dndId +
                                    "']"
                            ).replaceWith(newLayout);
                        }
                        // }

                        melisPluginEdition.moveResponsiveClass();
                        melisPluginEdition.pluginDetector();
                        melisPluginEdition.initResizable();
                        melisDragnDrop.setDragDropZone();

                        // save change to session
                        melisPluginEdition.sendDragnDropList(dndId, pageId);

                        // call popoverInit()
                        popoverInit();
                    }
                })
                .always(() => {
                    dndContainer.find("#loader").remove();
                });
        });

        $body.on("click", ".dnd-plus-button", function() {
            let _this = $(this);
            let pluginId = _this.data("plugin-id");
            let siteModule = _this.data("site-module");
            let parentDND = $(_this).parents(".melis-dragdropzone-container").last();
            let pageId = _this.data("page-id");

            let originalLayout = parentDND.data("layout-template");

            let parent = parentDND.attr("data-plugin-id");
            let sourceId = pluginId;

            // Get the source container wrapper
            let sourceContainer = $('.melis-dragdropzone-container[data-dragdropzone-id="' + sourceId + '"]');
            //update template
            $(sourceContainer).data("layout-template", 'MelisFront/dnd-2-cols-down-tpl');

            if (!sourceContainer.length) {
                console.warn("Source container '" + sourceId + "' not found.");
                return;
            }

            // Ensure .row exists inside sourceContainer
            let row = sourceContainer.children('.row');
            if (!row.length) {
                let existingContent = sourceContainer.children().not('.row');

                // Wrap non-row content if necessary
                if (!existingContent.hasClass('melis-dragdropzone-container')) {

                    if(sourceContainer.find(".melis-dragdropzone-container").length <= 1) {
                        originalLayout = 'MelisFront/dnd-default-tpl';
                    }

                    let wrapper = $('<div class="melis-dragdropzone-container"></div>')
                        .attr({
                            'data-dragdropzone-id': sourceId,
                            'data-plugin-id': sourceId,
                            'data-tag-id': sourceId,
                            'id': sourceId,
                            'data-site-module': siteModule,
                            'data-layout-template': originalLayout
                        });
                    wrapper.append(existingContent);
                    existingContent = wrapper;
                }

                row = $('<div class="row"></div>');
                let col = $('<div class="col-md-12"></div>').append(existingContent);
                row.append(col);
                sourceContainer.append(row);
            }

            // Determine next hierarchical index
            let lastIndex = 0;
            sourceContainer.find('[data-dragdropzone-id]').each(function () {
                let dndId = $(this).attr('data-dragdropzone-id');
                let match = dndId.match(new RegExp('^' + sourceId + '_(\\d+)$'));
                if (match) {
                    lastIndex = Math.max(lastIndex, parseInt(match[1], 10));
                }
            });
            let newIndex = lastIndex + 1;
            let newDNDId = sourceId + '_' + newIndex;

            // Get the last .col-md-12 and clone the wrapper inside it
            let lastCol = row.children('.col-md-12').last();
            let wrapperToClone = lastCol.find('.melis-dragdropzone-container[data-dragdropzone-id="' + sourceId + '"]').first();
            if (!wrapperToClone.length) {
                console.warn("No wrapper with data-dragdropzone-id='" + sourceId + "' found.");
                return;
            }

            // Deep clone with events and data
            let clonedWrapper = wrapperToClone.clone(true, true);

            // Function to update IDs on wrapper and children (non-destructive)
            clonedWrapper.find('[data-dragdropzone-id]').addBack().each(function () {
                let el = $(this);
                let oldId = el.attr('data-dragdropzone-id');

                if (oldId && oldId.startsWith(sourceId)) {
                    let suffix = oldId.substring(sourceId.length); // like _1, _1_2 etc.
                    let newFullId = newDNDId + suffix;

                    el.attr('data-dragdropzone-id', newFullId);

                    // // Only update plugin/tag/id if they existed
                    if (el.attr('data-plugin-id')) el.attr('data-plugin-id', newFullId);
                    if (el.attr('data-tag-id')) el.attr('data-tag-id', newFullId);
                    if (el.attr('id')) el.attr('id', newFullId);

                    // el.attr({
                    //     'data-plugin-id': newFullId,
                    //     'data-tag-id': newFullId,
                    //     'id': newFullId
                    // });
                }
            });

            var htmlInit = [];
            var mediaInit = [];
            var textAreaInit = [];

            // Update plugin IDs inside the cloned wrapper
            var latestPlId = 0;
            clonedWrapper.find('[data-plugin-id]').each(function () {
                let el = $(this);
                let oldPluginId = el.attr('data-plugin-id');
                let match = oldPluginId.match(/^(.*?)(\d+)$/);

                if (match) {
                    let prefix = match[1];
                    let num = parseInt(match[2], 10) + 1;
                    let newPluginId = prefix + num.toString().padStart(match[2].length, '0');

                    if($(this).parent().hasClass('melis-ui-outlined')){
                        let maxId = getNextPluginIndex(clonedWrapper);
                        latestPlId = parseInt(maxId, 10) + 1;
                        newPluginId = prefix + latestPlId.toString().padStart(maxId.length, '0');

                        // Replace only the number at the end
                        let oldId = $(this).parent().attr('id');
                        let newId = oldId.replace(/_(\d+)$/, '_' + latestPlId.toString());
                        // Set the new ID
                        $(this).parent().attr('id', newId);
                        $(this).parent().attr('data-plugin-id', newPluginId);
                        $(this).parent().attr('data-tag-id', newPluginId);

                        $(this).parent().children("[data-plugin-id], [data-pcache-plugin-id]").each(function(i, el){
                            // $(el).attr({
                            //     'data-plugin-id': newPluginId,
                            //     'data-tag-id': newPluginId,
                            //     'id': newPluginId
                            // });

                            if ($(el).attr('data-pcache-plugin-id')) $(el).attr('data-pcache-plugin-id', newPluginId);
                            if ($(el).attr('data-plugin-id')) $(el).attr('data-plugin-id', newPluginId);
                            if ($(el).attr('data-tag-id')) $(el).attr('data-tag-id', newPluginId);
                            if ($(el).attr('id')) $(el).attr('id', newPluginId);
                        });

                        //update encoded text
                        let str = $(this).parent().children(".plugin-hardcoded-conf").text().trim();
                        // console.log(str);
                        // Extract the ID using RegExp
                        // let match = str.match(/s:\d+:"(tag01_\d+)";/);
                        // if (match) {
                        //     let oldId = match[1];
                        //     let newId = newPluginId;
                        //     let newSerializedId = `s:${newId.length}:"${newId}";`;
                        //
                        //     // Replace old ID entry
                        //     let updated = str.replace(/s:\d+:"tag01_\d+";/, newSerializedId);
                        //     $(this).parent().children(".plugin-hardcoded-conf").text(updated);
                        // }

                        let updated = str.replace(
                            /s:\d+:"[^"]+_\d+";/,
                            `s:${newPluginId.length}:"${newPluginId}";`
                        );

                        $(this).parent().children(".plugin-hardcoded-conf").text(updated);

                        if (el.attr('data-plugin-id')) el.attr('data-plugin-id', newPluginId);

                        // if($(this).hasClass("textarea-editable")){
                        //     textAreaInit.push(newPluginId);
                        // }
                        // if($(this).hasClass("media-editable")){
                        //     mediaInit.push(newPluginId);
                        // }
                        // if($(this).hasClass("html-editable")){
                        //     htmlInit.push(newPluginId);
                        // }

                    }else {
                        // el.attr({
                        //     'data-plugin-id': newPluginId,
                        //     'data-tag-id': newPluginId,
                        //     'id': newPluginId
                        // });

                        if (el.attr('data-plugin-id')) el.attr('data-plugin-id', newPluginId);
                        if (el.attr('data-tag-id')) el.attr('data-tag-id', newPluginId);
                        if (el.attr('id')) el.attr('id', newPluginId);
                    }
                }
            });

            // Wrap in a new .col-md-12 and append to the row
            let newCol = $('<div class="col-md-12"></div>').append(clonedWrapper);
            row.append(newCol);

            // let baseId = sourceId.replace(/(_\d+)+$/, '');
            // fixNestedDndIds(sourceId);

            let root = $('[data-dragdropzone-id="'+sourceId+'"]').first();
            updateNestedDragdropIds(root, sourceId);

            // Save the session
            melisPluginEdition.sendDragnDropList(newDNDId, pageId, parent);
            //re init tags
            // $.each(htmlInit, function(i, value){
                if (typeof melistagHTML_init !== "undefined") {
                    melistagHTML_init();
                }
            // });
            // $.each(textAreaInit, function(i, value){
                if (typeof melistagTEXTAREA_init !== "undefined") {
                    melistagTEXTAREA_init();
                }
            // });
            // $.each(mediaInit, function(i, value){
                if (typeof melistagMEDIA_init !== "undefined") {
                    melistagMEDIA_init();
                }
            // });

            //init dnd zones
            melisPluginEdition.moveResponsiveClass();
            melisPluginEdition.pluginDetector();
            melisPluginEdition.initResizable();
            melisDragnDrop.setDragDropZone();
        });

        function updateNestedDragdropIds($element, baseId) {
            let index = 1;

            // Find only the FIRST nested .melis-dragdropzone-container for each branch
            $element.find('.melis-dragdropzone-container').each(function () {
                const $child = $(this);

                // Skip if it's already been renamed in a deeper recursion
                if ($child.data('renamed')) return;

                const newId = `${baseId}_${index}`;

                // Update all attributes
                $child.attr('data-dragdropzone-id', newId);
                if ($child.attr('data-plugin-id')) $child.attr('data-plugin-id', newId);
                if ($child.attr('data-tag-id')) $child.attr('data-tag-id', newId);
                if ($child.attr('id')) $child.attr('id', newId);

                // Mark as renamed to avoid renaming again in deeper recursions
                $child.data('renamed', true);

                // Recurse on this child to rename its descendants
                updateNestedDragdropIds($child, newId);

                index++;
            });
        }

        function getNextPluginIndex(sourceContainer) {
            let max = 0;
            $(sourceContainer).find('[data-plugin-id]').each(function () {
                let el = $(this);
                if($(this).parent().hasClass('melis-ui-outlined')){
                    let pluginId = el.attr('data-plugin-id');
                    let match = pluginId.match(/^(.*?)(\d+)$/);

                    if(match){
                        var tagId = parseInt(match[2], 10);
                        if(max < tagId){
                            max = tagId;
                        }

                    }
                }
            });
            return max;
        }

        function popoverInit() {
            $('[data-bs-toggle="popover"]').each(function () {
                let $trigger = $(this),
                    contentId = $trigger.data("bs-content-id"),
                    content = $("#" + contentId).html();

                $trigger.popover({
                    html: true,
                    sanitize: false,
                    content: content,
                    trigger: "click",
                    container: $trigger.closest(".dnd-layout-buttons"),
                    template:
                        '<div class="popover dnd-layout-buttons-popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
                });

                // Disable trigger button when popover is shown
                /* $trigger.on("shown.bs.popover", function() {
                            $trigger.prop("disabled", true);
                        });

                        // Re-enable trigger button when popover is hidden
                        $trigger.on("hidden.bs.popover", function() {
                            $trigger.prop("disabled", false);
                        });

                        // Toggle popover on click
                        $trigger.on("click", function() {
                            //$trigger.closest(".dnd-layout-buttons").show();

                            if ($trigger.prop("disabled")) {
                                $trigger.popover("hide");
                            } else {
                                $('[data-bs-toggle="popover"]').popover("hide"); // Hide other popovers
                                $trigger.popover("show");
                            }
                        }); */
            });
        }

        popoverInit();

        $body.on("click", ".popover-body .close-btn", function () {
            let $popoverTrigger = $(this)
                .closest(".popover")
                .prevAll('[data-bs-toggle="popover"]')
                .first();
            $popoverTrigger.popover("hide");
        });

        /* $body.on('click', function(e) {
                if (!$(e.target).closest('.popover').length && !$(e.target).is('[data-bs-toggle="popover"]')) {
                    $('[data-bs-toggle="popover"]').popover('hide');
                }
            }); */
});
