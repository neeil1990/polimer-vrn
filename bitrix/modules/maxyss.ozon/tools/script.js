function order_to_ship_add(orderId = 0, items) {


    let table = '<div class="new-row"><table class="row-table"><tr><td width="300px">'+BX.message('MAXYSS_OZON_PRODUCT')+'</td><td>'+BX.message('MAXYSS_OZON_COUNTE')+'</td><td>'+BX.message('MAXYSS_OZON_PRICE')+'</td><td>'+BX.message('MAXYSS_OZON_SIZE')+'</td></tr>';
    $.each(items, function (index, arValue) {
        table += '<tr><td><input name="item_id" type="hidden" value="'+arValue.ID+'">'+arValue.NAME+'</td><td><input name="quantity" type="number" value="'+arValue.QUANTITY+'" disabled></td><td>'+arValue.PRICE+'</td><td>'+arValue.DIMENSIONS['HEIGHT']+' x '+arValue.DIMENSIONS['LENGTH']+' x '+arValue.DIMENSIONS['WIDTH']+'</td></tr>';
    });
    table += '</table></div>';

    let DialogOrderAdd = new BX.CDialog({
        title: BX.message('MAXYSS_OZON_SHIP_ORDER_ADD_TITLE_DIALOG'),
        content: '<span style="font-style: italic">beta</span><br><br><span style="color: #003eff">'+BX.message('MAXYSS_OZON_SHIP_ORDER_ADD_TEXT_DIALOG')+'</span><br><br><div class="answer_order" id="table_controller">'+table+'<button class="add-row-but">'+BX.message('MAXYSS_OZON_ADD_SHIP')+'</button><button class="escape-rows">'+BX.message('MAXYSS_OZON_CANCEL')+'</button></div><br><br><div style="font-size: 15px; font-weight: bold;" class="answer_order_o"></div>',
        icon: 'head-block',
        resizable: true,
        draggable: true,
        height: '400',
        width: '800',
        buttons: ['<input type="button" name="ship_order" value="'+BX.message('MAXYSS_OZON_SHIP_ORDER')+'" id="ship_order" class="adm-btn-save">', BX.CDialog.btnClose, ]
    });
    DialogOrderAdd.Show();
    let tableBlock = DialogOrderAdd.PARAMS.content.querySelector('.answer_order');
    let params = {
        'block': tableBlock,
        'items': items,
        'dialog': DialogOrderAdd,
        'orderId': orderId,
    };
    new tableController(params);
}

function edit_value() {
    $('#value_ozon').prop('readonly', false);
}

function upload_ozon(id, iblock_id) {
    let wait = BX.showWait('upload_ozon');

    let elements = [];
    if(id > 0) {
        elements[0] = id;

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
            data: {
                action: 'upload_ozon',
                elements: elements,
                iblock_id: iblock_id
            },
            success: function (data) {
                let obj = $.parseJSON(data);

                if (obj.success) {
                    let DialogOzon = new BX.CDialog({
                        title: BX.message('MAXYSS_OZON_WAIT'),
                        content: '<br><div class="answer_title">' + BX.message('OZON_MAXYSS_UPLOAD_PRODUCT_SUCCESS') + '<br>' + obj.error + '</div><br>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '200',
                        width: '500',
                        buttons: [BX.CDialog.btnClose]
                    });
                    DialogOzon.Show();
                    // alert(BX.message('OZON_MAXYSS_UPLOAD_PRODUCT_SUCCESS') + '<br>' + obj.error);
                } else {
                    alert(BX.message('MAXYSS_OZON_POSTING_ELEMENT_ERROR'));
                }
            },
            error: function (xhr, str) {
                alert('An error has occurred: ' + xhr.responseCode);
            }
        });
    }else{
        alert(BX.message('MAXYSS_OZON_NO_ELEMENTS_SELECTED'));
    }
    BX.closeWait('upload_ozon', wait);

}

