{include 'content.tpl' assign=content}
javascript:(
    new BX.CDialog({
    content_url: "{$params}",
    width: 500,
    head: "",
    height: 260,
    resizable: false,
    draggable: true,
    content: "{$content|escape:'javascript'}",
    buttons: [BX.CDialog.btnSave, BX.CDialog.btnCancel]})
).Show();
