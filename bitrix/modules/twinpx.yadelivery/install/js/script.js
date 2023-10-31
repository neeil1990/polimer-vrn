(function () {
  let counter = 0;
  let intervalId = setInterval(() => {
    if (
      window.BX &&
      BX.Sale &&
      BX.Sale.OrderAjaxComponent &&
      BX.Sale.OrderAjaxComponent.sendRequest
    ) {
      let sendRequest = BX.Sale.OrderAjaxComponent.sendRequest.bind(
        BX.Sale.OrderAjaxComponent
      );

      BX.Sale.OrderAjaxComponent.sendRequest = function (action, actionData) {
        sendRequest(action, actionData);

        if (!window.twinpxYadeliveryOnBeforeSendRequestFlag) {
          var eventArgs = {
            action: action,
            actionData: actionData,
            cancel: false,
          };
          BX.Event.EventEmitter.emit(
            'BX.Sale.OrderAjaxComponent:onBeforeSendRequest',
            eventArgs
          );
        }

        delete window.twinpxYadeliveryOnBeforeSendRequestFlag;
      };

      //recreate person-type radios
      let hidden = document.querySelector('#bx-soa-region [class*="-group"]')
        ? ''
        : '-hidden';
      let personTypeRadio = document.querySelector(
        `#bx-soa-region${hidden} input[name*="PERSON_TYPE"]`
      );
      if (personTypeRadio) {
        let formGroupDiv = personTypeRadio.closest(`[class*="-group"]`);
        if (formGroupDiv) {
          formGroupDiv.innerHTML = '';
          BX.Sale.OrderAjaxComponent.getPersonTypeControl(formGroupDiv);
        }
      }

      clearInterval(intervalId);
    } else if (++counter >= 1000) {
      clearInterval(intervalId);
    }
  }, 200);
})();

window.twinpxYadeliveryFetchURL =
  window.twinpxYadeliveryFetchURL || '/bitrix/tools/twinpx.yadelivery/ajax.php';

//window.twinpxYadeliveryYmapsAPI = false;
window.twinpxYadeliveryYmapsAPI =
  window.twinpxYadeliveryYmapsAPI ||
  window.twinpxYadeliveryYmapsAPI === undefined
    ? true
    : false;

window.twinpxYadeliveryPopupProps = {
  width: 1076,
  height: 600,
  zIndex: 100,
  overlay: {
    backgroundColor: '#333',
    opacity: '80',
  },
  closeByEsc: true,
  autoHide: true,
  darkMode: false,
  draggable: false,
  resizable: false,
  lightShadow: false,
  angle: false,
};

window.twinpxYadeliveryFindBtnObject = function () {
  let btnObject, block;
  //find checked id
  let hidden = document.querySelector(
    '#bx-soa-order-form #bx-soa-delivery .bx-soa-pp-company-graf-container input'
  )
    ? ''
    : '-hidden';

  let id;
  document
    .querySelectorAll(
      `#bx-soa-order-form #bx-soa-delivery${hidden} .bx-soa-pp-company-graf-container input`
    )
    .forEach((checkbox) => {
      if (checkbox.checked) {
        id = checkbox.id;
      }
    });
  block = document.getElementById(id).closest('.bx-soa-pp-company');

  if (id) {
    if (window.twinpxYadeliveryButtons) {
      window.twinpxYadeliveryButtons.forEach((obj) => {
        if (obj.id === id) {
          btnObject = Object.assign({}, obj);
        }
      });
    } else {
      //create btnObject
      btnObject = {
        id: id,
        btn: '',
      };
    }
  }
  return { btnObject, block };
};

window.twinpxYadeliveryInsertButton = function (block, btnObject, onloadFlag) {
  if (!block || !btnObject) {
    let result = window.twinpxYadeliveryFindBtnObject();
    btnObject = result.btnObject;
    block = result.block;

    if (!btnObject) {
      return;
    }
  }

  if (onloadFlag) {
    twinpxYadeliveryInsertAndFill(btnObject);
  } else {
    //insert empty span
    let emptySpan = document.createElement('span');
    emptySpan.id = `${btnObject.id}_SPAN`;
    block.appendChild(emptySpan);
    let counter = 0;
    //wait for the reload
    let intervalId = setInterval(() => {
      if (!document.getElementById(`${btnObject.id}_SPAN`)) {
        window.twinpxYadeliverySetCheckedStorage();
        let { btnObject } = window.twinpxYadeliveryFindBtnObject();
        clearInterval(intervalId);
        if (btnObject) {
          twinpxYadeliveryInsertAndFill(btnObject);
        }
      } else if (++counter >= 100) {
        clearInterval(intervalId);
      }
    }, 200);
  }
};

window.twinpxYadeliveryInsertAndFill = function (btnObject) {
  if (
    document.getElementById(btnObject.id) &&
    document
      .getElementById(btnObject.id)
      .closest('.bx-soa-pp-company')
      .querySelector('.twpx_btn_wrapper')
  ) {
    return;
  }
  //check if the button already exists
  if (window.twinpxYadeliveryInsertBtnFlag) {
    //insert the button
    let div = document.createElement('div');
    div.innerHTML = btnObject.button;
    document
      .getElementById(btnObject.id)
      .closest('.bx-soa-pp-company')
      .appendChild(div);
  }
  //insert address if exists
  if (window.sessionStorage.getItem('twpxYadeliveryAddress')) {
    let address = JSON.parse(
      window.sessionStorage.getItem('twpxYadeliveryAddress')
    )[btnObject.id];

    if (address) {
      window.twinpxYadeliveryAddAddress(address);
    }
  }
};

(function () {
  if (window.BX && BX.Event.EventEmitter) {
    BX.Event.EventEmitter.subscribe(
      'BX.Sale.OrderAjaxComponent:onBeforeSendRequest',
      () => {
        window.twinpxYadeliveryOnBeforeSendRequestFlag = true;
        window.twinpxYadeliveryInsertButton();
      }
    );
  }
})();

window.twinpxYadeliveryAddAddress = function (address) {
  let rightBlock = document.querySelector(
    '#bx-soa-delivery .bx-soa-pp-company-desc'
  );
  if (rightBlock) {
    if (document.getElementById('twpx-address')) {
      document.getElementById('twpx-address').remove();
    }
    let div = document.createElement('div');
    div.id = 'twpx-address';
    div.innerHTML = address;
    rightBlock.appendChild(div);
  }
};

window.twinpxYadeliverySession = function (address, id) {
  id = id || window.sessionStorage.getItem('twpxYadeliveryId');

  let sessionJSON =
    JSON.parse(window.sessionStorage.getItem('twpxYadeliveryAddress')) || {};

  sessionJSON[id] = address;

  window.sessionStorage.setItem(
    'twpxYadeliveryAddress',
    JSON.stringify(sessionJSON)
  );
};

window.twinpxYadeliverySetCheckedStorage = function (onLoad) {
  let hidden = document.querySelector(
    '#bx-soa-order-form #bx-soa-delivery .bx-soa-pp-company-graf-container input'
  )
    ? ''
    : '-hidden';

  //onload - insert yadelivery button if checked
  //set session storage
  let checkbox = document.querySelector(
    `#bx-soa-order-form #bx-soa-delivery${hidden} .bx-soa-pp-company.bx-selected .bx-soa-pp-company-graf-container input`
  );

  if (onLoad) {
    if (window.twinpxYadeliveryButtons) {
      let btnObject;
      window.twinpxYadeliveryButtons.forEach((obj) => {
        if (obj.id === checkbox.id) {
          btnObject = Object.assign({}, obj);
        }
      });
      if (btnObject) {
        window.twinpxYadeliveryInsertButton(
          checkbox.closest('.bx-soa-pp-company'),
          btnObject,
          true
        );
      }
    }
  }
  //set session storage
  window.sessionStorage.setItem('twpxYadeliveryId', checkbox.id);
};