function upload_ozon_list(ids, iblock_id) {
    let wait = BX.showWait('upload_ozon');
    let elements = [];
    if(ids.length > 0){

        for(let i = 0;i<ids.length; i++){
                elements[i] = ids[i]
        }
        if(elements.length > 0) {
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                data: {
                    action: 'upload_ozon',
                    elements: elements,
                    iblock_id: iblock_id
                },
                success: function (data) {
                    let obj = $.parseJSON(data);

                    if (obj.success) {
                        let DialogOzon = new BX.CDialog({
                            title: BX.message('MAXYSS_OZON_WAIT'),
                            content: '<br><div class="answer_title">' + BX.message('OZON_MAXYSS_UPLOAD_PRODUCT_SUCCESS') + '<br>' + obj.error + '</div><br>',
                            icon: 'head-block',
                            resizable: true,
                            draggable: true,
                            height: '200',
                            width: '500',
                            buttons: [BX.CDialog.btnClose]
                        });
                        DialogOzon.Show();
                        // alert(BX.message('OZON_MAXYSS_UPLOAD_PRODUCT_SUCCESS'));
                    } else {
                        alert(BX.message('MAXYSS_OZON_POSTING_ELEMENT_ERROR'));
                    }
                },
                error: function (xhr, str) {
                    alert('An error has occurred: ' + xhr.responseCode);
                }
            });
        }
        else
        {
            alert(BX.message('MAXYSS_OZON_NO_ELEMENTS_SELECTED'));
        }
    }else{
        alert(BX.message('MAXYSS_OZON_NO_ELEMENTS_SELECTED'));
    }
    BX.closeWait('upload_ozon', wait);
}

function order_to_ship(orderId = 0){
    var button = this;
    var table = $('#tbl_sale_order');
    var orders = [];

    if(orderId > 0){
        orders = [orderId];
    }
    else
    {
        orders = push_orders();
    }

    let ship_ok = confirm(BX.message('MAXYSS_OZON_ORDER_SHIP_INFO_TEXT'));
    if(ship_ok) {
        let DialogOrder = new BX.CDialog({
            title: BX.message('MAXYSS_OZON_WAIT'),
            content: '<br><div class="answer_title" style="display: none">' + orders.length + BX.message("MAXYSS_OZON_WAIT_TEXT") + '</div><div class="answer_order"></div><br>',
            icon: 'head-block',
            resizable: true,
            draggable: true,
            height: '200',
            width: '500',
            buttons: [BX.CDialog.btnClose]
        });

        setTimeout(function () {
            DialogOrder.Show();
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                data: {
                    orders: orders,
                    action: 'order_to_ship'
                },
                success: function (data) {
                    let obg_ship = $.parseJSON(data);
                    if (obg_ship.order) {
                        $.each(obg_ship.order, function (index, value) {
                            if (value['ERROR']) $('.answer_order').append(value['ERROR'] + '<br>');
                            if (value['SUCCESS']) $('.answer_order').append(value['SUCCESS'] + '<br>');
                        })
                    }
                    if (obg_ship.error) {
                        $('.answer_order').append(obg_ship.error + '<br>');
                    }
                },
                error: function (xhr, str) {
                    alert('An error has occurred: ' + xhr.responseCode);
                }
            });
        }, 200);
    }
    return false;
}

function print_label_ozon(orderId = 0){
    var button = this;
    var orders = [];
    var table = $('#tbl_sale_order');

    if(orderId > 0){
        orders = [orderId];
    }
    else
    {
        orders = push_orders();
    }

    setTimeout(function () {
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
            data: {
                orders: orders,
                action: 'print_label_ozon'
            },
            success: function(data) {
                var obj=$.parseJSON(data);
                if(obj.success)
                    printJS('/upload/package-label.pdf?' + parseInt(new Date().getTime()/1000));
                else
                    alert(obj.error);
            },
            error:  function(xhr, str){
                alert('An error has occurred: ' + xhr.responseCode);
            }
        });
    }, 200);

    return false;
}

