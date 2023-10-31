<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle ( 'Ручная выгрузка каталога товаров из 1С' );
?>
<?
$arGroups = explode ( ',',
                      COption::GetOptionString ( "catalog",
                                                 "1C_GROUP_PERMISSIONS",
                                                 ""
                      )
);




$access = false;
if ($USER->isAuthorized())
{
    $arUserGroups = CUser::GetUserGroupArray();
    foreach ($arUserGroups as $group)
    {
        if (in_array($group,$arGroups))
            $access = true;
    }
}
if (!$access)
{
?>
    <div style="color:red; font-weight:bold;">Нет доступа к обмену с 1С!</div>
    <?$APPLICATION->IncludeComponent(
        "bitrix:system.auth.form",
        "",
        Array(),
        false
    );?>
<?
}
else
{
    // есть доступ к обмену с 1С
?>
    <p>0. Кладем этот файл в корень сайта.</p>
    <p>1. Отправить запрос и скопировать полученный код куки.</p>

    <form action="http://<?= $_SERVER['HTTP_HOST'] ?>/bitrix/admin/1c_exchange.php?type=catalog&mode=checkauth" method="post" enctype="multipart/form-data">
        <input type="submit">
    </form>
    <p>2.1. Вставить код куки в текстовое поле.</p>
    <p>2.2. Загрузить выгрузку в архиве в папку /<?=COption::GetOptionString("main", "upload_dir", "upload")?>/1c_catalog/</p>
    <p>2.3. Вставить название архива (с расширением) в поле Filename</p>
    <p>2.4. Отправлять на сервер один и тотже запрос пока не получим ответ success.</p>
    <p>3. Путь до файла-обработчика:<br />
        <input type="text" id="pathTo" size="50" value="/bitrix/admin/1c_exchange.php">
    <br />
        <input type="button" value="Стандартный" onClick="$('#pathTo').val('/bitrix/admin/1c_exchange.php')">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="button" value="Искусственный" onClick="$('#pathTo').val('/.exchange/1c_exchange.php')">
    </p>
    <br />

    <form action="" method="get" enctype="multipart/form-data" id="1cForm">
        <input type="hidden" name="type" value="catalog">
        <input type="hidden" name="mode" value="import">
        Filename: <input type="text" name="filename" value="import.xml"><br />
        Cookie: <input type="text" name="Cookie" value="">
        <br />
        <input type="submit">
    </form>

    <script type="text/javascript" src="http://malsup.github.com/jquery.form.js"></script>
    <script type="text/javascript">
        $(function () {
            $(document).ready(function () {
                window.go = false;
                var options = {
                    beforeSubmit: showRequest,
                    success: showResponse
                };

                $('#1cForm').ajaxForm(options);
            });
            function showRequest(formData, jqForm, options) {
                if (window.go == false) {
                    var path = $('#pathTo').val();
                    var options = {
                        beforeSubmit: showRequest,
                        success: showResponse,
                        url: path
                    };

                    $('#1cForm').ajaxForm(options);

                    window.go = true;
                    $('#1cForm').submit();
                    return false;
                }

                $('#out').append("<div id='remove'>Отправлен запрос...</div>");
                return true;
            }

            function showResponse(responseText, statusText, xhr, $form) {
                $('#remove').remove();
                if (responseText.indexOf('failure') != -1) {
                    $('#out').append('<div style="color:red;">' + responseText + '</div>');
                } else if (responseText.indexOf('success') != -1) {
                    $('#out').append('<div style="color:green;">' + responseText + '</div>');
                } else {
                    $('#out').append('<div style="color:blue;">' + responseText + '</div>');
                    $('#1cForm').submit();
                }
            }
        });
    </script>
    <div id="out"></div>
<?
}
?>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>