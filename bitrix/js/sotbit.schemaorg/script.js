i = 0;
$(document).ready(function(){
    $('.select_entity').live("change",function(){
        if($(this).val() != '')
        {
            getHtmlEntity($(this).val(), this);
            $(this).closest("tr").find("td:first").addClass("blue-text-left-select");
        }
        else
        {
            $(this).closest('tr').next('tr').find('td:eq(0)').html('');
            $(this).closest("tr").find("td:first").removeClass("blue-text-left-select");
            if( $(this).next().length != 0 ) {
                $(this).closest("td").find(".add-more").css("display", "none");
            }
        }
    });

    $('.add-more').live("click", function () {
        obj = $(this).closest('tr').next().children('td').children('table');
        num = $(obj[obj.length - 1]).find('input').attr('name');
        num = num.match(/\[\d+\]/g);
        num = num[num.length - 1].slice(1, -1);
        getHtmlEntityMultiple(num, $(this).prev().val(), $(this).prev());
    })

    $('.remove-this-entity').live("click", function () {
        if($(this).closest("table").closest("td").children('table').length == 1){
            $(this).closest("table").closest("tr").prev().find(".add-more").css("display", "none");
            $(this).closest("table").closest("tr").prev().find("td:first").removeClass("blue-text-left-select");
            $(this).closest("table").closest("tr").prev().find("select").val(1);
        }
        $(this).closest('table').remove();
    })

    $('#general_tab_edit_table .exceptions').each(function (index, value) {
        console.log($(value).val());
        if($(value).val() == '' || $(value).val().match(/^\/[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*\/*[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*$/i) == null)
            $(value).remove();
    });


});
$('#tr_ENTITIES_BLOCK #tr_ENTITIES_BLOCK .heading').live('click', function () {
    $(this).closest("table").css("display", "block");
    $(this).closest("table").find(".adm-detail-file-row").nextAll("tr").not(".tr-btn-remove-this-entity").toggleClass("hiden");
    $(this).closest("table").find(".heading td").toggleClass("arroy-right");
})

var getHtmlEntity = function($entityName = '', $obj = '') {
    BX.ajax({
        url: '/bitrix/admin/sotbit.schema_ajax.php',
        data: {'entity':$entityName, 'key':$($obj).attr('name')},
        method: 'POST',
        dataType: 'json',
        timeout: 30,
        async: true,
        processData: false,
        scriptsRunFirst: false,
        emulateOnload: false,
        start: true,
        cache: false,
        onsuccess: function(data){
            data = BX.parseJSON(data);
            if(typeof data.html != 'undefined' )
            {
                if($(data.html).find('.adm-detail-content-item-block').html()) {
                    $($obj).closest('tr').next('tr').find('td:eq(0)').html($(data.html).find('.adm-detail-content-item-block').html());
                    $($obj).closest("td").find(".add-more").css("display", "inline-block");
                }
                else
                {
                    $($obj).closest('tr').next('tr').find('td:eq(0)').html('');
                    if( $($obj).closest("td").find(".add-more").next().length != 0 ) {
                        $($obj).closest("td").find(".add-more").css("display", "none");
                        $($obj).closest("td").find("td:first").removeClass("blue-text-left-select");
                    }
                }

                $($obj).closest('tr').next('tr').find('td:eq(0)').find('.select_entity').bind('change', function(){
                    if($(this).val() != '')
                        getHtmlEntity($(this).val(), this);
                    else
                    {
                        $(this).closest('tr').next('tr').find('td:eq(0)').html('');
                        if( $($obj).closest("td").find(".add-more").next().length != 0 ) {
                            $($obj).closest("td").find(".add-more").css("display", "none");
                            $($obj).closest("td").find("td:first").removeClass("blue-text-left-select");
                        }
                    }
                });
            } else {

            }
        },
        onfailure: function(){
            console.log('error');
        }
    });
}

var getHtmlEntityMultiple = function(num, $entityName = '', $obj = '') {
    key = $($obj).attr('name') + "[" + num + "]";
    BX.ajax({
        url: '/bitrix/admin/sotbit.schema_ajax.php',
        data: {'entity':$entityName, 'key':key},
        method: 'POST',
        dataType: 'json',
        timeout: 30,
        async: true,
        processData: false,
        scriptsRunFirst: false,
        emulateOnload: false,
        start: true,
        cache: false,
        onsuccess: function(data){
            data = BX.parseJSON(data);
            if(typeof data.html != 'undefined' )
            {
                if($(data.html).find('.adm-detail-content-item-block').html() && $(document).find('#'+$entityName+'_edit_table').closest('#tr_ENTITIES_BLOCK')) {
                    $($obj).closest('tr').next('tr').find('td:eq(0)').append($(data.html).find('.adm-detail-content-item-block').html());
                }
                else
                    $($obj).closest('tr').next('tr').find('td:eq(0)').html('');

                $($obj).closest('tr').next('tr').find('td:eq(0)').find('.select_entity').bind('change', function(){
                    if($(this).val() != '')
                        getHtmlEntity($(this).val(), this);
                    else
                        $(this).closest('tr').next('tr').find('td:eq(0)').html('');
                });
            } else {

            }
        },
        onfailure: function(){
            console.log('error');
        }
    });
}

