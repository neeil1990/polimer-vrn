<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Покупки в кредит на Шишкова");
$APPLICATION->SetTitle("Кредит на шишкова");
?>

    <h2 style="text-align: center">Кредит магазин по адресу г. Воронеж, Шишкова, 107 Б</h2>

    <div id="pos-credit-container"></div>

    <script src="https://my.pochtabank.ru/sdk/v1/pos-credit.js"></script> <script>
    var options = {
        ttCode: '0112001008863',
        ttName: 'г. Воронеж, Шишкова, д.107 Б',
        fullName: '',
        phone: '',
        category: '252',
        manualOrderInput: true
    };
    window.PBSDK.posCredit.mount('#pos-credit-container', options);

    // подписка на событие завершения заполнения заявки
    window.PBSDK.posCredit.on('done', function(id){
        console.log('Id заявки: ' + id)
    });

    // При необходимости можно убрать виджет вызвать unmount
    // window.PBSDK.posCredit.unmount('#pos-credit-container');
</script><br>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>