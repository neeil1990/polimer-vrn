<?
$MESS["ARTURGOLUBEV_OZON_OTAB_N"] = "Ошибка выполнения скрипта. Обратитесь в техническую поддержку решения \"Интеграция с Ozon\"";

$MESS["ARTURGOLUBEV_OZON_OTAB_STICKER_ERR_TITLE"] = "Ошибка при получении этикетки";

$MESS["ARTURGOLUBEV_OZON_OTAB_STATUS_RESULT_TITLE"] = "Результат изменения статусов заказов на Ozon";
$MESS["ARTURGOLUBEV_OZON_OTAB_STATUS_CANCEL_CONFIRM_TITLE"] = "Отправка статуса заказа Отменён на Ozon";
$MESS["ARTURGOLUBEV_OZON_OTAB_STATUS_CANCEL_CONFIRM_BODY"] = '
	<div class="cancel_confirm_line">
		Причина отмены: 
		<select id="agoz_cancel_reason_id" name="cancel_reason_id">
			<option value="352">Товара нет в наличии</option>
			<option value="400">Остался только бракованный товар</option>
			<option value="401">Отмена из арбитража</option>
			<option value="402">Другая причина</option>
		</select>
	</div>
	<div class="cancel_confirm_line">
		Дополнительная информация по отмене:
		<input type="text" id="agoz_cancel_reason_message" name="cancel_reason_message" />
	</div>
	<div class="cancel_confirm_line">Указанная информация будет отправлена на Ozon</div>
';

$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_ERR_TITLE"] = "Ошибка при запросе акта и накладной";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_GET_PID_SUCCESS"] = "Запрос на формирования акта и накладной принят. ID процесса: ";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_GET_PID_STATUS"] = "Запрос статуса акта и накладной";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_PID_STATUS_R"] = "Акт и накладная готовы к печати";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_PID_STATUS_NR"] = "Текущий статус получения документа ";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_READY_SAVE"] = "Акт и накладная успешно получены";
$MESS["ARTURGOLUBEV_OZON_OTAB_ACT_SAVE_BTN"] = "Скачать акт и накладную";

$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_BUTTON_MAIN"] = "Ozon";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_WINDOW_TITLE"] = "Работа с заказом Ozon";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_NOW"] = "Текущий статус заказа на Ozon: ";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_awaiting_packaging"] = "Ожидает упаковки (awaiting_packaging)";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_awaiting_deliver"] = "Ожидает отгрузки (awaiting_deliver)";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_cancelled"] = "Отменено (cancelled)";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_delivering"] = "Доставляется (delivering)";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_delivered"] = "Доставлено (delivered)";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STICKER_BLOCK"] = "Печать:";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_PODBOR_PRINT"] = "Печать этикетки";
$MESS["ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_BLOCK"] = "Изменение статуса заказа на Ozon:";
?>