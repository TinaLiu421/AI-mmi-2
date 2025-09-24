function iweb_extra_func() {
    $('#searchform').submit(function() {
        var queryParameters = $(this).serialize().replace(/(&?)(([\w\-\d_]+)=)(&|$)/gi, '$1').replace(/(.*)(&)$/gi,'$1').split('&');
        if(iweb.isValue(queryParameters)) {
            queryParameters = queryParameters.join('&');
        }
        window.location.href = iweb.getUrl()+((iweb.isValue(queryParameters))?('?'+queryParameters):'');
        return false;
    });

    $('#advanced-searchform').submit(function() {
        $('#advanced-searchform #advanced_keywords').val($('#searchform #keywords').val());
        var queryParameters = $(this).serialize().replace(/(&?)(([\w\-\d_]+)=)(&|$)/gi, '$1').replace(/(.*)(&)$/gi,'$1').split('&');
        if(iweb.isValue(queryParameters)) {
            queryParameters = queryParameters.join('&');
        }
        window.location.href = iweb.getUrl()+((iweb.isValue(queryParameters))?('?'+queryParameters):'');
        return false;
    });


    $(document).on('click', 'button.btn-show-advanced', function() {
        $('div.advanced-search').toggle();
    });

    $(document).on('change', 'table.list #select_all', function() {
        if($(this).is(':checked')) {
            $(this).closest('table.list').find('input[type="checkbox"].list_index').prop('checked', false).trigger('click');
        }
        else {
            $(this).closest('table.list').find('input[type="checkbox"].list_index').prop('checked', true).trigger('click');
        }
    });
    
    $(document).on('change', 'table.list input[type="checkbox"].list_index', function() {
        if(!$(this).is(':checked')) {
            $('table.list #select_all').prop('checked', false);
            $('table.list #select_all').parent().removeClass('checked');
        }
    });

    $(document).on('click','div.t-list a.sorting', function() {
        if($('div.advanced-search').is(':visible')){
            if(iweb.isMatch($('#sorting').val(), $(this).data('value'))) {
                $('#advanced-searchform #sorting').val('');
            }
            else {
                $('#advanced-searchform #sorting').val($(this).data('value'));
            }
            $('#advanced-searchform').submit();
        }
        else {
            if(iweb.isMatch($('#sorting').val(), $(this).data('value'))) {
                $('#searchform #sorting').val('');
            }
            else {
                $('#searchform #sorting').val($(this).data('value'));
            }
            $('#searchform').submit();
        }
    });

    $(document).on('click', 'div.t-list button.btn-delete-item', function() {
        var find_ids = '';
        $.each($('table.list input[name="list_index[]"]:checked'),function() {
            if(find_ids!='') {
                find_ids += ',';
            }
            find_ids += $(this).val()
        });

        if(find_ids!='') {
            iweb.confirm(_page_global_lang['confirm_delete_all'],function(result) {
                if(result) {
                     iweb.post({
                        url: window.location.href,
                        values: {
                            _token: _token,
                            page_action: 'delete',
                            id: find_ids
                        }
                    },function() {
                        window.location.reload();
                    });
                }
            });
        }
    });
    
    $(document).on('click', 'div.t-list button.btn-void-item', function() {
        var find_ids = '';
        $.each($('table.list input[name="list_index[]"]:checked'),function() {
            if(find_ids!='') {
                find_ids += ',';
            }
            find_ids += $(this).val()
        });

        if(find_ids!='') {
            iweb.confirm(_page_global_lang['confirm_void_all'],function(result) {
                if(result) {
                     iweb.post({
                        url: window.location.href,
                        values: {
                            _token: _token,
                            page_action: 'void',
                            id: find_ids
                        }
                    },function() {
                        window.location.reload();
                    });
                }
            });
        }
    });

    // change enable
    $(document).on('click','div.t-list a.set-disabled', function() {
        iweb.post({
            url: window.location.href,
            values: {
                _token: _token,
                page_action: 'disabled',
                id: $(this).data('id')
            }
        },function() {
            window.location.reload();
        });
    });

    // switch seq
    $(document).on('change','div.t-list select.seq_number', function() {
        var page_id = $(this).data('id');
        var from_seq = parseInt($(this).data('offet'));
        var to_seq = parseInt($(this).val());
        if(from_seq != to_seq) {
            iweb.post({
                url: window.location.href,
                values: {
                    _token: _token,
                    page_action: 'seq',
                    id: page_id,
                    from_seq: from_seq,
                    to_seq: to_seq
                }
            },function() {
                window.location.reload();
            });
        }
    });
    
    if($('div.t-list').length > 0) {
        $('table.list').basictable({ 
            breakpoint: 1024
        });
    }
}