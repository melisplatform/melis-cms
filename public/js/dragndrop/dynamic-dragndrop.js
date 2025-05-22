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

                        $(this).parent().children("[data-plugin-id]").each(function(i, el){
                            $(el).attr({
                                'data-plugin-id': newPluginId,
                                'data-tag-id': newPluginId,
                                'id': newPluginId
                            });
                        });

                        //update encoded text
                        let str = $(this).parent().children(".plugin-hardcoded-conf").text().trim();
                        // Extract the ID using RegExp
                        let match = str.match(/s:\d+:"(tag01_\d+)";/);
                        if (match) {
                            let oldId = match[1];
                            let newId = newPluginId;
                            let newSerializedId = `s:${newId.length}:"${newId}";`;

                            // Replace old ID entry
                            let updated = str.replace(/s:\d+:"tag01_\d+";/, newSerializedId);
                            $(this).parent().children(".plugin-hardcoded-conf").text(updated);
                        }

                        if($(this).hasClass("textarea-editable")){
                            textAreaInit.push(newPluginId);
                        }
                        if($(this).hasClass("media-editable")){
                            mediaInit.push(newPluginId);
                        }
                        if($(this).hasClass("html-editable")){
                            htmlInit.push(newPluginId);
                        }

                    }else {
                        el.attr({
                            'data-plugin-id': newPluginId,
                            'data-tag-id': newPluginId,
                            'id': newPluginId
                        });
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
            $.each(htmlInit, function(i, value){
                melistagHTML_init(value);
            });
            $.each(textAreaInit, function(i, value){
                window.melistagTEXTAREA_init(value);
            });
            $.each(mediaInit, function(i, value){
                window.melistagMEDIA_init(value);
            });
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
});