window.addEventListener('DOMContentLoaded', () => {
  //remove session storage
  window.sessionStorage.removeItem('twpxYadeliveryAddress');
  window.sessionStorage.removeItem('twpxYadeliveryId');

  window.twinpxYadeliverySetCheckedStorage(true);

  //check the yandexDelivery
  if (document.querySelector('#bx-soa-order')) {
    document.querySelector('#bx-soa-order').addEventListener('click', (e) => {
      let block = e.target.closest('.bx-soa-pp-company');
      if (
        block &&
        e.target.closest('#bx-soa-delivery') &&
        !e.target.closest('.twpx_btn_wrapper')
      ) {
        let id = block.querySelector(
          '.bx-soa-pp-company-graf-container input'
        ).id;
        window.sessionStorage.setItem('twpxYadeliveryId', id);

        /*let btnObject;
        if (window.twinpxYadeliveryButtons) {
          window.twinpxYadeliveryButtons.forEach((obj) => {
            if (obj.id === id) {
              btnObject = Object.assign({}, obj);
            }
          });
        }
        window.twinpxYadeliveryInsertButton(block, btnObject);*/
      }
    });
  }

  //focusout for the Region and the Person blocks
  document
    .querySelector('form[name="ORDER_FORM"]')
    .addEventListener('focusout', (e) => {
      if (
        e.target.classList.contains('form-control') ||
        (e.target.tagName.toLowerCase() === 'input' &&
          e.target.closest('.form-control'))
      ) {
        if (window.twinpxYadeliveryRequired) {
          window.twinpxYadeliveryRequired.forEach((name) => {
            if (
              (e.target.closest('.bx-soa-location-input-container') &&
                e.target
                  .closest('.bx-soa-location-input-container')
                  .querySelector(`[name="${name}"]`)) ||
              e.target.getAttribute('name') === name
            ) {
              resetPrice();
            }
          });
        }
      }
    });

  //reset price when focusout happened
  async function resetPrice() {
    let formData = new FormData();
    formData.set('action', 'reset');

    await fetch(window.twinpxYadeliveryFetchURL, {
      method: 'POST',
      body: formData,
    });
  }
});

/*function setPrice(price, offer, expire) {
    let post = {};

    post['action'] = 'price';
    post['price'] = price;
    post['offer'] = offer;
    post['expire'] = expire;

    window.BX.ajax({
        url: window.twinpxYadeliveryFetchURL,
        method: 'POST',
        data: post,
        onsuccess: function (data) {
            window.BX.Sale.OrderAjaxComponent.sendRequest();
        },
    });
}*/

async function sendOffer(jsonStr) {
  let formData = new FormData(),
    response;

  formData.set('action', 'setOfferPrice');
  formData.set('fields', jsonStr);

  response = await fetch(window.twinpxYadeliveryFetchURL, {
    method: 'POST',
    body: formData,
  });

  return response.json();
}

function pageScroll(flag) {
  flag
    ? document.querySelector('body').classList.remove('no-scroll')
    : document.querySelector('body').classList.add('no-scroll');
}

