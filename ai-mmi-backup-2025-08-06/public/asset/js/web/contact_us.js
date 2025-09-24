var qindex = 1;
function iweb_self_func() {
    $(document).on('click', 'a.copytxt', function() {
        $('#inquiry').val($(this).data('txt'));
        $('#contact-form').submit();
    });

    iweb.form('#contact-form', 'json', null, function(response_data) {
        $('#inquiry').val('');
        if(iweb.isMatch(response_data.status, 200)) {
            $('#help-chat-panel').scrollTop($('#help-chat-panel')[0].scrollHeight);
            var dialog_group = '';
            if(iweb.isValue(response_data.message)) {
                dialog_group = '<div class="dialog reply">';
                dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(response_data.member_owner_avatar)+'\')"></div></div>';
                dialog_group += '<div class="name">'+(response_data.member_owner_name)+'</div>';
                dialog_group += '<div class="clearboth"></div>';
                dialog_group += '<div class="txt">'+(response_data.message)+'</div>';
                dialog_group += '</div><div class="clearboth"></div>';
                $('#help-chat-panel').append(dialog_group).each(function() {
                    $('#help-chat-panel').scrollTop($('#help-chat-panel')[0].scrollHeight); 
                    
                    setTimeout(function() {
                        dialog_group = '<div class="dialog ask">';
                        dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(response_data.ai_owner_avatar)+'\')"></div></div>';
                        dialog_group += '<div class="name">'+(response_data.ai_owner_name)+'</div>';
                        dialog_group += '<div class="clearboth"></div>';
                        if(iweb.isValue(response_data.next.answers)) {
                            dialog_group += '<div class="txt">'+(response_data.next.title);
                            if(iweb.isValue(response_data.next.answers)) {
                                dialog_group += '<div><ul>';
                                $.each(response_data.next.answers, function(key, value) {
                                    dialog_group += '<li><a class="copytxt" data-txt="'+value+'">'+value+'</a></li>';
                                });
                                dialog_group += '</ul></div>';
                            }
                            dialog_group += '</div>';
                        }
                        else {
                            dialog_group += '<div class="txt">'+(response_data.next.title)+'</div>';
                        }
                        dialog_group += '</div><div class="clearboth"></div>';
                        $('#help-chat-panel').append(dialog_group).each(function() {
                           $('#help-chat-panel').scrollTop($('#help-chat-panel')[0].scrollHeight); 
                        });
                    }, 1000);
                    
                    if(iweb.isValue(response_data.done)) {
                        setTimeout(function() {
                            dialog_group = '<div class="dialog ask">';
                            dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(response_data.ai_owner_avatar)+'\')"></div></div>';
                            dialog_group += '<div class="name">'+(response_data.ai_owner_name)+'</div>';
                            dialog_group += '<div class="clearboth"></div>';
                            dialog_group += '<div class="txt">'+(response_data.done)+'</div>';
                            dialog_group += '</div><div class="clearboth"></div>';
                            $('#help-chat-panel').append(dialog_group).each(function() {
                               $('#help-chat-panel').scrollTop($('#help-chat-panel')[0].scrollHeight); 
                            });
                        }, 1000);
                    }
                    
                    if(!iweb.isValue(response_data.next)) {
                        $('#contact-form').remove();
                    }
                });
            }
        }
        else {
            iweb.alert(response_data.message);
        }
    });
}

function iweb_self_layout() {
    var new_height = ($(window).height() - $('header.page-header').height());
    new_height = new_height - 380;
    $('#help-chat-panel').height(Math.max(0, parseInt(new_height)));
}