function print_act(id,warehouse) {
    $.ajax({
        type: 'GET',
        url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
        data: {
            action: 'get_documents',
            ozon_id: id,
            warehouse_id: warehouse
        },
        success: function(data) {
            var obj=$.parseJSON(data);

            if(obj.error){
                var error_mess = obj.error;
                if(error_mess.code === "NOT_FOUND_IN_SORTING_CENTER")
                    alert(BX.message('MAXYSS_OZON_NOT_FOUND_IN_SORTING_CENTER'));
                else if(error_mess.code === "INCLUDES_NOT_PACKAGED_POSTINGS")
                    alert(BX.message('MAXYSS_OZON_INCLUDES_NOT_PACKAGED_POSTINGS'));
                else
                    alert(BX.message('MAXYSS_OZON_PACKAGE_TIME_ALREADY_PASSED'));
            }else{
                var wait = BX.showWait('btnPrintOzon');
                var task_int = setInterval(function () {
                    $.ajax({
                        type: 'GET',
                        url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                        data: {
                            action: 'check_docs',
                            task_id: obj.id,
                            ozon_id: id
                        },
                        success: function(data_task_int) {
                            var task_status=$.parseJSON(data_task_int);

                            if(task_status.status === 'in_process'){
                                // dokumenty` v protcesse formirovaniia
                            }
                            else if(task_status.status === 'ready'){
                                clearInterval(task_int);
                                BX.closeWait('btnPrintOzon', wait);
                                $.ajax({
                                    type: 'GET',
                                    url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                                    data: {
                                        action: 'get_pdf',
                                        task_id: obj.id,
                                        ozon_id: id
                                    },
                                    success: function(data_pdf) {
                                        var obj=$.parseJSON(data_pdf);
                                        if(obj.success)
                                            printJS('/upload/package-label.pdf?' + parseInt(new Date().getTime()/1000));
                                        else
                                            alert(obj.error);
                                    },
                                    error:  function(xhr, str){
                                        alert('Error: ' + xhr.responseCode);
                                    }
                                });
                            }else{
                                clearInterval(task_int);
                                alert(BX.message('MAXYSS_OZON_PACKAGE_ERROR'));

                            }
                        },
                        error:  function(xhr, str){
                            alert('Error: ' + xhr.responseCode);
                        }
                    });
                }, 1000)
            }
        },
        error:  function(xhr, str){
            alert('An error has occurred: ' + xhr.responseCode);
        }
    });
}

function push_orders() {
    var orders = [];
    if($('#CRM_ORDER_LIST_V12_table')){
        $('#CRM_ORDER_LIST_V12_table input[name="ID[]"]:checked').each(function (index, value) {
            orders.push(parseInt($(value).val()));
        });
    }
    if($('#tbl_sale_order')){
        $('#tbl_sale_order input[name="ID[]"]:checked').each(function (index, value) {
            orders.push(parseInt($(value).val()));
        });
    }
    return orders;
}

if (!PropDialog && typeof PropDialog !== 'object') {
    var PropDialog = '';
}

