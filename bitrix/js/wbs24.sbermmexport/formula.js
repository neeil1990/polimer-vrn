class Wbs24SbermmexportFormula {
  constructor() {
    this.init()
  }

  init() {
    document.addEventListener("DOMContentLoaded", () => {
      this.addAllMarkHandlers()
      this.hideNotUsedExtendedPriceOptions()
      this.addHandersForExtendedPriceCheckboxes()
    })
  }

  addAllMarkHandlers() {
    const inputs = document.querySelectorAll('.sbermmexport-formula')

    for (let input of inputs) {
      const type = input.dataset.type;
      const markLinks = document.querySelectorAll('.js-add-mark-'+type)

      for (let link of markLinks) {
        this.addMarkHandler(link, input)
      }
    }
  }

  addMarkHandler(link, input) {
    link.addEventListener('click', (event) => {
      event.preventDefault()

      let mark = link.dataset.mark
      this.addMarkToCursorPosition(input, mark)
    })
  }

  addMarkToCursorPosition(input, mark) {
    let start = input.selectionStart
    let end = input.selectionEnd
    input.value = input.value.substring(0, start) + mark + input.value.substring(end)
    input.focus()
    input.selectionEnd = (start == end) ? (end + mark.length) : end
  }

  addHandersForExtendedPriceCheckboxes() {
    const checkboxes = document.querySelectorAll('.ep-formula-flag, .ep-flag')
    for (let el of checkboxes) {
      el.addEventListener('click', () => {
        this.hideNotUsedExtendedPriceOptions()
      })
    }
  }

  hideNotUsedExtendedPriceOptions() {
    const epFormulaFlag = document.querySelector('.ep-formula-flag')
    const epFlag = document.querySelector('.ep-flag')

    if (epFormulaFlag && epFlag) {
      this.setActivityForIncludedFormElements('.tr-ep', !epFormulaFlag.checked)
      this.setActivityForIncludedFormElements('.tr-ep-formula', !epFlag.checked)
    }
  }

  setActivityForIncludedFormElements(css, isActive) {
    const parents = document.querySelectorAll(css)
    for (let parentElem of parents) {
      let elems = parentElem.querySelectorAll('input, select')
      for (let el of elems) el.disabled = !isActive
      let links = parentElem.querySelectorAll('a')
      for (let el of links) el.style.display = !isActive ? 'none' : 'inline'
    }
  }
}
