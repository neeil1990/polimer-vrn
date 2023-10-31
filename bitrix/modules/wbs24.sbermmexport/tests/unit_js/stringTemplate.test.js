const mocha = require('mocha')
const chai = require('chai')

const assert = chai.assert

const Wbs24SbermmexportStringTemplate = require('../../install/js/stringTemplate.js')
const stringTemplate = new Wbs24SbermmexportStringTemplate()

describe("Wbs24SbermmexportStringTemplate", function() {
  it("test getAdminMenuStruct()", function() {
    // входные данные
    const inputId = 'testId'
    const marks = [
      {
        'TEXT': 'Test',
        'MENU': [
          {
            'TEXT': 'Test 2',
            'MARK': 'TEST2',
          },
          {
            'TEXT': 'Test 3',
            'MARK': 'TEST3',
          },
        ]
      },
    ]

    // результат для проверки
    const expectedResult = [
      {
        'TEXT': 'Test',
        'MENU': [
          {
            'TEXT': 'Test 2',
            'ONCLICK': 'StringTemplate.addMarkToCursorPosition("testId", "TEST2")',
          },
          {
            'TEXT': 'Test 3',
            'ONCLICK': 'StringTemplate.addMarkToCursorPosition("testId", "TEST3")',
          },
        ]
      },
    ]

    // запуск
    const result = stringTemplate.getAdminMenuStruct(inputId, marks)

    // проверка
    assert.equal(JSON.stringify(result), JSON.stringify(expectedResult))
  })
})
