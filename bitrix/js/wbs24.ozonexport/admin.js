class Wbs24Ozonexport {
  activateOptionsForCurrentIblock(field, currentIblockId) {
    let allIblockOptions = document.querySelectorAll(`select[name=${field}] option[data-iblock-id]`);
    for (let elem of allIblockOptions) {
      this.activateOption(elem, currentIblockId);
    }
  }

  activateOption(elem, currentIblockId) {
    let elementIblock = elem.dataset.iblockId;
    if (elementIblock == currentIblockId || elementIblock == "all") {
      elem.hidden = false;
      if (elem.dataset.selected == 'Y') elem.selected = true;
    } else {
      elem.hidden = true;
      elem.selected = false;
    }
  }
}
