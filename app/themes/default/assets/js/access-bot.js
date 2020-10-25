
$(window).load(function() {
    setTimeout(function(){
        $('#Phone').val('');
        $('#Login').val('');
        $('#Password').val('');
        $('#StaffId').val('').trigger('change');
        $('#Role').val(0).trigger('change');
    }, 500);

});

$( document ).ready(function() {

    $('select#StaffId').select2({
        placeholder: "Выберите сотрудника"
    });

    $('select#Role').select2({
        placeholder: "Выберите роль"
    });
    var submit_id=false;
    var submit_phone=false;
    var auth = true;

    $('#Role').on('select2:select', function (e) {
        let data = e.params.data;
        (data.id==1?$('.auth-check').removeClass('hidden'):$('.auth-check').addClass('hidden'));
    });

    $('#Auth').change(function() {
        auth = ($('#Auth').is(":checked")?(!empty($('#Login').val()) && !empty($('#Password').val())):true);

        let mode = $("input[type='submit']").attr('data-mode');
        let staff_id = $('#StaffId').val();
        let phone = $('#Phone').val();
        let submit_el = $("input[type='submit']");

        if(this.checked) {
            $('.auth-form').removeClass('hidden');
        } else {
            $('.auth-form').addClass('hidden')
            $('#Login').val('');
            $('#Password').val('');
        }

        if(staff_id && phone.length==10 && (mode=='create'?(submit_phone && submit_id):1) && auth) { submit_el.removeAttr("disabled"); } else { submit_el.attr("disabled", true); }
    });

    $('#Password').bind("change keyup input",function() {
        auth = ($('#Auth').is(":checked")?(!empty($('#Login').val()) && !empty($('#Password').val())):true);

        let mode = $("input[type='submit']").attr('data-mode');
        let staff_id = $('#StaffId').val();
        let phone = $('#Phone').val();
        let submit_el = $("input[type='submit']");

        if(staff_id && phone.length==10 && (mode=='create'?(submit_phone && submit_id):1) && auth) { submit_el.removeAttr("disabled"); } else { submit_el.attr("disabled", true); }
    });

    $('#Login').bind("change keyup input",function() {
        auth = ($('#Auth').is(":checked")?(!empty($('#Login').val()) && !empty($('#Password').val())):true);

        let mode = $("input[type='submit']").attr('data-mode');
        let staff_id = $('#StaffId').val();
        let phone = $('#Phone').val();
        let submit_el = $("input[type='submit']");

        if(staff_id && phone.length==10 && (mode=='create'?(submit_phone && submit_id):1) && auth) { submit_el.removeAttr("disabled"); } else { submit_el.attr("disabled", true); }
    });

    $('#StaffId').bind("change keyup input",function() {
        auth = ($('#Auth').is(":checked")?(!empty($('#Login').val()) && !empty($('#Password').val())):true);

        submit_id = 1;
        let submit_el = $("input[type='submit']");
        let mode = $("input[type='submit']").attr('data-mode');
        let tr = $(".table>tbody tr");
        let staff_id = $(this).val();
        let phone = $('input#Phone').val();

        tr.show();
        let staff = staffs.find(o => o.id === staff_id);
        if(staff_id) {
            tr.each(function (i, elem) {
                let id = $(this).find("td[data-key='StaffId']").text();
                ((staff_id !== id)?$(this).closest("tr").hide():'');
                ((staff_id === id)?submit_id=0:'');
            });
        }

        if(staff_id && phone.length==10 && (mode=='create'?(submit_phone && submit_id):1) && auth) { submit_el.removeAttr("disabled"); } else { submit_el.attr("disabled", true); }
    });

    $('input#Phone').on('change keyup paste', function () {
        auth = ($('#Auth').is(":checked")?(!empty($('#Login').val()) && !empty($('#Password').val())):true);

        submit_phone = 1;
        let submit_el = $("input[type='submit']");
        let mode = $("input[type='submit']").attr('data-mode');
        let tr = $(".table>tbody tr");

        let staff_id = $('#StaffId').val();
        var required = '9';
        var normalphone = this.value.replace(/[^0-9]/g, '');
        if(normalphone.substring(0, 1)==='7') { normalphone = normalphone.substring(1, normalphone.length); if(normalphone.substring(0, 1)==='7') { normalphone = normalphone.substring(1, normalphone.length); } }
        if(normalphone.substring(0, 1)==='8') { normalphone = normalphone.substring(1, normalphone.length); if(normalphone.substring(0, 1)==='8') { normalphone = normalphone.substring(1, normalphone.length); } }
        if(normalphone.substring(0, 2)==='89') { normalphone = normalphone.substring(1, normalphone.length); if(normalphone.substring(0, 1)==='8') { normalphone = normalphone.substring(1, normalphone.length); } }
        if(normalphone.substring(0, 1)!=='9' && normalphone.length > 0) {  console.log('нет 9 впереди'); normalphone = required.concat(normalphone); }
        this.value = normalphone.substring(0, 10);
        let value = this.value;

        tr.show();
        if(value.length==10) {
            tr.each(function (i, elem) {
                let phone = $(this).find("td[data-key='Phone']").text();
                ((value !== phone)?$(this).closest("tr").hide():'');
                ((value === phone)?submit_phone=0:'');
            });
        }

        if(value.length==0) {
            $('#StaffId').change();
        }
        if(staff_id && value.length==10 && (mode=='create'?(submit_phone && submit_id):1) && auth) { submit_el.removeAttr("disabled"); } else { submit_el.attr("disabled", true); }
    });

    $("input[type='button']").click(function(e) {
        $("#Phone").val("");
        $("#Phone").change();
        $("input[type='submit']").attr('value', 'Создать новый');
        $("input[type='button']").addClass('hidden');

        $('#StaffId').prop("disabled", false);

        $('#StaffId').val('').trigger('change');
        $('#Role').val(0).trigger('change');

        $("input[type='submit']").attr('data-mode', 'create');

        $('#Auth').prop("checked", false);
        $('.auth-check').addClass('hidden');
        $('.auth-check span').text($('.auth-check span').attr('data-text'));
    });


    $("span[data-mode='edit']").click(function(e) {
        e.preventDefault();
        let current_ = $(this);

        let id = current_.closest("tr").find("td[data-key='StaffId']").text();
        let phone = current_.closest("tr").find("td[data-key='Phone']").text();
        let role = current_.closest("tr").find("td[data-key='Role']").attr('data-value');

        $("input[type='submit']").attr('data-mode', 'edit');
        $("input[type='submit']").attr('value', 'Обновить');
        $("input[type='button']").removeClass('hidden');

        $('#StaffId').prop("disabled", true);

        $('#Auth').prop("checked", false);
        if(role==1) {
            $('.auth-check').removeClass('hidden');
            $('.auth-check span').text('Изменить ' + $('.auth-check span').attr('data-text'));
        } else {
            $('.auth-check').addClass('hidden');
            $('.auth-check span').text($('.auth-check span').attr('data-text'));
        }
        console.log('test');

        $('#Phone').val(phone);
        $('#StaffId').val(id).trigger('change');
        $('#Role').val(role).trigger('change');
    });

    $('form').submit(function(e) {
        let mode = $("input[type='submit']").attr('data-mode');
        e.preventDefault();
        $.ajax({
            url: (mode=='create'?'api/access-bot/add':'api/access-bot/edit'),
            type: 'POST',
            data:{
                id: $("#StaffId").val(),
                phone: $("#Phone").val(),
                role: $("#Role").val(),
                auth: $('#Auth').is(":checked"),
                login: $('#Login').val(),
                password: $('#Password').val()
            },
            success: function (response) {
                //location.reload();
            },
            error: function () {
                //location.reload();
            }
        });
    });

    $("span[data-mode='delete']").click(function(e) {
        e.preventDefault();
        $.ajax({
            url: "api/access-bot/delete",
            type: 'POST',
            data:{
                id: $(this).closest("tr").find("td[data-key='StaffId']").text()
            },
            success: function (response) {
                location.reload();
            },
            error: function () {
                location.reload();
            }
        });
    });

});