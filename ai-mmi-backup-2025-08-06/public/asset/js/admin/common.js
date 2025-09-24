function iweb_global_func() {
    // menu
    $(document).on('click', 'aside.left-menu > ul > li > a.parent', function() {
        var object = $(this);
        if(object.closest('li').find('ol').length > 0 ) {
            if(object.closest('li').find('ol').is(':visible')) {
                object.closest('li').find('ol').slideUp();
            }
            else {
                object.closest('li').find('ol').slideDown();
            }
        }
    });

    $(document).on('click', 'header.page-header > div.open > a', function() {
        if($('aside.left-menu').hasClass('show')) {
            $('aside.left-menu').removeClass('show');
        }
        else {
            $('aside.left-menu').addClass('show');
        }
    });
    
    if($('textarea[class="editor"]').length > 0 && (typeof tinymce !== 'undefined')) {
        $('textarea[class="editor"]').each(function() {
            $(this).wrapAll('<div class="tinymce-editor"></div>');
        });

        tinymce.init({
            selector: 'textarea[class="editor"]',
            language: ((iweb.default_language == 'en')?'en':'zh_TW'),
            body_class: 'iweb iweb-editor',
            width: 'auto',
            height: 360,
            menubar: false,
            branding: false,
            statusbar: false,
            plugins: [
                'advlist autolink lists link code colorpicker contextmenu',
                'fullscreen hr image link lists media',
                'pagebreak paste preview searchreplace spellchecker',
                'table textcolor wordcount'
            ],
            toolbar: 'fontselect fontsizeselect | formatselect | bold italic underline strikethrough subscript superscript | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table insert removeformat code preview fullscreen',
            fontsize_formats: "12px 14px 16px 18px 20px 22px 24px 28px 32px 36px 40px 44px 48px",
            content_css: ['asset/lib/base/iweb.min.css','asset/lib/tinymce4/editor.css'],
            content_style: '#tinymce { font-size:0.875rem;}',
            //relative_urls : false,
            remove_script_host : false,
            image_advtab: true,
            file_picker_callback: function(callback, value, meta) {
                tinymce.activeEditor.windowManager.open({
                    classes: 'filemanager',
                    url: _page_base_url+'/media_files?inline=1',
                    width: 600,
                    height: 480,
                    resizable : 'yes',
                    inline : 'yes',
                    close_previous : 'no'
                },
                {
                    oninsert: function(url) {
                        callback(url);
                    }
                });
                
                return false;
            },
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    }
    
    if($('input[class="datepicker"]').length > 0) {
        $.each($('input[class="datepicker"]'), function() {
            $(this).datetimepicker({
                timepicker: false,
                format: (iweb.isValue($(this).data('format'))?$(this).data('format'):'Y-m-d'),
                scrollMonth : false
            });
        });
    }
    
    if($('input[class="colorpicker"]').length > 0) {
        $('input[class="colorpicker"]').minicolors();
    }

    // lang tab
    if($('div.widget.fixed-top > div.tab-lang').length > 0 || $('div.widget.fixed-top > div.controls').length > 0) {
        $('main.page-body').addClass('expand');
        if($('div.widget.fixed-top > div.tab-lang').length > 0) {
            $('div.tab-lang > a').eq(0).addClass('current');
            $('div.'+$('div.tab-lang > a.current').data('target')).addClass('show');
            
            $('div.translation-tab').removeClass('show');
            $('div.'+($('div.tab-lang > a.current').data('target').replace('tab-content', 'translation-tab'))).addClass('show');
            
            $(document).on('click', 'div.tab-lang > a', function() {
                $('div.tab-lang > a').removeClass('current');
                $(this).addClass('current');
                $('div.tab-content').removeClass('show');
                $('div.'+$('div.tab-lang > a.current').data('target')).addClass('show');
                
                $('div.translation-tab').removeClass('show');
                $('div.'+($('div.tab-lang > a.current').data('target').replace('tab-content', 'translation-tab'))).addClass('show');
            });
            
            // translation
            $(document).on('click', 'button.btn-ts-translation', function() {
                if(iweb.isMatch($(this).data('type'), 't')) {
                    var tindex = $(this).data('tindex');
                    var sindex = $(this).data('sindex');
                    $('*[data-translation="'+tindex+'"]').each(function() {
                        var target_field_id = ($(this).attr('name').toString()).replace(/(\[\d+\])/g, '_'+sindex);
                        if($('*[id="'+target_field_id+'"]').length > 0) {
                            $('*[id="'+target_field_id+'"]').val($.t2s($(this).val())).trigger('input');
                            if (typeof tinymce !== 'undefined') {
                                if(iweb.isValue(tinymce.get(target_field_id))) {
                                    tinymce.get(target_field_id).setContent($.t2s($(this).val()));
                                }
                            }
                        }
                    });
                    $('a[data-target="tab-content-'+sindex+'"]').trigger('click');
                }
                else {
                    var tindex = $(this).data('tindex');
                    var eindex = $(this).data('eindex');
                    $('*[data-translation="'+tindex+'"]').each(function() {
                        var target_field_id = ($(this).attr('name').toString()).replace(/(\[\d+\])/g, '_'+eindex);
                        if($('*[id="'+target_field_id+'"]').length > 0) {
                            $('*[id="'+target_field_id+'"]').val(($(this).val())).trigger('input');
                            if (typeof tinymce !== 'undefined') {
                                if(iweb.isValue(tinymce.get(target_field_id))) {
                                    tinymce.get(target_field_id).setContent(($(this).val()));
                                }
                            }
                        }
                    });
                    $('a[data-target="tab-content-'+eindex+'"]').trigger('click');
                }
            });
        }
        else {
            $('div.tab-content').eq(0).addClass('show');
            $('div.expand-area div.tab-content').eq(0).addClass('show');
        }
    }
    else {
        $('div.tab-content').eq(0).addClass('show');
        $('div.expand-area div.tab-content').eq(0).addClass('show');
    }

    // preview meta image
    $(document).on('input', 'input.meta_image', function() {
        var extension = ($(this).val()).slice((($(this).val()).lastIndexOf('.') - 1 >>> 0) + 2);
        $(this).closest('div').find('div.meta-preview').remove();
        if($.inArray(extension.toLowerCase(),['jpg','jpeg','png']) >= 0) {
            $(this).closest('div').append('<div class="widget meta-preview" style="margin-top:10px;"><img src="'+($(this).val())+'"></div>');
        }
    });
    $('input.meta_image').each(function() {
        var extension = ($(this).val()).slice((($(this).val()).lastIndexOf('.') - 1 >>> 0) + 2);
        $(this).closest('div').find('div.meta-preview').remove();
        if($.inArray(extension.toLowerCase(),['jpg','jpeg','png']) >= 0) {
            $(this).closest('div').append('<div class="widget meta-preview" style="margin-top:10px;"><img src="'+($(this).val())+'"></div>');
        }
    });
   
    // list pagination
    if($('div.list-mypage').length > 0) {
        iweb.pagination('div.list-mypage');
    }
    
    // details page delete & edit
    $(document).on('click', 'button.btn-delete-target-item', function() {
        var find_ids = $(this).data('id');
        if(iweb.isValue(find_ids)) {
            iweb.confirm(_page_global_lang['confirm_delete_one'],function(result) {
                if(result) {
                     iweb.post({
                        url: window.location.href,
                        values: {
                            _token: _token,
                            page_action: 'delete',
                            id: find_ids
                        }
                    },function(response_data) {
                        window.location.href = (iweb.isValue(response_data.url))?response_data.url:document.referrer;
                    });
                }
            });
        }
    });
    
    $(document).on('click', 'button.btn-edit-target-item', function() {
        var url = window.location.href;
        window.location.href = url.replace(/(.*)(\/details\/)(\d+)(.*)/, '$1/edit/$3');
    });
}

function iweb_layout() {
    $('aside.left-menu.show').removeClass('show');
}