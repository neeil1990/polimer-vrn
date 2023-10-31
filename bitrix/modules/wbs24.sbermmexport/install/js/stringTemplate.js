class Wbs24SbermmexportStringTemplate {
  setInputHandlers(menuButtonId, inputId, marks) {
    if (!BX) return

    BX.ready(() => {
      BX.bind(BX(menuButtonId), 'click', (event) => {
        event.preventDefault()
        BX.adminShowMenu(event.target, this.getAdminMenuStruct(inputId, marks), '')
      })
    })
  }

  getAdminMenuStruct(inputId, marks) {
    let menuStruct = []
    for (let item of marks) {
      if (typeof item['MENU'] != 'undefined') {
        menuStruct.push({
          'TEXT': item['TEXT'],
          'MENU': this.getAdminMenuStruct(inputId, item['MENU'])
        })
      } else if (typeof item['MARK'] != 'undefined') {
        menuStruct.push({
          'TEXT': item['TEXT'],
          'ONCLICK': 'StringTemplate.addMarkToCursorPosition("'+inputId+'", "{'+item['MARK']+'}")',
        })
      }
    }

    return menuStruct
  }

  addMarkToCursorPosition(inputId, mark) {
    let input = document.querySelector('#'+inputId)
    if (input) {
      let start = input.selectionStart
      let end = input.selectionEnd
      input.value = input.value.substring(0, start) + mark + input.value.substring(end)
      input.focus()
      input.selectionEnd = (start == end) ? (end + mark.length) : end
    }
  }
}

if (typeof window === 'undefined') module.exports = Wbs24SbermmexportStringTemplate;