function add_prop_sinc(attr_id,iblock_id, at_name) {
    if(attr_id > 0) {
        if (!PropDialog) {
            PropDialog = new BX.CDialog({
                title: BX.message('MAXYSS_OZON_WAIT'),
                content: '<br><div class="answer_title" style="display: none">' + BX.message("MAXYSS_OZON_WAIT_TEXT") + '</div><form id="form_prop_values"><div class="answer_prop"></div><div class="answer_prop_values"><br><br><br><table id="table_prop_values"></table></div></form><div id="result_sale"></div><br>',
                icon: 'head-block',
                resizable: true,
                draggable: true,
                height: '500',
                width: '500',
                buttons: ['<input type="button" onclick="save_prop();" name="save_prop" value="' + BX.message('MAXYSS_OZON_SAVE') + '" id="save_prop">', BX.CDialog.btnClose]
            });
        }


        if (iblock_id > 0){
            BX.ajax({
                method: 'POST',
                dataType: 'html',
                timeout: 30,
                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                data: {
                    action: 'add_prop_sinc',
                    attr_id: attr_id,
                    iblock_id: iblock_id
                },
                onsuccess: function (data) {
                    if (data != null) {
                        // if(data.success) {
                        //
                        PropDialog.Show();
                        PropDialog.SetTitle(at_name);

                        $('#form_prop_values').html(data);
                        // $('.answer_prop').html(BX.message('MAXYSS_OZON_IBLOCK_BASE') + '<br><br><br>' + data);
                        // $('#table_prop_values').html('');
                        // }

                    }
                },
                onfailure: function () {
                    new Error("No document for print");
                }
            });
        }else{
            $('#table_prop_values').html('');
            $('#save_prop').remove();
            PropDialog.Show();
            PropDialog.SetTitle(BX.message('MAXYSS_OZON_IBLOCK_SINC_TITLE'));
            $('.answer_prop').html(BX.message('MAXYSS_OZON_IBLOCK_SINC_TITLE_ERROR') + '<br><br><br>');
        }
    }
}

function get_prop_values(select,iblock_id,attr) {
    if(attr !== false) {
        let prop_id = '';
        prop_id = select.val();
        if (prop_id !== '') {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                timeout: 30,
                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                data: {
                    action: 'add_prop_sinc_values',
                    iblock_id: iblock_id,
                    prop_id: prop_id,
                },
                onsuccess: function (data) {
                    let prop_html = '';
                    $('#table_prop_values_' + iblock_id).html('');

                    // if (data.indexOf('select') > 0) {
                    if (data.prop_value.length > 0) {
                        $.each(data.prop_value, function (index, value) {

                            if(value.VALUE)
                                prop_html += '<tr><td><input type="hidden" value="' + value.ID + '" name="prop_value['+iblock_id+'][' + index + ']">' + value.VALUE + '</td>';
                            if(value.UF_NAME)
                                prop_html += '<tr><td><input type="hidden" value="' + value.UF_XML_ID + '" name="prop_value['+iblock_id+'][' + index + ']">' + value.UF_NAME + '</td>';

                            prop_html += '<td><select name="attr_value['+iblock_id+'][' + index + ']"><option value=""></option>';
                            $.each(attr, function (index1, value1) {
                                prop_html +=  '<option value="'+value1.id+'">'+value1.value+'</option>';
                            });
                            prop_html += '</select></td></tr>';

                        });
                        $('#table_prop_values_'  + iblock_id).append(prop_html);

                        // $.each(attr, function (index, value) {
                        //     $('#table_prop_values_'  + iblock_id).append('<tr><td><input type="hidden" value="' + value.id + '" name="attr_value['+iblock_id+'][' + index + ']">' + value.value + '</td><td>' + data.replace('[]', '['+index+']') + '</td></tr>');
                        // });
                    }else{
                        $('#table_prop_values_' + iblock_id).html('');
                    }
                },
                onfailure: function () {
                    new Error("Error");
                }
            });
        }else{
            $('#table_prop_values_' + iblock_id).html('');
        }
    }
}

function save_prop() {
    let form = document.querySelector('#form_prop_values');
    let formdata = new FormData(form);
    let data = [];
    formdata.append('action', 'save_prop_sinc_values');

    for (var [key, value] of formdata.entries()) {
        data[key] = value
    }
    BX.ajax({
        method: 'POST',
        dataType: 'json',
        timeout: 30,
        url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
        data: data,
        onsuccess: function (data) {
            if (data.error) {
                alert(data.error);
            }else{
                PropDialog.Close();
                $('#span_' + data.attr_id).addClass('green');
            }
        },
        onfailure: function () {
            new Error("No document for print");
        }
    });
}

function findParents(el, tag) {
    if(!el.tagName.toLowerCase != tag){
        while ((el = el.parentElement)){
            if(el.tagName.toLowerCase == tag){
                return el;
            }
        }
    } else{
        return el;
    }
};

