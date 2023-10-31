
function replaseBasketTop() {
    $.ajax({
        url: '/ajax/basket.php',
        type: 'get',
        success: function (data) {
            $('.header__cart').replaceWith(data);
        }
    })
}
function addToBasket2(idel, quantity,el) {
    $href = "/ajax/add.php?id="+idel;
    var _result = true;
    $.ajax({
        url: $href + '&quantity=' + quantity,
        type: 'get',
        success: function (data) {
            if (data == 'Товар успешно добавлен в корзину') {
                replaseBasketTop();
                alertify.success(data);
            } else {
                alertify.error(data);
                _result = false;
            }
        }
    })

    if(el){
        $(el).text('Перейти');
        $(el).attr('onclick','window.location.href="/personal/cart/"');
    }


    return _result;
}

function setCupon(){
    var numCupon = $('#coupon').val();
    if(numCupon){
        $.ajax({
            type: "GET",
            url: "/ajax/set_cupon.php?cupon="+numCupon,
            success: function(msg){
                if(msg)
                {
                    console.log(msg);
                    UpdateBigBasket();
                    alertify.success("Купон активирован!");
                }
                else
                {
                    alertify.error("Купон не найден");
                }
            }
        });
    }else{
        alertify.error('Введите номер купона!');
        return false;
    }

}

function inputQuntly(max,count,id){
    if(count < 1){
        $('.quantity#'+id+' input').val(1);
        alertify.error("Запрашиваемое кол-во. На складе нет");
        return false;
    }
    if(count > max){
        $('.quantity#'+id+' input').val(max);
        alertify.error("Запрашиваемое кол-во превышает остаток. На складе: " + max);
        return false;
    }else{
        var data="id="+id+"&quant="+count;
        ChangeCount(data);
    }
}


function basketPlus(max,count,id, ratio){
    var increm = parseInt(count) + parseInt(ratio);

    if(increm > max){
        $('.quantity#'+id+' input').val(max - ratio);
        alertify.error("Запрашиваемое кол-во превышает остаток. На складе: " + max);
        return false;
    }else{
        var data="id="+id+"&quant="+increm;
        ChangeCount(data);
    }
}

function basketMinus(max,count,id,ratio){
    var increm = parseInt(count) - parseInt(ratio);

    if(increm < 1){
        $('.quantity#'+id+' input').val(ratio);
        alertify.error("Запрашиваемое кол-во. На складе нет");
        return false;
    }else{
        var data="id="+id+"&quant="+increm;
        ChangeCount(data);
    }
}

function ChangeCount(data)
{
    $.ajax({
        type: "GET",
        url: "/ajax/change_count.php",
        data:data,
        success: function(msg){
            if(msg!="error")
            {
                 UpdateBigBasket();
            }
            else
            {
                alertify.error("");
            }
        }
    });
}
function UpdateBigBasket(){
    $.ajax({
        type: "GET",
        url: "/ajax/big_basket.php",
        data:"",
        success: function(msg){
            if(msg!="error")
            {
                $(".page_content").html(msg);
            }
            else
            {
                alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
            }
        }
    });
}

function deleteBasket(){
    $.ajax({
        type: "GET",
        url: "/ajax/delete_all_basket.php",
        data:"",
        success: function(msg){
            if(msg!="error")
            {
                UpdateBigBasket();
            }
            else
            {
                alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
            }
        }
    });
    return false;
}

$(function(){

    $('.catalog-sections-text').readmore({
        speed: 75,
        maxHeight: 100,
        moreLink: '<a href="#" style="border-bottom:snow;padding: 0 10px;">Подробнее...</a>',
        lessLink: '<a href="#" style="border-bottom:snow;padding: 0 10px;">Скрыть</a>'
    });
    $('.col-txt > .catalog-sections-text-hidden').readmore({
        speed: 75,
        maxHeight: 130,
        moreLink: '<a href="#" style="border-bottom:snow">Подробнее...</a>',
        lessLink: '<a href="#" style="border-bottom:snow">Скрыть</a>'
    });

    $('.category__show').click(function(){
       var than = $(this);
        than.parent().find('.toggle_product_no').slideToggle();
        return false;
    });

    $('.show_brand').click(function(){
        var than = $(this);
        than.closest('.brand-list').find('.brand-hidden').slideToggle({
            start : function () {
                if ($(this).is(':visible'))
                    $(this).css('display','inline-block');
            }
        });
        return false;
    });


	  $('.ym-goal-subscribe-price').submit(function(e) {
        var $form = $(this);
        $.ajax({
          type: $form.attr('method'),
          url: $form.attr('action'),
          data: $form.serialize()
        }).done(function(data) {
          alertify.success("Подписка оформлена!");
		  $form[0].reset();
        }).fail(function() {
          alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
        });
        //отмена действия по умолчанию для кнопки submit
        e.preventDefault();
      });

});

