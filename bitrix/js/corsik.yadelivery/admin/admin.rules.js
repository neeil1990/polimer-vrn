const ACTION = "/bitrix/tools/corsik.yadelivery/ajax_admin.php";

class Rule {
  static show(link, title) {
    const rule = new Rule();
    rule.bxDialog(link, title).Show();
  }

  bxDialog(link, title) {
    return new BX.CDialog({
      title: title,
      content_url: link,
      height: "100%",
      width: 600,
      draggable: true,
      resizable: true,
      buttons: [
        new BX.CWindowButton({
          title: BX.message("UI_BUTTONS_SAVE_BTN_TEXT"),
          action: this.buttonSave.bind(this),
        }),
        BX.CDialog.btnCancel,
      ],
    });
  }

  buttonSave() {
    const form = BX("corsik_create_rule");
    const prepared = BX.ajax.prepareForm(form);
    const values = !!prepared && prepared.data ? prepared.data : {};
    this.sendPost(values);
  }

  sendPost(data) {
    BX.ajax({
      url: ACTION,
      type: "POST",
      method: "POST",
      data: data,
      onsuccess: () => document.location.reload(true),
      onfailure: () => BX.debug("Error create rule!"),
    });
  }
}
