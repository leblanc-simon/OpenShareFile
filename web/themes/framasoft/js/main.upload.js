function loadProgress(value)
{
    $('.bar').css('width', value + '%');
    $('.progress-value').html('(' + value + '%)');
}


function humanSize(bytes)
{
    var i = 0;
    
    if (bytes >= 1024) {
        do {
            bytes = bytes / 1024;
            i++;
        } while (bytes > 1024);
    }

    return Math.max(bytes, 0.10).toFixed(1) + ' ' + js_human_sizes[i];
}


function appendFileInFilelist(file)
{
    var html = '<div class="file-to-upload hide"><i class="file-empty ' + file.type.replace('/', '_') + '"></i> ';
    html    += file.name + ' (' + humanSize(file.size) + ')';
    html    += '<i class="icon-trash delete-file" data-delete="' + file.ident + '"></i></div>';
    
    $('.list-files').append(html);
    $('.delete-file[data-delete="' + file.ident + '"]').parent().fadeIn();
    
    $('.delete-file[data-delete="' + file.ident + '"]').click(function() {
        if (data_to_upload == null) {
            return;
        }
        
        var ident = $(this).attr('data-delete');
        
        for (var i in data_to_upload.files) {
            if (ident == data_to_upload.files[i].ident) {
                break;
            }
        }
        
        data_to_upload.files.splice(i, 1);
        
        $(this).parent().fadeOut();
        
        if (data_to_upload.files.length == 0) {
            $('.btn-upload button').hide();
        }
    });
}


function showInfo(title, message)
{
    $('#modal-info > header > h3').html(title);
    $('#modal-info > .modal-body > p').html(message);
    $('#modal-info').modal();
}

$(document).ready(function(){
    // Disable default drag&drop's browser
    $(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });
    
    // Hide password and emails field
    $('.password, .emails, .btn-upload button').hide();
    
    // Show password field when protect is checked
    $('#form_protect').change(function(){
        if (this.checked == true) {
            $('.password').fadeIn();
            $('.password input').focus();
        } else {
            $('.password input').html('');
            $('.password').fadeOut();
            $('#form_crypt')[0].checked = false;
        }
    });
    
    // Show emails field when send is checked
    $('#form_send_by_mail').change(function(){
        if (this.checked == true) {
            $('.emails').fadeIn();
            $('.emails input')[0].focus();
        } else {
            $('.emails').fadeOut();
        }
    });
    
    // Show emails field when send is checked
    $('#form_crypt').change(function(){
        var is_protect = $('#form_protect')[0].checked;
        if (is_protect == false) {
            $('#form_protect').attr('checked', 'checked');
            $('#form_protect').change();
        }
    });
    
    var nb_file_manipulated = 0;
    
    // Activate fileupload
    $('#upload-form').fileupload({
        url: url_upload,
        fileInput: $('#upload-form input:file'),
        dropZone: $('#upload-form .drop'),
        maxNumberOfFiles: max_file,
        drop: function(e, data) {
            var nb_files = 0;
            if (data_to_upload != null) {
                nb_files = data_to_upload.files.length;
            }
            
            if (nb_files >= max_file) {
                showInfo(js_max_file_error_title, js_max_file_error_message);
                return false;
            }
            
            return true;
        },
        add: function (e, data) {
            nb_file_manipulated++;
            data.files[0].ident = nb_file_manipulated;
            
            if (data_to_upload == null) {
                data_to_upload = data;
            } else {
                if (data_to_upload.files.length >= max_file) {
                    showInfo(js_max_file_error_title, js_max_file_error_message);
                    return false;
                }
                data_to_upload.files.push(data.files[0]);
            }
            
            $('.btn-upload button').show();
            
            appendFileInFilelist(data.files[0]);
            
            return true;
        },
        progress: function(e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            
            loadProgress(progress);
        },
        done: function(e, data) {
            console.log('success');
            console.log(data);
            if (data.result && data.result.success == true) {
                window.location = data.result.url;
            } else if (data.result && data.result.success == false) {
                showInfo('', data.result.message);
            }
        },
        fail: function(e, data) {
            console.log('fail');
            console.log(data);
            showInfo('', data.result.message);
        }
    });
    
    
    $('#upload-form').submit(function(){
        if ($('#form_protect').checked == true && $.trim($('#form_password')) == '') {
            $('.password').addClass('error');
        } else {
            $('.password').removeClass('error');
        }
        
        if (data_to_upload != null && data_to_upload.files.length > 0) {
            var form_data = {};
            $('#upload-form').find('input').each(function(){
                if ($(this).attr('type') == 'checkbox' && this.checked == false) {
                    return;
                }
                form_data[$(this).attr('name')] = $(this).val();
                
            });
            
            data_to_upload.formData = form_data;
            
            $('.upload, .upload-bg').show();
            
            data_to_upload.submit();
        }
        
        return false;
    });
});