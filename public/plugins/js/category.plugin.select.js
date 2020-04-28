window.initCategorySelectField  =  function() {
    var targetElement   = $('.melis-cms-category-select'),
        btn             = "<a class='btn btn-default melis-cms-category-select-button'><i class='fa fa-sitemap'></i></a>";

        targetElement.wrap("<div></div>");
        targetElement.after(btn);
};

window.initCategorySelectTree = function(targetElement) {
    var $body = $("body");

        $(targetElement).on('refresh.jstree', function (e, data) {
            melisCoreTool.done('#categorySelectSiteFilter');
            melisCoreTool.done('#clear-search');
            melisCoreTool.done('#expand-tree');
            melisCoreTool.done('#collapse-tree');
            melisCoreTool.done('#refresh-tree');
            melisCoreTool.done('#categorySelectSiteFilter');
            melisCoreTool.done('#categorySelectLangFilter');
            melisCoreTool.done('#category-select-search-tree');
            $("#category-select-search-tree").trigger('keyup');
        }).jstree({
            "core" : {
                "multiple": false,
                "check_callback": true,
                "animation" : 500,
                "themes": {
                    "name": "proton",
                    "responsive": false
                },
                "dblclick_toggle" : false,
                "data" : {
                    "cache" : false,
                    "url" : "/melis/MelisCmsCategory2/MelisCmsCategoryList/getCategoryTreeView?langlocale="+$(targetElement).data('langlocale'),
                }
            },
            "types" : {
                "#" : {
                    "valid_children" : ["category"]
                },
                "catalog" : {
                    "valid_children" : ["category"]
                },
                "category" : {
                    "valid_children" : ["category"]
                },
            },
            "plugins": [
                "search" // Plugins for Search of the Node(s) of the Tree View
            ]
        });

        // remove all dblclick functions
        $body.off('dblclick',".melis-cms-category-select-tree .jstree-node");
};