(function (window) {
    'use strict';
    if (window.tableController)
        return;
    function bind(func, context) {
        return function () {
            return func.apply(context, arguments);
        };
    };

    window.tableController = function (arParams) {
        this.orderId = arParams.orderId;
        this.block = arParams.block;
        this.items = arParams.items;
        this.addRow = this.block.querySelector('.add-row-but');
        this.escapeRow = this.block.querySelector('.escape-rows');
        this.rowsToComplete = this.block.querySelectorAll('.new-row');
        this.document = document;
        this.options = {
            startEvent : ['mousedown', 'touchstart'],
            moveEvent : ['mousemove', 'touchmove'],
            endEvent:['mouseup', 'touchend'],
        };
        this.startEvent = this.options.startEvent.join('.' + this.block.id + ' ') + '.' + this.block.id;
        this.moveEvent  = this.options.moveEvent.join('.' + this.block.id + ' ') + '.' + this.block.id;
        this.endEvent   = this.options.endEvent.join('.' + this.block.id + ' ') + '.' + this.block.id;
        this.mouseDownHandler = $.proxy(this.mouseDownHandler, this);
        this.moveHandle = $.proxy(this.moveHandle, this);
        this.endHandle  = $.proxy(this.endHandle, this);
        this.newTableRow = false;
        this.hasRow = false;
        this.wasParent = false;
        this.nodes = [];
        this.data = [];
        this.dialog = arParams.dialog;
        this.init();
    };
    window.tableController.prototype = {
        init: function () {
            $(this.document).on(this.startEvent, '#' + this.block.id, this.mouseDownHandler);
            this.addRow.addEventListener('click', bind(this.createRow, this));
            this.escapeRow.addEventListener('click', bind(this.escapeRows, this));
            this.dialog.DIV.querySelector('#ship_order').addEventListener('click', bind(this.getData, this));
        },
        mouseDownHandler: function (e) {
            if(this.hasRow && e.target.tagName != 'INPUT' && this.getParent(e.target, 'table')){
                let row = this.getParent(e.target, 'tr');
                this.wasParent = row;
                let rowRects = row.getBoundingClientRect();
                let table = this.getParent(e.target, 'table');
                if(row && table){
                    let newTable = document.createElement('table');
                    newTable.classList.add('row-table');
                    let rowCopy = row.cloneNode(true);
                    newTable.appendChild(rowCopy);
                    newTable.style.opacity = '0.5';
                    newTable.style.left = rowRects.x + 'px';
                    newTable.style.top = rowRects.y + 'px';
                    newTable.style.width = rowRects.width + 'px';
                    newTable.style.position = 'fixed';
                    newTable.style.zIndex = 100000;
                    document.body.appendChild(newTable);
                    this.newTableRow = newTable;
                    this.newTableRow.querySelector('[name="quantity"]').value = 1;
                    if(this.newTableRow){
                        $(this.document).on(this.moveEvent,this.moveHandle);
                        $(this.document).on(this.endEvent, this.endHandle);
                    }
                }
            }
        },
        moveHandle: function(e){
            this.newTableRow.style.top = e.clientY+'px';
            for(let i = 0;i<this.rowsToComplete.length; i++){
                if(this.rowsToComplete[i]){
                    let blockBounds = this.rowsToComplete[i].getBoundingClientRect();
                    if(blockBounds.y<e.clientY && blockBounds.y+blockBounds.height>e.clientY){
                        this.rowsToComplete[i].classList.add('to-drop');
                    }else{
                        this.rowsToComplete[i].classList.remove('to-drop');
                    }
                }
            }
        },
        endHandle: function(e) {
            $(this.document).off(this.moveEvent, this.moveHandle);
            $(this.document).off(this.endEvent, this.endHandle);
            let reachTable = false;
            for(let i = 0;i<this.rowsToComplete.length; i++){
                if(this.rowsToComplete[i]){
                    let blockBounds = this.rowsToComplete[i].getBoundingClientRect();
                    if(blockBounds.y<e.clientY && blockBounds.y+blockBounds.height>e.clientY){
                        reachTable = true;
                        let parentQuant = this.wasParent.querySelector('[name="quantity"]');
                        if(this.rowsToComplete[i].classList.contains('new-row--empty')){
                            this.newTableRow.querySelector('[name="quantity"]').value = 1;
                            this.rowsToComplete[i].innerHTML = '';
                            this.rowsToComplete[i].classList.remove('new-row--empty');
                            this.rowsToComplete[i].appendChild(this.newTableRow);
                            this.newTableRow.style.position = '';
                            this.newTableRow.style.left = '';
                            this.newTableRow.style.top = '';
                            this.newTableRow.style.zIndex = '';
                            this.newTableRow.style.opacity = '';
                            this.newTableRow.style.width = '';
                            let th = document.createElement('tr');
                            th.innerHTML = '<tr><td width="300px">'+BX.message('MAXYSS_OZON_PRODUCT')+'</td><td>'+BX.message('MAXYSS_OZON_COUNTE')+'</td><td>'+BX.message('MAXYSS_OZON_PRICE')+'</td><td>'+BX.message('MAXYSS_OZON_SIZE')+'</td></tr>';
                            this.newTableRow.prepend(th);
                            if(parseInt(parentQuant.value)>1){
                                parentQuant.value = parseInt(parentQuant.value)-1;
                            }else{
                                this.wasParent.remove();
                            }
                        }else{
                            if(this.getParent(this.wasParent, 'div') == this.rowsToComplete[i]){
                                this.newTableRow.remove();
                                this.newTableRow = false;
                            }else{
                                let search = this.checkRow(this.rowsToComplete[i], this.newTableRow.querySelector('tr'));
                                if(search.length>0){
                                    this.setRowItem(search[0], this.rowsToComplete[i]);
                                }else{
                                    this.rowsToComplete[i].querySelector('table').appendChild(this.newTableRow.querySelector('tr'));
                                    if(parseInt(parentQuant.value)>1){
                                        parentQuant.value = parseInt(parentQuant.value)-1;
                                    }else{
                                        this.wasParent.remove();
                                    }
                                }
                            }
                        }
                        this.rowsToComplete[i].classList.remove('to-drop');
                        break;
                    }
                }
                this.rowsToComplete[i].classList.remove('to-drop');
            };
            if(!reachTable){
                this.newTableRow.remove();
                this.newTableRow = false;
            };
            this.checkBlocks();
        },
        createRow: function(e){
            let newRow = document.createElement('div');
            newRow.innerText = BX.message('MAXYSS_OZON_MOVE_PRODUCT');
            newRow.classList.add('new-row--empty');
            newRow.classList.add('new-row');
            e.target.parentNode.insertBefore(newRow, e.target);
            this.rowsToComplete = this.block.querySelectorAll('.new-row');
            this.hasRow = true;
            this.block.classList.add('drag_active');
        },
        checkRow: function(row, item){
            let itemVal = item.querySelector('[name="item_id"]').value;
            let rowItems = row.querySelectorAll('[name="item_id"]');
            let rowItemVals = [];
            for (let i = 0;i<rowItems.length;i++){
                rowItemVals.push(rowItems[i].value);
            }
            let search = rowItemVals.filter(function (num) {
               if(num == itemVal) return num;
            });
            return search;
        },
        setRowItem: function(itemNunm , row){
            let existingRow = row.querySelector('[value="'+itemNunm+'"]');
            existingRow = this.getParent(existingRow, 'tr');
            let existingRowQuantity = existingRow.querySelector('[name="quantity"]');
            let parentQuant = this.wasParent.querySelector('[name="quantity"]');
            if(parentQuant.value>1){
                parentQuant.value = parseInt(parentQuant.value)-1;
            }else{
                parentQuant.value = 0;
                this.wasParent.remove();
            }
            existingRowQuantity.value = parseInt(existingRowQuantity.value)+1;
            this.newTableRow.remove();
            this.newTableRow = false;
        },
        checkBlocks: function(){
            if(this.rowsToComplete.length>1){
                for(let i = 0;i<this.rowsToComplete.length;i++){
                    let trs = this.rowsToComplete[i].querySelectorAll('tr');
                    if(trs.length <= 1){
                        this.rowsToComplete[i].remove();
                    };
                };
            }
            this.rowsToComplete = this.block.querySelectorAll('.new-row');
            if(this.rowsToComplete.length<2){
                this.hasRow = false;
                this.block.classList.remove('drag_active');
            };
            // this.getData();
        },
        getData: function(){
            this.data = [];
            for(let i = 0;i<this.rowsToComplete.length; i++){
                this.data[i] = {};
                this.data[i].items = [];
                let items = this.rowsToComplete[i].querySelectorAll('tr:not(:first-child)');
                for(let o = 0;o<items.length;o++){
                    let id = items[o].querySelector('[name="item_id"]').value;
                    let quant = items[o].querySelector('[name="quantity"]').value;
                    this.data[i].items[o] = {};
                    this.data[i].items[o]['ID'] = id;
                    this.data[i].items[o]['QUANTITY'] = quant;
                };
            };

            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                data: {
                    orders: [this.orderId],
                    action: 'order_to_ship',
                    pakages: this.data
                },
                success: function(data) {
                    let obg_ship=$.parseJSON(data);
                    if(obg_ship.order){
                        $.each(obg_ship.order, function (index, value) {
                            if(value['ERROR']) $('.answer_order_o').append(value['ERROR'] + '<br>');
                            if(value['SUCCESS']) $('.answer_order_o').append(value['SUCCESS'] + '<br>');
                        })
                    }
                    if(obg_ship.error){
                        $('.answer_order_o').append(obg_ship.error + '<br>');
                    }
                },
                error:  function(xhr, str){
                    alert('An error has occurred: ' + xhr.responseCode);
                }
            });

        },
        getParent: function (el, tag) {
            if(!el.tagName.toLowerCase() != tag){
                while ((el = el.parentElement)){
                    if(el.tagName.toLowerCase() == tag){
                        return el;
                    }
                }
            } else{
                return el;
            }
        },
        escapeRows: function (e) {
            let res = confirm(BX.message('MAXYSS_OZON_ORDER_SHIP_DELETE'));
            if(res){
                this.rowsToComplete = this.block.querySelectorAll('.new-row');
                if(this.rowsToComplete.length>1){
                    for(let i = 1;i<this.rowsToComplete.length;i++){
                        this.rowsToComplete[i].remove();
                    }
                };
                let table = '<table class="row-table"><tr><td width="300px">'+BX.message('MAXYSS_OZON_PRODUCT')+'</td><td>'+BX.message('MAXYSS_OZON_COUNTE')+'</td><td>'+BX.message('MAXYSS_OZON_PRICE')+'</td><td>'+BX.message('MAXYSS_OZON_SIZE')+'</td></tr>';
                $.each(this.items, function (index, arValue) {
                    table += '<tr><td><input name="item_id" type="hidden" value="'+arValue.ID+'">'+arValue.NAME+'</td><td><input name="quantity" type="number" value="'+arValue.QUANTITY+'" disabled></td><td>'+arValue.PRICE+'</td><td>'+arValue.DIMENSIONS['HEIGHT']+' x '+arValue.DIMENSIONS['LENGTH']+' x '+arValue.DIMENSIONS['WIDTH']+'</td></tr>';
                });
                table += '</table>';
                this.block.querySelector('.new-row').innerHTML = table;
                this.hasRow = false;
                this.block.classList.remove('drag_active');
            }
        },
        send: function () {

        },
    }
})(window);

