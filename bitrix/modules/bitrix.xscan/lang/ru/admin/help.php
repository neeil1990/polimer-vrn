<?
$MESS["BITRIX_XSCAN_HELP"] = "Справка";
$MESS["BITRIX_XSCAN_HELLO"] =  <<<'html'

<div class="adm-info-message-wrap adm-info-message-red">
    <div class="adm-info-message">
        <div class="adm-info-message-title">Помимо поиска троянов так же следует:</div>
        <ul>
            <li> Проверить нет ли запущенных подозрительных процессов на сервере </li> 
            <li> Проверить нет ли посторонних заданий в crontab</li> 
            <li> Нет ли посторонних ssh ключей</li> 
            <li> Провести ревизию всех паролей</li> 
            <li> Регулярно обновлять все системы сайта</li> 
        </ul>
        <div class="adm-info-message-icon"></div>
    </div>
</div>

<div class="adm-info-message">
    <p>Как правило закладки выполняют произвольный код, отправляют письма, создают/загружают произвольные файлы. 
    <br>
    Вот несколько примеров</p>
</div>

<br>

<img src="/bitrix/images/bitrix.xscan/1.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/2.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/3.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/4.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/5.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/6.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/7.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/8.png" class="xscan-img">
<img src="/bitrix/images/bitrix.xscan/9.png" class="xscan-img">


<br>

<div class="adm-info-message">
    <p>
    На github можно найти готовые подборки (<a href="https://github.com/x-o-r-r-o/PHP-Webshells-Collection" target="_blank">раз</a>, <a href="https://github.com/JohnTroony/php-webshells/tree/master/Collection" target="_blank">два</a>) подобных скриптов.
    <br>
    И всё это может быть как в отдельно созданных файлах, так и в модифицированных файлах сайта.<br>
    <br>
    Приложение ищет определённые шаблоны в коде и формирует список подозрительных файлов. <br>
    <br>
    <b>Не все находки на самом деле являются троянами, иногда разработчики тоже так пишут.</b>
    <br><br>

    При анализе следует учитывать: <br>
    
    </p>

    <ul>
        <li> Имя и расположение файла </li> 
        <li> Даты создания и модификации файла</li> 
        <li> Дата модификации может быть подделана</li> 
        <li> Если сайт разворачивался из бэкапа, даты создания будут свежие</li> 
    </ul>

    <br>

    <p>
        При отправке в карантин файл будет переименован .php -> .ph_<br>
        <b>Это может привести к неработоспособности всего сайта или его частей.<br> Убедитесь что у вас есть доступ к ftp/ssh для отката изменений</b>
        <br><br>
        
        Если у вас несколько сайтов на одном хосте, вам необходимо отдельно обновлять и проверять каждый сайт.
        <br><br>
        Вы можете скачать все срабатывания в виде архива и проверить его обычным антивирусом или на сайте <a href="https://www.virustotal.com" target="_blank">www.virustotal.com</a>.<br>
        Некоторые закладки они легко обнаруживают
    </p>

</div>




html;

?>