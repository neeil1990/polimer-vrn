<?php

$MESS['YANDEX_MARKET_TRADING_ENTITY_COMMON_DIGITAL_KEYSIBLOCK_TITLE'] = 'Инфоблок ключей';
$MESS['YANDEX_MARKET_TRADING_ENTITY_COMMON_DIGITAL_KEYSIBLOCK_IBLOCK'] = 'Инфоблок';
$MESS['YANDEX_MARKET_TRADING_ENTITY_COMMON_DIGITAL_KEYSIBLOCK_IBLOCK_HELP'] = '
<p>Выберите инфоблок, который используете для хранения ключей. Или создайте новый, используя <a href="#UPLOAD_URL#">файл</a>.</p>
<p>Если используете собственный инфоблок для хранения ключей, ниже описан формат хранения:</p>
<ul>
<li>Активность &nbsp;&mdash; доступность ключа для продажи.</li>
<li>Название элемента (key)&nbsp;&mdash; значение ключа.</li>
<li>Свойство &laquo;Товар&raquo; (product)&nbsp;&mdash; свойство типа &laquo;Привязка к элементам&raquo;. Символьный код&nbsp;&mdash; PRODUCT_ID.</li>
<li>Свойство &laquo;Заказ&raquo; (order)&nbsp;&mdash; идентификатор заказа, по которому отправлен ключ. Символьный код&nbsp;&mdash; ORDER_ID.</li>
<li>Свойство &laquo;Ид корзины&raquo; (basket)&nbsp;&mdash; идентификатор товара корзины, по которому отправлен ключ. Символьный код&nbsp;&mdash; BASKET_ID.</li>
<li>Свойство &laquo;Статус&raquo; (status)&nbsp;&mdash; статус, в котором находиться ключ. Символьный код&nbsp;&mdash; STATUS.</li>
</ul>
<p>Обязательными являются поля для хранения ключа и указания товара.</p>
<p>Если схема в вашем решении отличается, выбрать собственное поле можно, установив опцию trading_digital_keys_property_x, где x - код поля, указанный выше в скобках. Например, для хранения ключа в свойстве установите опцию trading_digital_keys_property_key равной PROPERTY_KEY, чтобы загружать ключ из свойства с символьным кодом KEY.</p>
';