function downLoadId(lid) {
    let prop = $('#downLoadProp_'+lid).val();
    let message_download;
    let DialogLk;

    let btn_close = {
        title: BX.message('MAXYSS_OZON_DOWNLOAD_ID_OZON_CLOSE'),
        id: 'OZON_CLOSE',
        name: 'OZON_CLOSE',
        className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-close',
        action: function () {
            top.BX.WindowManager.Get().Close();
            DialogLk.DIV.parentNode.removeChild(DialogLk.DIV);
            // console.log(DialogLk);

        }
    };
    let btn_save = {
        title: BX.message('MAXYSS_OZON_DOWNLOAD_ID_OZON_RUN'),
        id: 'OZON_RUN',
        name: 'OZON_RUN',
        className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
        action: function () {
            // top.BX.WindowManager.Get().Close();

            if(prop !=='') {
                let wait_download_id = BX.showWait('downLoadProp_' + lid);
                get_mill_id(lid, prop, wait_download_id);
            }else{
                alert(BX.message('MAXYSS_OZON_DOWNLOAD_MESSAGE_FAIL'));
            }

        }
    };
    if(prop !==''){
        message_download = BX.message('MAXYSS_OZON_DOWNLOAD_MESSAGE_OK');
    }
    else{
        message_download = BX.message('MAXYSS_OZON_DOWNLOAD_MESSAGE_FAIL')
    }
    DialogLk = new BX.CDialog({
        title: BX.message('MAXYSS_OZON_DOWNLOAD_ID_OZON_TITLE_POPUP'),
        content: '<div id="download_answer">' + message_download + '</div>',
        icon: 'head-block',
        resizable: true,
        draggable: true,
        height: '400',
        width: '800',
        buttons: [btn_save, btn_close]
    });
    DialogLk.Show();
}

