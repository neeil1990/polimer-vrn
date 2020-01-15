<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Калькулятор ворот Дорхан (DOORHAN) онлайн на официальном сайте");
$APPLICATION->SetTitle("Калькулятор ворот Дорхан (DOORHAN)");
?><style>
        #dha-canvas #dha-base{
            margin: 0 auto;
        }
    </style>
<div id="dhaide">
</div>
    <!--[if gt IE 8]>-->
    <script>window.jQuery || document.write('<script type="text/javascript" src="https://aide.doorhan.ru/dhaide/js/vendor/jquery.js"><\/script>');
    <script>
        (function (t, h, e, A, I, D, E) {
            D=h.createElement(e);D.async=1;D.setAttribute('crossorigin','use-credentials');D.src=A+'/?data='+encodeURIComponent(JSON.stringify(I));
            t['dhAide']={},I.src=A,dhAide.egg=I;
            h.getElementsByTagName(e)[0].parentNode.appendChild(D);
        })(window, document, 'script', 'https://aide.doorhan.ru/dhaide/js/dhaide.js',
            {	// конфигурация
                type: "full", // тип калькулятора
                markup: 0, // наценка (%)
                cityCode: "CB0000120", // код города
                dealerCode: "VR0000695", // код дилера
                agreementLink: "http://polimer-vrn.ru/upload/compliance.pdf", // адрес ссылки на текст соглашения на обработку персональных данных
                layout: {
                    aide: 'dhaide', // id тэга для вставки калькулятора
                }
            });
    </script>
    <!--<![endif]--><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>