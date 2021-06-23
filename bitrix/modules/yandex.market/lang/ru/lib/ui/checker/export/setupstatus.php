<?php

$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_TITLE'] = 'Выгрузка прайс-листов';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_STATE_NOT_FOUND'] = 'не выгружен';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_STATE_FAIL'] = 'не закончен';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_STATE_PROGRESS'] = 'в процессе';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_STATE_REFRESH_HALT'] = 'полное обновление не выполнялось с #MODIFICATION_DATE#';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_STATE_UNPROCESSED_CHANGES'] = 'не обработано #COUNT# #LABEL#';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_CHANGE_1'] = 'изменение';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_CHANGE_2'] = 'изменения';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_CHANGE_5'] = 'изменений';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_RESOLVE_EXPORT'] = 'выгрузить';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_HALT_DESCRIPTION_USE_CRONTAB'] = '
<p>Проверяем наличие прайс-листов, время обновление файлов которых отстает от планируемого запуска агентов более чем на час.</p>
<p>Настройте <a href="https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=43&LESSON_ID=2943" target="_blank">выполнение агентов на cron</a> для запуска заданий 1 раз в минуту.</p>
';
$MESS['YANDEX_MARKET_CHECKER_TEST_EXPORT_SETUP_STATUS_HALT_DESCRIPTION_INCREASE_FREQUENCY'] = '
<p>Проверяем наличие прайс-листов, время обновление файлов которых отстает от планируемого запуска агентов более чем на час.</p>
<ul>
	<li>Настройте <a href="https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=43&LESSON_ID=2943" target="_blank">запуск агентов на cron</a> 1 раз в минуту;</li>
	<li>Увеличьте Длительность шага агента до 50 секунд в <a href="/bitrix/admin/settings.php?lang=ru&mid=yandex.market&mid_menu=1">настройках модуля</a>.</li>
</ul>
';