$(function() {
    var $body                       = $("body"),
        categorySelectBtn           = '.melis-cms-category-select-button',
        melisCmsCategorySelectField = null;

        $body.on('click', categorySelectBtn, function() {
            var zoneId      = "melis_cms_categories_category_select_modal_content",
                melisKey    = "melis_cms_categories_category_select_modal_content",
                modalUrl    = "/melis/MelisCmsCategory2/MelisCmsCategorySelect/render-category-select-modal",
                params      = {};

                melisCmsCategorySelectField = $(categorySelectBtn).prev();
                
                melisHelper.createModal(zoneId,melisKey,false,params,modalUrl,function(){
            });
        });
        
        //selecting a category
        $body.on('click','#add-selected-category', function() {
            var selectedCategory =  $(".melis-cms-category-select-tree .jstree-clicked");

                if ( selectedCategory.length > 0 ) {
                    var categoryid = selectedCategory.attr('id').split('_')[0];

                        melisCmsCategorySelectField.val(categoryid);
                }
                else {
                    melisCmsCategorySelectField.val($("#root-checkbox").val());
                }
        });

        /* $body.on('dblclick','#melis_cms_categories_category_select_modal_content .jstree-node', function() {
            console.log('i am selected');
        }); */

        $body.on('click', '#root-checkbox', function() {
            $(".melis-cms-category-select-tree .jstree-clicked").removeClass('jstree-clicked');
        });

        $body.on('click','#melis_cms_categories_category_select_modal_content .jstree-node', function() {
            $("#root-checkbox").prop("checked", false);
        });

        $body.on("keydown", "#melis_cms_categories_category_select_modal_content #category-select-search-tree", function(e) {
            var $this           = $(this),
                searchString    = $this.val().trim(),
                searchResult    = $('.melis-cms-category-select-tree').jstree('search', searchString);

                setTimeout(function() {
                    if ( $(searchResult).find('.jstree-search').length == 0 && searchString != '' ) {
                        $("#searchSelectNoResult").removeClass('hidden');
                        $("#searchSelectNoResult").find("strong").text(searchString);
                    }
                    else {
                        $("#searchSelectNoResult").addClass('hidden');
                    }
                }, 5);
        });

        $body.on("keyup", "#melis_cms_categories_category_select_modal_content #category-select-search-tree", function(e) {
            var $this           = $(this),
                searchString    = $this.val().trim(),
                searchResult    = $('.melis-cms-category-select-tree').jstree('search', searchString);
                
                setTimeout(function(){
                    if ( $(searchResult).find('.jstree-search').length == 0 && searchString != '' ) {
                        $("#searchSelectNoResult").removeClass('hidden');
                        $("#searchSelectNoResult").find("strong").text(searchString);
                    }
                    else {
                        $("#searchSelectNoResult").addClass('hidden');
                    }
                }, 5);
        });

        // Clear Input Search
        $body.on("click", "#clear-search", function(e) {
            categoryOpeningItemFlag = false;
            $("#category-select-search-tree").val("");
            $('.melis-cms-category-select-tree').jstree('search', '');
            // $("#searchNoResult").addClass('hidden');
        });

        // Toggle Buttons for Category Tree View
        $body.on("click", "#expand-tree", function(e) {
            $(".melis-cms-category-select-tree").jstree("open_all");
        });

        $body.on("click", "#collapse-tree", function(e) {
            $(".melis-cms-category-select-tree").jstree("close_all");
        });

        // Refrech Category Tree View
        $body.on("click", "#refresh-tree", function(e) {
            categoryOpeningItemFlag = false;
            var catTree = $('.melis-cms-category-select-tree').jstree(true);

                catTree.deselect_all();
                catTree.refresh();
                $("#category-select-search-tree").val("");
                $('.melis-cms-category-select-tree').jstree('search', '');
                // $("#searchNoResult").addClass('hidden');
        });

        // site filter
        $body.on('change',"#categorySelectSiteFilter", function() {
            var value           = this.value,
                cmsCategoryTree = $(".melis-cms-category-select-tree"),
                langLocale      = $("#categorySelectLangFilter").val();

                if ( typeof( cmsCategoryTree.jstree(true).settings ) !== "undefined" ) {
                    melisCoreTool.pending('#categorySelectSiteFilter');
                    melisCoreTool.pending('#category-select-search-tree');
                    melisCoreTool.pending('#clear-search');
                    melisCoreTool.pending('#expand-tree');
                    melisCoreTool.pending('#collapse-tree');
                    melisCoreTool.pending('#refresh-tree');
                    melisCoreTool.pending('#categorySelectLangFilter');
                    melisCoreTool.pending(this);
                    cmsCategoryTree.jstree(true).settings.core.data.data = [{name : "langlocale", value: langLocale},{name:"siteId", value : value}];
                    cmsCategoryTree.jstree(true).refresh();
                }
                
                if ( value !== "" ) {
                    $(".info-select-category-site-filter").fadeIn('medium');
                }
                else {
                    $(".info-select-category-site-filter").fadeOut('medium');
                }
        });

        // site filter
        $body.on('change',"#categorySelectLangFilter", function() {
            var value           = this.value,
                cmsCategoryTree = $(".melis-cms-category-select-tree"),
                siteId          = $("#categorySelectSiteFilter").val();

                if ( typeof( cmsCategoryTree.jstree(true).settings ) !== "undefined" ) {
                    melisCoreTool.pending('#categorySelectSiteFilter');
                    melisCoreTool.pending('#clear-search');
                    melisCoreTool.pending('#expand-tree');
                    melisCoreTool.pending('#category-select-search-tree');
                    melisCoreTool.pending('#collapse-tree');
                    melisCoreTool.pending('#refresh-tree');
                    melisCoreTool.pending('#categorySelectSiteFilter');
                    melisCoreTool.pending(this);
                    cmsCategoryTree.jstree(true).settings.core.data.data = [{name : "langlocale", value: value},{name:"siteId", value : siteId}];
                    cmsCategoryTree.jstree(true).refresh();
                }
        });
});