function get_mill_id_next(lid, prop, last_id, wait_download_id, step) {

    $.ajax({
        type: 'GET',
        url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
        data: {
            action: 'get_mill_id',
            site: lid,
            prop: prop,
            last_id: last_id,
        },
        success: function(data) {
            var IS_JSON = true;
            try {
                var obj = $.parseJSON(data);
            }
            catch (err) {
                IS_JSON = false;
            }
            if (IS_JSON) {
                console.log(obj);
                if(obj.go_run){
                    if(obj.last_id !== ''){
                        get_mill_id_next(lid, prop, obj.last_id, wait_download_id, step+1);
                        $('#download_answer').parent().append( "<strong> - - - - - -</strong>" );
                    }
                }
                else{
                    if(obj.mess === 'end'){
                        BX.closeWait('downLoadProp_'+lid, wait_download_id);
                        $('#download_answer').parent().append( "<br><strong>"+BX.message('MAXYSS_OZON_DOWNLOAD_MESSAGE_SUCCESS')+"</strong>" );


                    }
                }
            } else {
                alert('not valid json');
            }
        },
        error:  function(xhr, str){
            alert('Возникла ошибка: ' + xhr.responseCode);
        }
    });
}

function get_mill_id(lid, prop, wait_download_id) {
    $.ajax({
         type: 'GET',
         url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
         data: {
            action: 'get_mill_id',
            site: lid,
            prop: prop,
         },
         success: function(data) {
             var IS_JSON = true;
             try {
                 var obj = $.parseJSON(data);
             }
             catch (err) {
                 IS_JSON = false;
             }
             if (IS_JSON) {
                 console.log(obj);
                 if(obj.go_run){
                     if(obj.last_id){
                         get_mill_id_next(lid, prop, obj.last_id, wait_download_id, 0);
                     }
                 }
                 else{
                     if(obj.mess === 'end'){
                         BX.closeWait('downLoadProp_'+lid, wait_download_id);
                         $('#download_answer').parent().append( "<br><strong>"+BX.message('MAXYSS_OZON_DOWNLOAD_MESSAGE_SUCCESS')+"</strong>" );

                     }
                 }
             } else {
                 alert('not valid json');
             }
            },
         error:  function(xhr, str){
            alert('Возникла ошибка: ' + xhr.responseCode);
            }
      });
}