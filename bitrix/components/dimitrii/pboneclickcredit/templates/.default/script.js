;(function(window, document){
    'use strict';

    $(window).load(function(){
        
        if (window.pbcSettings.order) {
            window.pbcSettings.order = window.pbcSettings.order.map(function (order) {
                order.price = parseFloat(order.price);
                order.quantity = parseInt(order.quantity);
                return order
            });
        }

        $(document).on('click','.pb-sdk-pos-credit__btn',function () {

            if ($('input#fullName.pb-sdk-pos-credit__form-control').val()) {
                var ttName = $('input#fullName.pb-sdk-pos-credit__form-control').val();
                window.pbcSettings.fullName = ttName;
                window.pbcData['ORDER']['FIO'] = ttName;
            }

            if ($('input#phone.pb-sdk-pos-credit__form-control').val()) {
                var ttPhone = $('input#phone.pb-sdk-pos-credit__form-control').val();
                window.pbcSettings.phone = ttPhone;
                window.pbcData['ORDER']['PHONE'] = ttPhone;
            }
            console.log('click', window.pbcSettings);
        });

        $(document).on('click', '#pos-credit-one-click', function (event) {
            console.log('click');
            event.preventDefault(); // выключaем стaндaртную рoль элементa
            window.PBSDK.posCredit.mount('#pos-credit-container', window.pbcSettings);
            $("#modal-post-credit").modal('show');
        });

        window.PBSDK.posCredit.on('done', function(id) {
            window.pbcData['ORDER']['PAY_ID'] = id;
            $.ajax({
                url: window.pbcUrl,
                cache: false,
                data: window.pbcData,
                success: function (result) {
                    console.log(result);
                }
            });
        });

    });

})(window, document);