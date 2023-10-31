<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>

<div class="lk_block cl">
   <a href="?logout=yes" class="exit">Выйти</a>
   <div class="lk_leftbar">
      <a href="#" class="lk_sandwich">
         <span></span>
         <span></span>
         <span></span>
      </a>
      <h1 class="h1-lk">Личный кабинет</h1>
      <div class="welcome">
         Добро пожаловать,
         <? global $USER; ?>
         <span class="username"><? echo $USER->GetFullName(); ?></span>
         <span class="usermail"><? echo $USER->GetEmail(); ?></span>
      </div>
      <div class="block-menu cl">
         <a href="/personal/orders-list.php" class="menu-item ph active">История<br>заказов</a>
         <a href="/personal/info.php" class="menu-item pd">Персональные<br>данные</a>
         <a href="/personal/security.php" class="menu-item ss">Настройки<br>безопасности</a>
      </div>
   </div>
   <div class="lk_content">
      <div class="header">История заказов</div>

      <? foreach ($arResult['ORDERS'] as $arOrder): ?>

      <div class="ph__item">
         <div class="title cl">
            <div class="id">Заказ №<span class="num"><?=$arOrder['ORDER']['ID']?></span> от <span class="date"><?=$arOrder['ORDER']['DATE_INSERT_FORMATED']?></span></div>
            <a href="#" class="detail">Подробнее о заказе</a>
         </div>
         <div class="inner">
            <div class="status_options">
               <div class="pos status cl">
                  <div class="line"></div>
                  <span>Статус:</span>
                  <span class="val">
                     <?
                     if($arOrder['ORDER']['CANCELED'] == 'Y'){
                        print 'Заказ отменен';
                     }else{
                        if($arOrder['ORDER']['STATUS_ID'] == 'B'){
                           print 'Средства заблокированы';
                     }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'K'){
                           print 'Обрабатывается (ул. Богачева)';
                        }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'N'){
                           print 'Принят';
                        }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'P'){
                           print 'Оплачен';
                        }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'V'){
                           print 'Обрабатывается (ул. Шишкова)';
                        }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'z'){
                           print 'Обрабатывается (Монтажный проезд)';
                        }
                        elseif($arOrder['ORDER']['STATUS_ID'] == 'F'){
                           print 'Выполнен';
                        }
                     }
                     ?>
                  </span>
               </div>
               <div class="pos date cl">
                  <div class="line"></div>
                  <span>Дата формирование</span>
                  <span class="val"><?=$arOrder['ORDER']['DATE_STATUS_FORMATED']?></span>
               </div>
               <a href="/personal/order/cancel/<?=$arOrder['ORDER']['ID']?>/?CANCEL=Y" class="btn_prch cancel">Отменить заказ</a>
               <a href="/personal/order/?COPY_ORDER=Y&ID=<?=$arOrder['ORDER']['ID']?>" class="btn_prch repeat">Повторить заказ</a>
            </div>
            <div class="sum">Сумма к оплате: <span><?=$arOrder['ORDER']['FORMATED_PRICE']?></span></div>
            <div class="paid">Оплачен: <span>
                  <?
                  if($arOrder['ORDER']['PAYED'] == 'N'){
                     print 'Нет';
                  }else{
                     print 'Да';
                  }
                  ?>
               </span></div>
            <div class="paytype">Способ оплаты: <span><?=$arOrder['PAYMENT'][0]['PAY_SYSTEM_NAME']?></span></div>
            <div class="delivery">Доставка: <span><?=$arOrder['SHIPMENT'][0]['DELIVERY_NAME']?></span></div>
            <div class="goods">Состав заказа:
               <ol>
                  <? foreach ($arOrder['BASKET_ITEMS'] as $arItem): ?>
                  <li><a href="<?=$arItem['DETAIL_PAGE_URL']?>"><?=$arItem['NAME']?></a> - <span><?=$arItem['QUANTITY']?> <?=$arItem['MEASURE_NAME']?></span></li>
                  <? endforeach; ?>

               </ol>
            </div>
         </div>
      </div>

      <? endforeach; ?>


   </div>
</div>
