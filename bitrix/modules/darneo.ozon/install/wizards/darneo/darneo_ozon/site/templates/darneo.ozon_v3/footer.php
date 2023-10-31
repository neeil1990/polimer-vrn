<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION, $USER;

use Bitrix\Main\Loader;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Bitrix\Main\Localization\Loc;

?>
</div>
</div>
</div>

<div id='kt_app_footer' class='app-footer'>
    <div class='app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3'>
        <div class='text-dark order-2 order-md-1'>
            <span class='text-muted fw-semibold me-1'><?=date('Y')?>&copy;</span>
            <a href='https://darneo.ru' target='_blank' class='text-gray-800 text-hover-primary'>Darneo</a>
        </div>
        <ul class='menu menu-gray-600 menu-hover-primary fw-semibold order-1'>
            <li class='menu-item'>
                <a href='https://darneo.ru' target='_blank' class='menu-link px-2'>
                    <?=Loc::getMessage('DARNEO_OZON_TEMPLATE_SUPPORT')?>
                </a>
            </li>
            <li class='menu-item'>
                <a href='https://marketplace.1c-bitrix.ru/solutions/darneo.ozon/' target='_blank' class='menu-link px-2'>
                    <?=Loc::getMessage('DARNEO_OZON_TEMPLATE_REVIEW')?>
                </a>
            </li>
        </ul>
    </div>
</div>
</div>
</div>
</div>
</div>

<script>var hostUrl = '<?= SITE_TEMPLATE_PATH ?>/assets/';</script>
<script src='<?= SITE_TEMPLATE_PATH ?>/assets/plugins/global/plugins.bundle.js'></script>
<script src='<?= SITE_TEMPLATE_PATH ?>/assets/js/scripts.bundle.js'></script>
<script src='<?= SITE_TEMPLATE_PATH ?>/js/preloader.js'></script>
<script src='<?= SITE_TEMPLATE_PATH ?>/assets/plugins/custom/datatables/datatables.bundle.js'></script>
<script>
    BX.UI.Hint.init(BX('templateHelper'))
</script>
<?php if (Loader::includeModule('darneo.ozon') && HelperSettings::isChat()): ?>
    <script>
        (function (w, d, u) {
            var s = d.createElement('script')
            s.async = true
            s.src = u + '?' + (Date.now() / 60000 | 0)
            var h = d.getElementsByTagName('script')[0]
            h.parentNode.insertBefore(s, h)
        })(window, document, 'https://darneo24.ru/upload/crm/site_button/loader_4_ngstc9.js')
    </script>
<?php endif; ?>
</body>
</html>