//????????? ??????
function twinpxYadeliveryCourierPopupOpen(yadeliveryButton) {
  let bxSoaOrderForm =
      document.querySelector('#bx-soa-order-form') ||
      yadeliveryButton.closest('form'),
    fields = twinpxYadeliverySerializeForm(bxSoaOrderForm),
    twpxYadeliveryElem = document.createElement('div'),
    showOfferElem,
    courierPopup;
  fetchTimeout = 20000;

  twpxYadeliveryElem.id = 'twpx_yadelivery';
  twpxYadeliveryElem.classList.add('twpx_yadelivery');
  twpxYadeliveryElem.innerHTML = `
    <div class="yd-popup-error-message"></div>
    <div id="showOffer" class="load-circle"></div>
    <div class="yd-popup-error-form">
      <form action="" novalidate="">
        <div class="yd-popup-form">
            <div class="yd-popup-form__col">
                <div class="b-float-label">
                    <input name="PropFio" id="ydFormFio" type="text" value="" required="" data-code="PropFio">
                    <label for="ydFormFio">${BX.message(
                      'TWINPX_JS_FIO'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropEmail" id="ydFormEmail" type="email" value="" data-code="PropEmail">
                    <label for="ydFormEmail">${BX.message(
                      'TWINPX_JS_EMAIL'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropPhone" id="ydFormPhone" type="tel" value="" required="" data-code="PropPhone">
                    <label for="ydFormPhone">${BX.message(
                      'TWINPX_JS_PHONE'
                    )}</label>
                </div>        
            </div>

            <div class="yd-popup-form__col">

                <div class="b-float-label" id="ydFormCityBlock">
                    <input name="PropCity" id="ydFormCity" type="text" value="" required="" data-code="PropCity">
                    <label for="ydFormCity">${BX.message(
                      'TWINPX_JS_CITY'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropStreet" id="ydFormStreet" type="text" value="" required="" data-code="PropStreet">
                    <label for="ydFormStreet">${BX.message(
                      'TWINPX_JS_STREET'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropHome" id="ydFormHome" type="text" value="" required="" data-code="PropHome">
                    <label for="ydFormHome">${BX.message(
                      'TWINPX_JS_HOME'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropCorp" id="ydFormCorp" type="text" value="" data-code="PropCorp">
                    <label for="ydFormCorp">${BX.message(
                      'TWINPX_JS_CORP'
                    )}</label>
                </div>

                <div class="b-float-label">
                    <input name="PropAp" id="ydFormAp" type="text" value="" required="" data-code="PropAp">
                    <label for="ydFormAp">${BX.message('TWINPX_JS_AP')}</label>
                </div>

                <div class="b-float-label" id="ydFormAddressBlock">
                    <textarea name="PropAddress" rows="10" cols="10" id="ydFormAddress" required="" data-code="PropAddress"></textarea>
                    <label for="ydFormOffise">${BX.message(
                      'TWINPX_JS_ADDRESS'
                    )}</label>
                </div>

                <div class="b-float-label" id="ydFormCommentBlock">
                    <textarea name="PropComment" rows="10" cols="10" id="ydFormComment" data-code="PropComment"></textarea>
                    <label for="ydFormComment">${BX.message(
                      'TWINPX_JS_COMMENT'
                    )}</label>
                </div>
            </div>
        </div>

        <div class="yd-popup-form__submit">
            <span class="yd-popup-form__btn yd-popup-form__btn--skip">${BX.message(
              'TWINPX_JS_RESET'
            )}</span>
            <button class="yd-popup-form__btn" type="submit">${BX.message(
              'TWINPX_JS_CONTINUE'
            )}</button>
        </div>
      </form>
    </div>
  `;

  errorMessageElem = twpxYadeliveryElem.querySelector(
    '.yd-popup-error-message'
  );
  showOfferElem = twpxYadeliveryElem.querySelector('#showOffer');
  errorFormElem = twpxYadeliveryElem.querySelector('.yd-popup-error-form');

  showOfferElem.addEventListener('click', (e) => {
    e.preventDefault();

    if (
      e.target.className === 'yd-popup-offers__item' ||
      e.target.closest('.yd-popup-offers__item')
    ) {
      let offerElem =
          e.target.className === 'yd-popup-offers__item'
            ? e.target
            : e.target.closest('.yd-popup-offers__item'),
        jsonStr = offerElem.getAttribute('data-json'); //string

      sendOffer(jsonStr);

      if (window.BX) {
        window.BX.Sale.OrderAjaxComponent.sendRequest();
      }

      courierPopup.destroy();
      pageScroll(true);

      //insert button if needed
      window.twinpxYadeliveryInsertButton();
    }
  });

  errorFormElem
    .querySelector('.yd-popup-form__btn--skip')
    .addEventListener('click', (e) => {
      e.preventDefault();
      courierPopup.destroy();
      pageScroll(true);
    });

  //float label input
  errorFormElem
    .querySelectorAll('.b-float-label input, .b-float-label textarea')
    .forEach((control) => {
      let item = control.closest('.b-float-label'),
        label = item.querySelector('label');

      if (control.value.trim() !== '') {
        label.classList.add('active');
      }

      control.addEventListener('blur', () => {
        if (control.value.trim() !== '') {
          label.classList.add('active');
        } else {
          label.classList.remove('active');
        }
      });

      control.addEventListener('keyup', () => {
        if (item.classList.contains('invalid')) {
          validateControl(item, control);
        }
      });
    });

  errorFormElem.querySelectorAll('textarea').forEach(function (textarea) {
    textarea.addEventListener('input', function () {
      this.style.height = this.scrollHeight + 'px';
    });
  });

  errorFormElem.querySelector('form').addEventListener('submit', (e) => {
    e.preventDefault();
    let formElem = e.target;

    //validate the form
    let focusElement = errorFormValidation(formElem);

    //focus
    if (!focusElement) {
      //fill empty fields on the page
      formElem.querySelectorAll('input, textarea').forEach((formControl) => {
        let name = formControl.getAttribute('name');
        let orderFormControls = bxSoaOrderForm.querySelectorAll(
          `[name="${name}"]`
        );

        if (orderFormControls) {
          orderFormControls.forEach((orderFormControl) => {
            orderFormControl.value = formControl.value;
          });
        }
      });

      //send request with Bitrix method
      if (
        window.BX &&
        !!BX.Sale &&
        !!BX.Sale.OrderAjaxComponent &&
        !!BX.Sale.OrderAjaxComponent.sendRequest
      ) {
        BX.Sale.OrderAjaxComponent.sendRequest();
      }

      //after the request fire the onPopupShow again
      twpxYadeliveryElem.classList.remove('twpx_yadelivery--form');
      errorMessageElem.innerHTML = '';
      showOfferElemLoader(true);
      //new fields values
      fields = twinpxYadeliverySerializeForm(bxSoaOrderForm);
      //try to load offers again
      let props = '';
      for (let i = 0; i < formElem.elements.length; i++) {
        let element = formElem.elements[i],
          code = formElem.elements[i].getAttribute('data-code');
        if (code) {
          props += `${i !== 0 ? '&' : ''}${code}=${formElem.elements[i].value}`;
        }
      }
      courierPopupShow(props);
    }
  });

  function validateControl(item, control) {
    let regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

    //required
    if (
      control.getAttribute('required') === '' &&
      control.value.trim() !== ''
    ) {
      //tel length < 13
      if (control.getAttribute('type') === 'tel') {
        let digits = control.value.match(/\d+(\.\d+)?/g);
        if (
          control.value.trim() !== '' &&
          digits &&
          digits.join('').length < 13
        ) {
          item.classList.remove('invalid');
        }
      } else {
        item.classList.remove('invalid');
      }
    }

    //email
    Object.keys(regExp).forEach((key) => {
      if (control.getAttribute('type') === key) {
        if (
          (control.value.trim() !== '' && regExp[key].test(control.value)) ||
          (control.getAttribute('required') !== '' &&
            control.value.trim() === '')
        ) {
          item.classList.remove('invalid');
        }
      }
    });
  }

  function errorFormValidation(formElem) {
    let regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

    let focusElement;

    //required
    formElem.querySelectorAll('[required]').forEach((reqInput) => {
      if (reqInput.value.trim() === '') {
        if (!focusElement) {
          focusElement = reqInput;
        }
        reqInput.closest('.b-float-label').classList.add('invalid');
      } else {
        reqInput.closest('.b-float-label').classList.remove('invalid');
      }
    });

    //email
    Object.keys(regExp).forEach((key) => {
      formElem.querySelectorAll(`[type=${key}]`).forEach((input) => {
        //required
        if (
          input.getAttribute('required') === '' ||
          input.value.trim() !== ''
        ) {
          if (!regExp[key].test(input.value)) {
            if (!focusElement) {
              focusElement = input;
            }
            input.closest('.b-float-label').classList.add('invalid');
          } else {
            input.closest('.b-float-label').classList.remove('invalid');
          }
        }
      });
    });

    //tel length < 13
    formElem.querySelectorAll('[type=tel]').forEach((telInput) => {
      let digits = telInput.value.match(/\d+(\.\d+)?/g);
      if (
        telInput.getAttribute('required') === '' ||
        telInput.value.trim() !== ''
      ) {
        if (!digits || digits.join('').length >= 13) {
          if (!focusElement) {
            focusElement = telInput;
          }
          telInput.closest('.b-float-label').classList.add('invalid');
        } else {
          telInput.closest('.b-float-label').classList.remove('invalid');
        }
      }
    });

    if (focusElement) {
      focusElement.focus();
    }

    return focusElement;
  }

  courierPopup = BX.PopupWindowManager.create('twpx_yadelivery_popup', '', {
    content: twpxYadeliveryElem,
    titleBar: BX.message('TWINPX_JS_TYPE'),
    closeIcon: {
      opacity: 1,
    },
    ...twinpxYadeliveryPopupProps,
    events: {
      onPopupShow: courierPopupShow,
      onPopupClose: courierPopupClose,
    },
  });

  courierPopup.show();

  function offersError(message) {
    errorMessageElem.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${message}</div>`;
    showOfferElemLoader(false);
  }

  function createOffersHtml(offersArray) {
    let selectMessage = window.BX
        ? window.BX.message('TWINPX_JS_SELECT')
        : 'Choose',
      html = `<div class="yd-popup-offers__wrapper">`;

    offersArray.forEach(({ json, date, time, price }) => {
      html += `<div class="yd-popup-offers__item" data-json='${json}'>
      <div class="yd-popup-offers__info">
          <span class="yd-popup-offers__date"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/pvz-calendar.svg)"></i>${date}</span>
          <span class="yd-popup-offers__time"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/pvz-clock.svg)"></i>${time}</span>
        </div>
      <b class="yd-popup-offers__price">${price}</b>
      <a href="#" class="yd-popup-offers__btn">${selectMessage}</a>
    </div>`;
    });

    html += '</div>';

    return html;
  }

  function showOfferElemLoader(flag) {
    flag
      ? showOfferElem.classList.add('load-circle')
      : showOfferElem.classList.remove('load-circle');
  }

  async function courierPopupShow(props) {
    pageScroll(false);

    let formData = new FormData(),
      controller = new AbortController(),
      response,
      result,
      html = '';

    //fetch request
    formData.set('action', 'getOffer');
    formData.set('fields', fields);

    if (props) {
      formData.set('props', props);
    }

    setTimeout(() => {
      if (!response) {
        controller.abort();
      }
    }, fetchTimeout);

    try {
      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
        signal: controller.signal,
      });

      result = await response.json();

      if (result && typeof result === 'object') {
        if (result.STATUS === 'Y') {
          if (result.ERRORS) {
            offersError(result.ERRORS);

            if (result.FIELDS) {
              twpxYadeliveryElem.classList.add('twpx_yadelivery--form');

              //get input values
              Object.keys(result.FIELDS).forEach((key) => {
                let formControls = document.querySelectorAll(
                  `[name="${result.FIELDS[key]}"]`
                );
                if (!formControls) return;

                let errorFormControl = errorFormElem.querySelector(
                  `[data-code="${key}"]`
                );
                let value = '';
                formControls.forEach((formControl) => {
                  if (!value && formControl.value) {
                    value = formControl.value;
                  }
                });
                errorFormControl.value = value;

                //set name attribute
                errorFormControl.setAttribute('name', result.FIELDS[key]);

                //active label
                if (errorFormControl.value.trim() !== '') {
                  errorFormControl
                    .closest('.b-float-label')
                    .querySelector('label')
                    .classList.add('active');
                }
              });

              //show address
              let addressBlock = errorFormElem.querySelector(
                '#ydFormAddressBlock'
              );
              if (result.FIELDS.PropAddress && addressBlock) {
                addressBlock
                  .closest('.yd-popup-form__col')
                  .querySelectorAll('.b-float-label')
                  .forEach((controlBlock) => {
                    if (
                      !controlBlock.id ||
                      (controlBlock.id !== 'ydFormAddressBlock' &&
                        controlBlock.id !== 'ydFormCommentBlock')
                    ) {
                      if (
                        controlBlock.id !== 'ydFormCityBlock' ||
                        !result.FIELDS.PropCity
                      ) {
                        controlBlock.remove();
                      }
                    }
                  });
              } else if (addressBlock) {
                addressBlock.remove();
              }

              //validate inputs
              errorFormValidation(errorFormElem.querySelector('form'));
            }
          } else if (result.OFFERS) {
            //remove preloader
            showOfferElemLoader(false);

            //html
            html = createOffersHtml(result.OFFERS);
            showOfferElem.innerHTML = html;

            //effect
            setTimeout(() => {
              showOfferElem.classList.add('yd-popup-offers--animate');
            }, 0);
          } else {
            offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
          }
        } else {
          offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
        }
      }
    } catch (err) {
      offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
    }

    courierPopup.adjustPosition();
  }

  function courierPopupClose() {
    courierPopup.destroy();
    pageScroll(true);
  }
}

function showPvz(yadeliveryButton, yadeliveryMode) {
  let bxSoaOrderForm =
      document.querySelector('#bx-soa-order-form') ||
      yadeliveryButton.closest('form'),
    fields = twinpxYadeliverySerializeForm(bxSoaOrderForm),
    ydPopupContainer,
    ydPopupList,
    ydPopupWrapper,
    ydPopupDetailWrapper,
    ydPopupDetail,
    ydPopupSlide,
    ydPopupSlideDetail,
    errorFormElem,
    map,
    objectManager,
    bounds,
    firstGeoObjectCoords,
    topBtns,
    regionName,
    payment,
    pvzPopup,
    centerCoords,
    pointsArray,
    pointsNodesArray = {},
    newBounds = [],
    fetchTimeout = 20000,
    container = `<div class="yd-popup-container yd-popup--map ${
      yadeliveryMode === 'simple' ? 'yd-popup--simple' : ''
    }">
        <div class="yd-popup-error-message">
          <div class="yd-popup-error__message">
            <i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>
            ${BX.message('TWINPX_JS_NO_YMAP_KEY')}
          </div>
        </div>
        <div id="ydPopupMap" class="yd-popup-map load-circle"></div>
        <div class="yd-popup-slide">
          <div class="yd-popup-slide-wrapper">
            <div class="yd-popup-slide-detail"></div>
            <div class="yd-popup-slide-error-form">
              <form action="" novalidate="">
                <div class="yd-popup-form">
                  <div class="b-float-label">
                      <input name="PropFio" id="ydSlideFormFio" type="text" value="" required="" data-code="PropFio">
                      <label for="ydSlideFormFio">${BX.message(
                        'TWINPX_JS_FIO'
                      )}</label>
                  </div>

                  <div class="b-float-label">
                      <input name="PropEmail" id="ydSlideFormEmail" type="email" value="" data-code="PropEmail">
                      <label for="ydSlideFormEmail">${BX.message(
                        'TWINPX_JS_EMAIL'
                      )}</label>
                  </div>

                  <div class="b-float-label">
                      <input name="PropPhone" id="ydSlideFormPhone" type="tel" value="" required="" data-code="PropPhone">
                      <label for="ydSlideFormPhone">${BX.message(
                        'TWINPX_JS_PHONE'
                      )}</label>
                  </div>
                  
                  <input name="PropAddress" id="ydSlideFormAddress" type="hidden" value="" data-code="PropAddress">
                </div>

                <div class="yd-popup-form__submit">
                    <button class="yd-popup-form__btn" type="submit">${BX.message(
                      'TWINPX_JS_CONTINUE'
                    )}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="yd-popup-list">
          <div class="yd-popup-list__back">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="6.446" height="10.891" viewBox="0 0 6.446 10.891">
              <defs><clipPath id="clip-path"><rect width="6.446" height="10.891" transform="translate(0 0)" fill="none" stroke="#0b0b0b" stroke-width="1"/></clipPath></defs>
              <g transform="translate(0 0)"><g clip-path="url(#clip-path)"><path d="M5.446,9.891,1,5.445,5.446,1" fill="none" stroke="#0b0b0b" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></g></g>
            </svg>
            ${BX.message('TWINPX_JS_RETURN_LIST')}
          </div>
          <div class="yd-popup-list-wrapper load-circle"></div>
          <div class="yd-popup-list-detail-wrapper">
            <div class="yd-popup-list-detail"></div>
            <div class="yd-popup-error-form">
              <form action="" novalidate="">
                <div class="yd-popup-form">
                  <div class="b-float-label">
                      <input name="PropFio" id="ydFormFio" type="text" value="" required="" data-code="PropFio">
                      <label for="ydFormFio">${BX.message(
                        'TWINPX_JS_FIO'
                      )}</label>
                  </div>

                  <div class="b-float-label">
                      <input name="PropEmail" id="ydFormEmail" type="email" value="" data-code="PropEmail">
                      <label for="ydFormEmail">${BX.message(
                        'TWINPX_JS_EMAIL'
                      )}</label>
                  </div>

                  <div class="b-float-label">
                      <input name="PropPhone" id="ydFormPhone" type="tel" value="" required="" data-code="PropPhone">
                      <label for="ydFormPhone">${BX.message(
                        'TWINPX_JS_PHONE'
                      )}</label>
                  </div>
                  
                  <input name="PropAddress" id="ydFormAddress" type="hidden" value="" data-code="PropAddress">
                </div>

                <div class="yd-popup-form__submit">
                    <span class="yd-popup-form__btn yd-popup-form__btn--skip">${BX.message(
                      'TWINPX_JS_RESET'
                    )}</span>
                    <button class="yd-popup-form__btn" type="submit">${BX.message(
                      'TWINPX_JS_CONTINUE'
                    )}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="yd-popup-mobile-top">
          <div class="yd-popup-btn yd-popup-btn--light yd-popup-btn--active">${BX.message(
            'TWINPX_JS_ONCART'
          )}</div>
          <div class="yd-popup-btn yd-popup-btn--light">${BX.message(
            'TWINPX_JS_ONLIST'
          )}</div>
        </div>
        <div class="yd-popup-mobile-bottom">
          <div class="yd-popup-btn yd-popup-btn--gray">${BX.message(
            'TWINPX_JS_CLOSE'
          )}</div>
        </div>
      </div>`;

  //pvz popup
  pvzPopup = BX.PopupWindowManager.create('ydPopup', '', {
    content: container,
    ...twinpxYadeliveryPopupProps,
    events: {
      onPopupShow: pvzPopupShow,
      onPopupClose: pvzPopupClose,
    },
  });

  pvzPopup.show();

  async function pvzPopupShow() {
    pageScroll(false);

    //show error if there is no api ymaps key
    if (!window.twinpxYadeliveryYmapsAPI) {
      document
        .querySelector('#ydPopup')
        .classList.add('yd-popup--error-message');
    }

    //send request to get region name
    let formData = new FormData(),
      controller = new AbortController(),
      response,
      result;

    //fetch request
    formData.set('action', 'getRegion');
    formData.set('fields', fields);

    setTimeout(() => {
      if (!response) {
        controller.abort();
      }
    }, fetchTimeout);

    try {
      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
        signal: controller.signal,
      });

      result = await response.json();

      if (result && result.STATUS === 'Y') {
        regionName = result.REGION;
        payment = result.PAYMENT;
        onPopupShow(result.ERRORS);
      }
    } catch (err) {
      throw err;
    }
  }

  function pvzPopupClose() {
    pvzPopup.destroy();
    pageScroll(true);
  }

  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  function pointsError(message) {
    ydPopupWrapper.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${
      message || BX.message('TWINPX_JS_EMPTY_LIST')
    }</div>`;
  }

  function offersError(error) {
    ydPopupSlideDetail.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
    ydPopupDetail.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
    elemLoader(ydPopupSlideDetail, false);
    elemLoader(ydPopupDetailWrapper, false);
  }

  function onObjectEvent(e) {
    let id = e.get('objectId');

    let pointObject = pointsArray.find((p) => {
      return p.id === id;
    });

    clickPlacemark(pointObject, map, pointObject.coords);
  }

  function onClusterEvent(e) {
    setBtnActive(1);

    //show points on the slide
    ydPopupSlideDetail.innerHTML = '';

    for (let key in pointsNodesArray) {
      if (pointsNodesArray[key]['sorted'] === true) {
        let pointNode = pointsNodesArray[key]['node'].cloneNode(true);
        ydPopupSlideDetail.appendChild(pointNode);
      }
    }
  }

  function setBtnActive(btnIndex) {
    topBtns[btnIndex === 0 ? 1 : 0].classList.remove('yd-popup-btn--active');
    topBtns[btnIndex].classList.add('yd-popup-btn--active');
  }

  function setPopupMode(mode) {
    ydPopupContainer.classList.remove(
      'yd-popup--map',
      'yd-popup--detail',
      'yd-popup--list',
      'yd-popup--slide'
    );
    ydPopupContainer.classList.add(`yd-popup--${mode}`);
  }

  function createPointsItem({
    id,
    title,
    type,
    schedule,
    address,
    coords,
    json,
  }) {
    let item = document.createElement('div');
    item.className = 'yd-popup-list__item';
    item.setAttribute('data-id', id);
    item.setAttribute('data-address', address);
    item.setAttribute('data-coords', coords);
    item.setAttribute('data-json', json);

    item.innerHTML = `
      <div class="yd-popup-list__title">${title}</div>
      <div class="yd-popup-list__text">
      <span>${type}</span> ${schedule}<br>
      ${address}
      </div>
      <div class="yd-popup-btn yd-popup-btn--red">${BX.message(
        'TWINPX_JS_SELECT'
      )}</div>
    `;

    return item;
  }

  async function sendId(json, address) {
    //get offers
    let formData = new FormData();
    formData.set('action', 'setPvzId');
    formData.set('json', json);

    let controller = new AbortController();
    let response;

    setTimeout(() => {
      if (!response) {
        controller.abort();
      }
    }, fetchTimeout);

    try {
      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
        signal: controller.signal,
      });

      let result = await response.json();

      if (
        result.FIELDS &&
        result.FIELDS.PropAddress &&
        document.querySelector(`[name="${result.FIELDS.PropAddress}"]`)
      ) {
        //set address control value
        document.querySelector(`[name="${result.FIELDS.PropAddress}"]`).value =
          address;

        window.BX.Sale.OrderAjaxComponent.sendRequest();

        //insert button if needed
        window.twinpxYadeliveryInsertButton();
      }
    } catch (err) {
      throw err;
    }
  }

  function createItem(id) {
    //item content
    let item = ydPopupList.querySelector(`[data-id="${id}"]`).cloneNode(true);
    let slideItem = ydPopupList
      .querySelector(`[data-id="${id}"]`)
      .cloneNode(true);

    item.querySelector('.yd-popup-btn').style.display = 'none';
    slideItem.querySelector('.yd-popup-btn').style.display = 'none';

    return [item, slideItem];
  }

  async function showDetail(jsonString, coords, map) {
    //set detail mode
    setPopupMode('detail');

    if (
      yadeliveryMode &&
      yadeliveryMode === 'simple' &&
      window.matchMedia('(max-width: 1076px)').matches
    ) {
      setBtnActive(1);
      setPopupMode('list');
    }

    elemLoader(ydPopupSlideDetail, true);
    elemLoader(ydPopupDetailWrapper, true);

    ydPopupSlideDetail.innerHTML = '';
    ydPopupDetail.innerHTML = '';

    let jsonObject = JSON.parse(jsonString);
    let itemsArray = createItem(jsonObject.id);
    let item = itemsArray[0];
    let slideItem = itemsArray[1];

    if (yadeliveryMode === undefined) {
      await showOffers({ jsonObject, map, coords, itemsArray });
    } else if (yadeliveryMode === 'simple') {
      //detail
      item.querySelector('.yd-popup-btn').addEventListener('click', (e) => {
        e.preventDefault();
        sendId(
          item.getAttribute('data-json'),
          `${item.querySelector('.yd-popup-list__title').textContent}, ${
            jsonObject.address
          }.`
        );
        pvzPopup.close();

        window.twinpxYadeliveryAddAddress(jsonObject.address);
        window.twinpxYadeliverySession(jsonObject.address);
      });
      //slide
      slideItem
        .querySelector('.yd-popup-btn')
        .addEventListener('click', (e) => {
          e.preventDefault();
          sendId(
            slideItem.getAttribute('data-json'),
            `${slideItem.querySelector('.yd-popup-list__title').textContent}, ${
              jsonObject.address
            }.`
          );
          pvzPopup.close();

          window.twinpxYadeliveryAddAddress(jsonObject.address);
          window.twinpxYadeliverySession(jsonObject.address);
        });
    }

    //active button
    setBtnActive(1);
  }

  function clickPlacemark(jsonObject, map, coords) {
    let itemsArray = createItem(jsonObject.id);

    setPopupMode('slide');

    if (
      yadeliveryMode &&
      yadeliveryMode === 'simple' &&
      window.matchMedia('(max-width: 1076px)').matches
    ) {
      setBtnActive(1);
      setPopupMode('list');
    }

    elemLoader(ydPopupSlideDetail, true);
    elemLoader(ydPopupDetailWrapper, true);

    ydPopupSlideDetail.innerHTML = '';
    ydPopupDetail.innerHTML = '';

    showOffers({ jsonObject, map, coords, itemsArray });
  }

  async function showOffers({ jsonObject, map, coords, itemsArray, props }) {
    let c = coords;
    //pan map on desktop
    if (typeof coords === 'string') {
      c = coords.split(',');
      c[0] = c[0] * 1;
      c[1] = c[1] * 1;
    }
    map.panTo(c).then(() => {
      map.setZoom(16);
    });

    if (!itemsArray) {
      itemsArray = createItem(jsonObject.id);
    }

    let item = itemsArray[0];
    let slideItem = itemsArray[1];

    //get offers
    let formData = new FormData();
    formData.set('action', 'pvzOffer');
    formData.set(
      'fields',
      `${fields}&id=${jsonObject.id}&address=${jsonObject.address}&title=${jsonObject.title}`
    );

    if (props) {
      formData.set('props', props);
    }

    let controller = new AbortController();
    let response;

    setTimeout(() => {
      if (!response) {
        controller.abort();
      }
    }, fetchTimeout);

    try {
      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
        signal: controller.signal,
      });

      let result = await response.json();

      let html = '';

      if (result && typeof result === 'object') {
        if (result.STATUS === 'Y') {
          //set address value
          errorFormElem.querySelector('[data-code="PropAddress"]').value = `${
            item.querySelector('.yd-popup-list__title').textContent
          }, ${item.getAttribute('data-address')}.`;

          if (result.ERRORS) {
            offersError(result.ERRORS);

            if (result.FIELDS) {
              ydPopupList.classList.add('yd-popup-list--form');
              ydPopupSlide.classList.add('yd-popup-slide--form');

              //get input values
              Object.keys(result.FIELDS).forEach((key) => {
                let formControls = document.querySelectorAll(
                  `[name="${result.FIELDS[key]}"]`
                );
                let errorFormControl = errorFormElem.querySelector(
                  `[data-code="${key}"]`
                );
                let slideErrorFormControl = ydPopupSlideErrorForm.querySelector(
                  `[data-code="${key}"]`
                );

                if (
                  !formControls ||
                  !errorFormControl ||
                  !slideErrorFormControl
                )
                  return;

                let value = '';
                formControls.forEach((formControl) => {
                  if (!value && formControl.value) {
                    value = formControl.value;
                  }
                });

                //set values for all the controls except address hidden input
                if (
                  !errorFormControl.getAttribute('type') ||
                  errorFormControl.getAttribute('type') !== 'hidden'
                ) {
                  errorFormControl.value = value;
                }
                if (
                  !slideErrorFormControl.getAttribute('type') ||
                  slideErrorFormControl.getAttribute('type') !== 'hidden'
                ) {
                  slideErrorFormControl.value = value;
                }

                //set name attribute
                errorFormControl.setAttribute('name', result.FIELDS[key]);
                slideErrorFormControl.setAttribute('name', result.FIELDS[key]);

                //active label
                if (
                  errorFormControl.value.trim() !== '' &&
                  errorFormControl.closest('.b-float-label')
                ) {
                  errorFormControl
                    .closest('.b-float-label')
                    .querySelector('label')
                    .classList.add('active');
                }
                if (
                  slideErrorFormControl.value.trim() !== '' &&
                  slideErrorFormControl.closest('.b-float-label')
                ) {
                  slideErrorFormControl
                    .closest('.b-float-label')
                    .querySelector('label')
                    .classList.add('active');
                }
              });

              //validate inputs
              errorFormValidation(errorFormElem.querySelector('form'));
              errorFormValidation(ydPopupSlideErrorForm.querySelector('form'));
            }
          } else if (result.OFFERS) {
            html = `<div class="yd-h3">${BX.message(
              'TWINPX_JS_VARIANT'
            )}</div>`;

            result.OFFERS.forEach(({ json, date, time, price }) => {
              html += `
              <div class="yd-popup-offer" data-json='${json}'>
                <div class="yd-popup-offer__info">
                <div class="yd-popup-offer__date">${date}</div>
                <div class="yd-popup-offer__time">${time}</div>
                </div>
                <div class="yd-popup-offer__price">${price}</div>
                <div class="yd-popup-btn yd-popup-btn--red">${BX.message(
                  'TWINPX_JS_SELECT'
                )}</div>
              </div>
              `;
            });

            //remove preloader
            elemLoader(ydPopupDetailWrapper, false);
            elemLoader(ydPopupSlideDetail, false);

            ydPopupDetail.innerHTML = html;
            ydPopupSlideDetail.innerHTML = html;

            //popup adjust position
            setTimeout(() => {
              pvzPopup.adjustPosition();
            }, 100);
          } else {
            offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
          }
        } else {
          offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
        }
      }
    } catch (err) {
      offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
    }

    //item content
    ydPopupDetail.prepend(item);
    ydPopupSlideDetail.prepend(slideItem);

    ydPopupDetailWrapper.scrollTo({
      top: 0,
    });

    ydPopupSlideWrapper.scrollTo({
      top: 0,
    });
  }

  function onPopupShow(errorMessage) {
    ydPopupContainer = document.querySelector('#ydPopup .yd-popup-container');
    ydPopupList = ydPopupContainer.querySelector('.yd-popup-list');
    ydPopupWrapper = ydPopupList.querySelector('.yd-popup-list-wrapper');
    ydPopupDetailWrapper = ydPopupList.querySelector(
      '.yd-popup-list-detail-wrapper'
    );
    ydPopupDetail = ydPopupList.querySelector('.yd-popup-list-detail');
    ydPopupSlide = ydPopupContainer.querySelector('.yd-popup-slide');
    ydPopupSlideWrapper = ydPopupContainer.querySelector(
      '.yd-popup-slide-wrapper'
    );
    ydPopupSlideDetail = ydPopupContainer.querySelector(
      '.yd-popup-slide-detail'
    );
    ydPopupSlideErrorForm = ydPopupContainer.querySelector(
      '.yd-popup-slide-error-form'
    );
    errorFormElem = ydPopupList.querySelector('.yd-popup-error-form');

    pointsArray = [];

    errorFormElem
      .querySelector('.yd-popup-form__btn--skip')
      .addEventListener('click', (e) => {
        e.preventDefault();
        ydPopupList.querySelector('.yd-popup-list__back').click();
      });

    //float label input
    errorFormElem
      .querySelectorAll('.b-float-label input, .b-float-label textarea')
      .forEach(errorFormControlEvents);

    ydPopupSlideErrorForm
      .querySelectorAll('.b-float-label input, .b-float-label textarea')
      .forEach(errorFormControlEvents);

    function errorFormControlEvents(control) {
      let item = control.closest('.b-float-label'),
        label = item.querySelector('label');

      if (control.value.trim() !== '') {
        label.classList.add('active');
      }

      control.addEventListener('blur', () => {
        if (control.value.trim() !== '') {
          label.classList.add('active');
        } else {
          label.classList.remove('active');
        }
      });

      control.addEventListener('keyup', () => {
        if (item.classList.contains('invalid')) {
          validateControl(item, control);
        }
      });
    }

    errorFormElem.querySelectorAll('textarea').forEach(function (textarea) {
      textarea.addEventListener('input', function () {
        this.style.height = this.scrollHeight + 'px';
      });
    });

    errorFormElem
      .querySelector('form')
      .addEventListener('submit', sendErrorForm);
    ydPopupSlideErrorForm
      .querySelector('form')
      .addEventListener('submit', sendErrorForm);

    function sendErrorForm(e) {
      e.preventDefault();
      let formElem = e.target;

      //validate the form
      let focusElement = errorFormValidation(formElem);

      //focus
      if (!focusElement) {
        //fill empty fields on the page
        formElem.querySelectorAll('input, textarea').forEach((formControl) => {
          let name = formControl.getAttribute('name');
          let orderFormControls = bxSoaOrderForm.querySelectorAll(
            `[name="${name}"]`
          );

          if (orderFormControls) {
            orderFormControls.forEach((orderFormControl) => {
              orderFormControl.value = formControl.value;
            });
          }
        });

        //send request with Bitrix method
        if (
          window.BX &&
          !!BX.Sale &&
          !!BX.Sale.OrderAjaxComponent &&
          !!BX.Sale.OrderAjaxComponent.sendRequest
        ) {
          BX.Sale.OrderAjaxComponent.sendRequest();
        }

        //after the request fire the onPopupShow again
        let itemElem = ydPopupDetail.querySelector('.yd-popup-list__item');
        let jsonObject = JSON.parse(itemElem.getAttribute('data-json'));
        let coords = itemElem.getAttribute('data-coords');

        ydPopupList.classList.remove('yd-popup-list--form');
        ydPopupSlide.classList.remove('yd-popup-slide--form');

        //new fields values
        fields = twinpxYadeliverySerializeForm(bxSoaOrderForm);
        //try to load offers again
        let props = '';
        for (let i = 0; i < formElem.elements.length; i++) {
          let element = formElem.elements[i],
            code = formElem.elements[i].getAttribute('data-code');
          if (code) {
            props += `${i !== 0 ? '&' : ''}${code}=${
              formElem.elements[i].value
            }`;
          }
        }
        showOffers({ jsonObject, map, coords, props });
      }
    }

    //choose point event
    ydPopupWrapper.addEventListener('click', (e) => {
      e.preventDefault();

      let item, id, coords, address;

      if (e.target.classList.contains('yd-popup-list__item')) {
        item = e.target;
      } else if (e.target.closest('.yd-popup-list__item')) {
        item = e.target.closest('.yd-popup-list__item');
      }

      if (!item) return;

      function getProp(prop) {
        return item.getAttribute(`data-${prop}`);
      }

      if (e.target.classList.contains('yd-popup-btn')) {
        if (yadeliveryMode === undefined) {
          //click button
          setPopupMode('detail');
          showDetail(getProp('json'), getProp('coords'), map);
        } else if (yadeliveryMode === 'simple') {
          sendId(
            getProp('json'),
            `${
              item.querySelector('.yd-popup-list__title').textContent
            }, ${getProp('address')}.`
          );
          pvzPopup.close();

          window.twinpxYadeliveryAddAddress(getProp('address'));
          window.twinpxYadeliverySession(getProp('address'));
        }
      } else if (window.matchMedia('(min-width: 1077px)').matches) {
        //pan map on desktop
        let coords = getProp('coords').split(',');
        coords[0] = coords[0] * 1;
        coords[1] = coords[1] * 1;
        map.panTo(coords).then(() => {
          map.setZoom(16);
        });
      }
    });

    //choose offer event
    ydPopupDetail.addEventListener('click', chooseOffer);
    ydPopupSlideDetail.addEventListener('click', chooseOffer);

    async function chooseOffer(e) {
      e.preventDefault();
      if (
        e.target.classList.contains('yd-popup-btn') &&
        e.target.closest('.yd-popup-offer')
      ) {
        let btn = e.target;
        let offerElem = btn.closest('.yd-popup-offer');
        let jsonStr = offerElem.getAttribute('data-json'); //string

        let result = await sendOffer(jsonStr);

        if (result && result.STATUS === 'Y') {
          if (
            result.FIELDS &&
            result.FIELDS.PropAddress &&
            document.querySelector(`[name="${result.FIELDS.PropAddress}"]`)
          ) {
            //set address control value
            document.querySelector(
              `[name="${result.FIELDS.PropAddress}"]`
            ).value = errorFormElem.querySelector(
              '[data-code="PropAddress"]'
            ).value;
          }
          pvzPopup.destroy();
          BX.Sale.OrderAjaxComponent.sendRequest();
          pageScroll(true);

          window.twinpxYadeliverySession(
            ydPopupDetail
              .querySelector('.yd-popup-list__item')
              .getAttribute('data-address')
          );
          //insert button if needed
          window.twinpxYadeliveryInsertButton();
        }
      }
    }

    //ymaps
    if (window.ymaps && window.ymaps.ready) {
      ymaps.ready(() => {
        //let regionName = prompt('city'); use it if region change is needed
        //check if region exists
        if (!regionName && errorMessage) {
          elemLoader(document.querySelector('#ydPopupMap'), false);
          elemLoader(ydPopupWrapper, false);

          ydPopupContainer.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${errorMessage}</div>`;
          return;
        }

        //geo code
        const myGeocoder = ymaps.geocode(
          regionName.replace(/\u0451/g, '\u0435').replace(/\u0401/g, '\u0415'),
          {
            results: 1,
          }
        );

        myGeocoder.then((res) => {
          pvzPopup.adjustPosition();

          // first result, its coords and bounds
          let firstGeoObject = res.geoObjects.get(0);
          firstGeoObjectCoords = firstGeoObject.geometry.getCoordinates();
          bounds = firstGeoObject.properties.get('boundedBy');
          newBounds = bounds;

          map = new ymaps.Map(
            'ydPopupMap',
            {
              center: firstGeoObjectCoords,
              zoom: 9,
              controls: ['searchControl'],
            },
            {
              suppressMapOpenBlock: true,
            }
          );

          if (window.matchMedia('(min-width: 1077px)').matches) {
            let zoomControl = new ymaps.control.ZoomControl();
            map.controls.add(zoomControl);
          }

          let customBalloonContentLayout =
            ymaps.templateLayoutFactory.createClass(
              `<div class="yd-popup-balloon-content">${BX.message(
                'TWINPX_JS_MULTIPLE_POINTS'
              )}</div>`
            );

          objectManager = new ymaps.ObjectManager({
            clusterize: true,
            clusterBalloonContentLayout: customBalloonContentLayout,
          });

          objectManager.objects.options.set('iconLayout', 'default#image');
          objectManager.objects.options.set(
            'iconImageHref',
            '/bitrix/images/twinpx.yadelivery/yandexPoint.svg'
          );
          objectManager.objects.options.set('iconImageSize', [32, 42]);
          objectManager.objects.options.set('iconImageOffset', [-16, -42]);
          objectManager.clusters.options.set(
            'preset',
            'islands#blackClusterIcons'
          );
          objectManager.objects.events.add(['click'], onObjectEvent);
          objectManager.clusters.events.add(['balloonopen'], onClusterEvent);

          let firstBound = true;

          if (map) {
            //add object manager
            map.geoObjects.add(objectManager);
            //remove preloader
            elemLoader(document.querySelector('#ydPopupMap'), false);
            //map bounds
            map.setBounds(bounds, {
              checkZoomRange: true,
            });
            //events
            map.events.add('boundschange', onBoundsChange);
            map.events.add('click', () => {
              setPopupMode('map');
            });
          }

          function onBoundsChange(e) {
            newBounds = e ? e.get('newBounds') : newBounds;

            if (firstBound) {
              firstBound = false;
              return;
            }

            //wrapper sorted mode
            ydPopupWrapper.classList.add('yd-popup-list-wrapper--sorted');

            //clear sorted pvz
            for (let key in pointsNodesArray) {
              if (pointsNodesArray[key]['sorted'] === true) {
                pointsNodesArray[key]['sorted'] = false;
                pointsNodesArray[key]['node'].classList.remove(
                  'yd-popup-list__item--sorted'
                );
              }
            }

            //items array
            let arr = pointsArray.filter((point) => {
              return (
                point.coords[0] > newBounds[0][0] &&
                point.coords[0] < newBounds[1][0] &&
                point.coords[1] > newBounds[0][1] &&
                point.coords[1] < newBounds[1][1]
              );
            });

            //set items sorted
            arr.forEach((point) => {
              let sortedItem = pointsNodesArray[point.id]['node'];
              pointsNodesArray[point.id]['sorted'] = true;
              if (sortedItem) {
                sortedItem.classList.add('yd-popup-list__item--sorted');
              }
            });
          }

          //send to the server
          (async () => {
            //get offices
            let formData = new FormData();
            formData.set('action', 'getPoints');
            formData.set(
              'fields',
              `lat-from=${bounds[0][0]}&lat-to=${bounds[1][0]}&lon-from=${bounds[0][1]}&lon-to=${bounds[1][1]}&payment=${payment}`
            );

            let controller = new AbortController();
            let response;

            setTimeout(() => {
              if (!response) {
                controller.abort();
              }
            }, fetchTimeout);

            try {
              response = await fetch(window.twinpxYadeliveryFetchURL, {
                method: 'POST',
                body: formData,
                signal: controller.signal,
              });
              let result = await response.json();

              //remove preloader
              elemLoader(ydPopupWrapper, false);

              if (result && typeof result === 'object') {
                if (result.STATUS === 'Y') {
                  if (result.ERRORS) {
                    pointsError(result.ERRORS);
                  } else {
                    if (result.POINTS) {
                      //fill pointsArray
                      pointsArray = result.POINTS;

                      //list
                      let pointsFlag,
                        objectsArray = [];

                      result.POINTS.forEach((point) => {
                        if (!point.id) return;

                        pointsFlag = true;

                        //placemark
                        objectsArray.push({
                          type: 'Feature',
                          id: point.id,
                          geometry: {
                            type: 'Point',
                            coordinates: point.coords,
                          },
                        });

                        //list
                        let item = createPointsItem(point);
                        ydPopupWrapper.appendChild(item);

                        //push to nodes array
                        pointsNodesArray[point.id] = {
                          node: item,
                          sorted: false,
                        };
                      });

                      objectManager.add(objectsArray);

                      if (!pointsFlag) {
                        pointsError();
                      }

                      //map bounds
                      if (map) {
                        centerCoords = map.getCenter();
                      }

                      //if the map was moved while offices were loading
                      if (
                        ydPopupWrapper.classList.contains(
                          'yd-popup-list-wrapper--sorted'
                        )
                      ) {
                        onBoundsChange();
                      }
                    } else {
                      pointsError(BX.message('TWINPX_JS_EMPTY_OFFER'));
                    }
                  }
                } else {
                  pointsError(BX.message('TWINPX_JS_NO_RESPONSE'));
                }
              }
            } catch (err) {
              pointsError();
            }
          })();
        });
      });
    }
    //back button
    ydPopupList
      .querySelector('.yd-popup-list__back')
      .addEventListener('click', (e) => {
        setPopupMode('list');
        //show sorted
        if (Object.values(pointsNodesArray).find((value) => value.sorted)) {
          ydPopupWrapper.classList.add('yd-popup-list-wrapper--sorted');
        }
      });

    topBtns = document.querySelectorAll(
      '#ydPopup .yd-popup-mobile-top .yd-popup-btn'
    );

    topBtns[0].addEventListener('click', (e) => {
      e.preventDefault();
      if (ydPopupContainer.classList.contains('yd-popup--map')) return;

      setBtnActive(0);
      setPopupMode('map');
    });

    topBtns[1].addEventListener('click', (e) => {
      e.preventDefault();
      if (ydPopupContainer.classList.contains('yd-popup--list')) return;

      setBtnActive(1);
      setPopupMode('list');

      ydPopupWrapper.scrollTo({
        top: 0,
      });
    });

    document
      .querySelector('#ydPopup .yd-popup-mobile-bottom .yd-popup-btn')
      .addEventListener('click', (e) => {
        e.preventDefault();
        pvzPopup.destroy();
        pageScroll(true);
      });
  }

  function validateControl(item, control) {
    let regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

    //required
    if (
      control.getAttribute('required') === '' &&
      control.value.trim() !== ''
    ) {
      //tel length < 13
      if (control.getAttribute('type') === 'tel') {
        let digits = control.value.match(/\d+(\.\d+)?/g);
        if (
          control.value.trim() !== '' &&
          digits &&
          digits.join('').length < 13
        ) {
          item.classList.remove('invalid');
        }
      } else {
        item.classList.remove('invalid');
      }
    }

    //email
    Object.keys(regExp).forEach((key) => {
      if (control.getAttribute('type') === key) {
        if (
          (control.value.trim() !== '' && regExp[key].test(control.value)) ||
          (control.getAttribute('required') !== '' &&
            control.value.trim() === '')
        ) {
          item.classList.remove('invalid');
        }
      }
    });
  }

  function errorFormValidation(formElem) {
    let regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

    let focusElement;

    //required
    formElem.querySelectorAll('[required]').forEach((reqInput) => {
      if (reqInput.value.trim() === '') {
        if (!focusElement) {
          focusElement = reqInput;
        }
        reqInput.closest('.b-float-label').classList.add('invalid');
      } else {
        reqInput.closest('.b-float-label').classList.remove('invalid');
      }
    });

    //email
    Object.keys(regExp).forEach((key) => {
      formElem.querySelectorAll(`[type=${key}]`).forEach((input) => {
        //required
        if (
          input.getAttribute('required') === '' ||
          input.value.trim() !== ''
        ) {
          if (!regExp[key].test(input.value)) {
            if (!focusElement) {
              focusElement = input;
            }
            input.closest('.b-float-label').classList.add('invalid');
          } else {
            input.closest('.b-float-label').classList.remove('invalid');
          }
        }
      });
    });

    //tel length < 13
    formElem.querySelectorAll('[type=tel]').forEach((telInput) => {
      let digits = telInput.value.match(/\d+(\.\d+)?/g);
      if (
        telInput.getAttribute('required') === '' ||
        telInput.value.trim() !== ''
      ) {
        if (!digits || digits.join('').length >= 13) {
          if (!focusElement) {
            focusElement = telInput;
          }
          telInput.closest('.b-float-label').classList.add('invalid');
        } else {
          telInput.closest('.b-float-label').classList.remove('invalid');
        }
      }
    });

    if (focusElement) {
      focusElement.focus();
    }

    return focusElement;
  }
}

function twinpxYadeliverySerializeForm(form) {
  const obj = Object.fromEntries(new FormData(form));

  let result = '';

  Object.keys(obj).forEach((key) => {
    result += `&${key}=${obj[key]}`;
  });

  return result.substring(1);
}
