document.addEventListener("DOMContentLoaded", function() {
    function isSuccessExport() {
        if (document.location.pathname == "/bitrix/admin/cat_export_setup.php") {
            let query = document.location.search;
            if (query.includes('success_export=Y')) {
                return true;
            }
        }

        return false;
    }

    function showWarningMessage(message) {
        let container = document.querySelector('.adm-toolbar-panel-container');
        if (container) {
            let div = document.createElement('div');
            div.innerHTML = message;
            container.before(div);
        }
    }

    async function getManualCallWarning() {
        let response = await fetch('/bitrix/tools/wbs24.sbermmexport/ajax.php?ACTION=getManualCallWarning');
        let message = "";
        if (response.ok) {
            message = await response.text();
        }

        return message;
    }

    async function init() {
        if (isSuccessExport()) {
            let message = await getManualCallWarning();
            showWarningMessage(message);
        }
    }

    init();
});
