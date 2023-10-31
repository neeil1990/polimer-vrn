window.twinpxYadeliveryFetchURL =
  window.twinpxYadeliveryFetchURL ||
  '/bitrix/tools/twinpx.yadelivery/admin/ajax.php';
  
//window.twinpxYadeliveryYmapsAPI = false;
window.twinpxYadeliveryYmapsAPI = window.twinpxYadeliveryYmapsAPI || window.twinpxYadeliveryYmapsAPI === undefined ? true : false;

window.newDeliveryPopupOnload = function () {
  const ydContent = document.querySelector('#newDelivery .yd-popup-content'),
    ydForm = document.querySelector('#newDelivery .yd-popup-form'),
    ydBody = ydContent.querySelector('.yd-popup-body'),
    ydOffers = ydBody.querySelector('.yd-popup-offers'),
    ydError = ydContent.querySelector('.yd-popup-error'),
    regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

  //fill button
  twinpxYadeliveryFillbutton(ydForm);

  //paysystem select
  twinpxYadeliveryPaysystemSelect(ydForm);
  
  //tabs
  twinpxYadeliveryTabs('newDeliveryContent');
  
  function twinpxYadeliveryTabs(winId) {
    let tabs = document.querySelector(`#${winId} .yd-popup-tabs`);
    let navItems = tabs.querySelectorAll('.yd-popup-tabs__nav__item');
    let tabItems = tabs.querySelectorAll('.yd-popup-tabs__tabs__item');
    
    navItems.forEach(navItem => {
      navItem.addEventListener('click', (e) => {
        e.preventDefault();
        //class
        navItems.forEach(n => {
          n.classList.remove('yd-popup-tabs__nav__item--active');
        });
        navItem.classList.add('yd-popup-tabs__nav__item--active');
        //tab
        tabItems.forEach(t => {
          t.classList.remove('yd-popup-tabs__tabs__item--active');
        });
        tabs.querySelector(`.yd-popup-tabs__tabs__item[data-tab=${navItem.getAttribute('data-tab')}]`).classList.add('yd-popup-tabs__tabs__item--active');
      });
    });
  }

  async function sendOffer(jsonStr) {
    let formData, response;

    formData = new FormData();
    formData.set('action', 'setOfferPrice');
    formData.set('fields', jsonStr);

    response = await fetch(window.twinpxYadeliveryFetchURL, {
      method: 'POST',
      body: formData,
    });

    return response.json();
  }

  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  elemLoader(ydContent, false);

  //click offer
  ydOffers.addEventListener('click', (e) => {
    if (
      e.target.classList.contains('yd-popup-offers__item') ||
      e.target.closest('.yd-popup-offers__item')
    ) {
      let item = e.target.classList.contains('yd-popup-offers__item')
        ? e.target
        : e.target.closest('.yd-popup-offers__item');
      let data = item.getAttribute('data-json');

      (async () => {
        let formData = new FormData(),
          response,
          result;

        formData.append('action', 'setDelivery');
        formData.append('data', data);

        //preloader
        ydOffers.classList.remove('yd-popup-offers--animate');
        elemLoader(ydOffers, true);
        ydOffers.innerHTML = '';

        let controller = new AbortController();

        setTimeout(() => {
          if (!response) {
            controller.abort();
          }
        }, 20000);

        response = await fetch(window.twinpxYadeliveryFetchURL, {
          method: 'POST',
          body: formData,
        });
        result = await response.json();

        if (result.STATUS !== 'Y') {
          window.ydConfirmer.destroy();
          if (result.RELOAD) {
            window.location.href = result.RELOAD;
          } else {
            window.location.reload();
          }
        } else {
          //error
          //ydOffers.innerHTML = result.ERROR;
          ydError.innerHTML = result.ERROR;
          ydBody.classList.remove('yd-popup-body--result');
          ydOffers.classList.remove('yd-popup-offers--animate');
          elemLoader(ydOffers, true);
          ydOffers.innerHTML = '';
        }
      })();
    }
  });

  //float label input
  ydContent
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
          validate(item, control);
        }
      });
    });

  function validate(item, control) {
    //required
    if (
      control.getAttribute('required') === '' &&
      control.value.trim() !== ''
    ) {
      item.classList.remove('invalid');
    }

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

  //input mask
  if (window.BX && window.BX.MaskedInput) {
    new BX.MaskedInput({
      mask: '+7 (999) 999 9999',
      input: BX('ydFormPhone'),
      placeholder: '_',
    });
  }

  //check form
  if (!ydContent.querySelector('form')) return;

  ydContent
    .querySelector('form')
    .querySelectorAll('textarea')
    .forEach(function (textarea) {
      textarea.addEventListener('input', function () {
        this.style.height = this.scrollHeight + 'px';
      });
    });

  ydContent.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    let focusElement;

    //clear error
    ydError.innerHTML = '';

    //required
    form.querySelectorAll('[required]').forEach((reqInput) => {
      if (reqInput.value.trim() === '') {
        if (!focusElement) {
          focusElement = reqInput;
        }
        reqInput.closest('.b-float-label').classList.add('invalid');
      } else {
        reqInput.closest('.b-float-label').classList.remove('invalid');
      }
    });

    Object.keys(regExp).forEach((key) => {
      form.querySelectorAll(`[type=${key}]`).forEach((input) => {
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

    //check offer
    if (document.getElementById('ydFormOrder')) {
      let orderInput = document.getElementById('ydFormOrder'),
        orderId = orderInput.value,
        formData = new FormData(),
        response,
        result;

      formData.append('action', 'checkOrder');
      formData.append('orderId', orderId);

      //preloader
      elemLoader(ydForm, true);

      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
      });
      result = await response.json();

      elemLoader(ydForm, false);

      if (result.STATUS !== 'Y') {
        if (!focusElement) {
          focusElement = orderInput;
        }
        orderInput.closest('.b-float-label').classList.add('invalid');
      } else {
        orderInput.closest('.b-float-label').classList.remove('invalid');
      }
    }

    function offersError(message) {
      ydError.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${message}</div>`;
      ydBody.classList.remove('yd-popup-body--result');
      ydOffers.classList.remove('yd-popup-offers--animate');
      elemLoader(ydOffers, true);
      ydOffers.innerHTML = '';
    }

    //focus
    if (focusElement) {
      focusElement.focus();
    } else {
      //send
      ydBody.classList.add('yd-popup-body--result');

      //fetch request
      let formData = new FormData();
      formData.set('action', 'newGetOffer');
      formData.set('form', $(form).serialize());

      let controller = new AbortController();
      let response;

      setTimeout(() => {
        if (!response) {
          controller.abort();
        }
      }, 20000);

      try {
        response = await fetch(window.twinpxYadeliveryFetchURL, {
          method: 'POST',
          body: formData,
          signal: controller.signal,
        });

        let result = await response.json();

        if (result && typeof result === 'object') {
          if (result.STATUS === 'Y') {
            if (result.ERRORS) {
              offersError(result.ERRORS);
            } else if (result.OFFERS) {
              let html = '<div class="yd-popup-offers__wrapper">';

              result.OFFERS.forEach(({ json, date, time, price }) => {
                html += `<div class="yd-popup-offers__item" data-json='${json}'>
                <div class="yd-popup-offers__info">
                    <span class="yd-popup-offers__date"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/calendar.svg)"></i>${date}</span>
                    <span class="yd-popup-offers__time"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/clock.svg)"></i>${time}</span>
                  </div>
                <b class="yd-popup-offers__price">${price}</b>
                <a href="#" class="twpx-ui-btn">${BX.message(
                  'TWINPX_JS_SELECT'
                )}</a>
              </div>`;
              });

              html += '</div>';

              //remove preloader
              elemLoader(ydOffers, false);

              //html
              ydOffers.innerHTML = html;

              setTimeout(() => {
                ydOffers.classList.add('yd-popup-offers--animate');
              }, 0);
            } else {
              offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
            }
          } else {
            offersError(BX.message('TWINPX_JS_ERROR'));
          }
        }

        //ydOffers.innerHTML = result;
        setTimeout(() => {
          ydOffers.classList.add('yd-popup-offers--animate');
        }, 100);
      } catch (err) {
        offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
      }
    }
  });
};

window.newDeliveryPvzPopupOnload = function (orderId, pvzId, chosenAddress) {
  const ydContent = document.querySelector('#newDeliveryPvz .yd-popup-content'),
    ydForm = document.querySelector('#newDeliveryPvz .yd-popup-form'),
    ydBody = ydContent.querySelector('.yd-popup-body'),
    ydContainer = ydBody.querySelector('.yd-popup-map-container'),
    ydError = ydContent.querySelector('.yd-popup-error'),
    regExp = {
      email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
      //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
    };

  let payment;

  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  elemLoader(ydContent, false);

  //fill button
  twinpxYadeliveryFillbutton(ydForm);

  //paysystem select
  twinpxYadeliveryPaysystemSelect(ydForm);
  
  //tabs
  twinpxYadeliveryTabs('newDeliveryPvz');
  
  function twinpxYadeliveryTabs(winId) {
    let tabs = document.querySelector(`#${winId} .yd-popup-tabs`);
    let navItems = tabs.querySelectorAll('.yd-popup-tabs__nav__item');
    let tabItems = tabs.querySelectorAll('.yd-popup-tabs__tabs__item');
    
    navItems.forEach(navItem => {
      navItem.addEventListener('click', (e) => {
        e.preventDefault();
        //class
        navItems.forEach(n => {
          n.classList.remove('yd-popup-tabs__nav__item--active');
        });
        navItem.classList.add('yd-popup-tabs__nav__item--active');
        //tab
        tabItems.forEach(t => {
          t.classList.remove('yd-popup-tabs__tabs__item--active');
        });
        tabs.querySelector(`.yd-popup-tabs__tabs__item[data-tab=${navItem.getAttribute('data-tab')}]`).classList.add('yd-popup-tabs__tabs__item--active');
      });
    });
  }

  //float label input
  ydContent
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
          validate(item, control);
        }
      });
    });

  function validate(item, control) {
    //required
    if (
      control.getAttribute('required') === '' &&
      control.value.trim() !== ''
    ) {
      item.classList.remove('invalid');
    }

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

  ydContent
    .querySelector('form')
    .querySelectorAll('textarea')
    .forEach(function (textarea) {
      textarea.addEventListener('input', function () {
        this.style.height = this.scrollHeight + 'px';
      });
    });

  ydContent.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    let focusElement;

    //clear error
    ydError.innerHTML = '';

    //required
    form.querySelectorAll('[required]').forEach((reqInput) => {
      if (reqInput.value.trim() === '') {
        if (!focusElement) {
          focusElement = reqInput;
        }
        reqInput.closest('.b-float-label').classList.add('invalid');
      } else {
        reqInput.closest('.b-float-label').classList.remove('invalid');
      }
    });

    Object.keys(regExp).forEach((key) => {
      form.querySelectorAll(`[type=${key}]`).forEach((input) => {
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

    //check offer
    if (document.getElementById('ydFormPvzOrder')) {
      let orderInput = document.getElementById('ydFormPvzOrder'),
        orderId = orderInput.value,
        formData = new FormData(),
        response,
        result;

      formData.append('action', 'checkOrder');
      formData.append('orderId', orderId);

      //preloader
      elemLoader(ydForm, true);

      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
      });
      result = await response.json();

      elemLoader(ydForm, false);

      if (result.STATUS !== 'Y') {
        if (!focusElement) {
          focusElement = orderInput;
        }
        orderInput.closest('.b-float-label').classList.add('invalid');
      } else {
        orderInput.closest('.b-float-label').classList.remove('invalid');
      }
    }

    //focus
    if (focusElement) {
      focusElement.focus();
    } else {
      payment = form.querySelector('[name="PAY_TYPE"]').value;

      //send
      ydBody.classList.add('yd-popup-body--result');

      //create form serialized inputs
      let fields = '';
      for (let i = 0; i < form.elements.length; i++) {
        if (fields) {
          fields += '&';
        }
        fields += `${form.elements[i].getAttribute('name')}=${
          form.elements[i].value
        }`;
      }

      //show map
      let ydPopupContainer,
        ydPopupList,
        ydPopupWrapper,
        ydPopupDetail,
        map,
        objectManager,
        bounds,
        firstGeoObjectCoords,
        regionName,
        pvzPopup,
        centerCoords,
        pointsArray,
        pointsNodesArray = {},
        newBounds = [],
        container = `<div class="yd-popup-container yd-popup--map ${
          pvzId ? 'yd-popup--simple' : ''
        }">
          <div id="ydPopupMap" class="yd-popup-map load-circle"></div>
          <div class="yd-popup-list">
            <div class="yd-popup-list__back">${BX.message(
              'TWINPX_JS_RETURN'
            )}</div>
            <div class="yd-popup-list__chosen">${BX.message(
              'TWINPX_JS_CHOSEN'
            )}</div>
            <div class="yd-popup-list-wrapper load-circle"></div>
            <div class="yd-popup-list-detail"></div>
          </div>
        </div>`;

      let cityInput = document.getElementById('ydFormPvzCity');
      regionName = cityInput ? cityInput.value : '';

      ydContainer.innerHTML = container;
      onPopupShow(pvzId);

      function pointsError(error) {
        ydPopupWrapper.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
        elemLoader(ydPopupDetail, false);
      }

      function offersError(error) {
        ydPopupDetail.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
        elemLoader(ydPopupDetail, false);
      }

      function onObjectEvent(e) {
        let id = e.get('objectId');

        let pointObject = pointsArray.find((p) => {
          return p.id === id;
        });

        clickPlacemark(id, pointObject.address, map, pointObject.coords);
      }

      function onClusterEvent() {
        //active button
        topBtns[0].classList.remove('yd-popup-btn--active');
        topBtns[1].classList.add('yd-popup-btn--active');
        //map-list mode
        ydPopupContainer.classList.remove('yd-popup--map');
        ydPopupContainer.classList.remove('yd-popup--detail');
        ydPopupContainer.classList.add('yd-popup--list');
      }

      async function clickPlacemark(id, address, map, coords) {
        map.panTo(coords).then(() => {
          map.setZoom(16);
        });

        //set detail mode
        ydPopupContainer.classList.add('yd-popup--detail');
        ydPopupContainer.classList.remove('yd-popup--map');
        ydPopupContainer.classList.remove('yd-popup--list');

        //add preloader
        elemLoader(ydPopupDetail, true);
        ydPopupDetail.innerHTML = '';

        //get offers
        let formData = new FormData();
        formData.set('action', 'pvzOfferAdmin');
        formData.set('fields', `${fields}&id=${id}&address=${address}`);

        let controller = new AbortController();
        let response;

        setTimeout(() => {
          if (!response) {
            controller.abort();
          }
        }, 20000);

        try {
          response = await fetch(window.twinpxYadeliveryFetchURL, {
            method: 'POST',
            body: formData,
            signal: controller.signal,
          });

          let result = await response.json();

          if (result && typeof result === 'object') {
            if (result.STATUS === 'Y') {
              if (result.ERRORS) {
                offersError(result.ERRORS);
              } else {
                if (result.OFFERS) {
                  let html = '';

                  html = `<div class="yd-h3">${BX.message(
                    'TWINPX_JS_SELECT'
                  )}</div>`;

                  result.OFFERS.forEach(({ json, date, time, price }) => {
                    html += `
                      <div class="yd-popup-offer" data-json='${json}'>
                        <div class="yd-popup-offer__info">
                          <div class="yd-popup-offer__date">${date}</div>
                          <div class="yd-popup-offer__time">${time}</div>
                        </div>
                        <div class="yd-popup-offer__price">${price}</div>
                        <div class="twpx-ui-btn">${BX.message(
                          'TWINPX_JS_SELECT'
                        )}</div>
                      </div>
                    `;
                  });

                  //remove preloader
                  elemLoader(ydPopupDetail, false);
                  //html
                  ydPopupDetail.innerHTML = html;
                } else {
                  offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
                }
              }
            } else {
              offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
            }
          }

          //item content
          let item = ydPopupList
            .querySelector(`[data-id="${id}"]`)
            .cloneNode(true);
          ydPopupDetail.prepend(item);
          ydPopupDetail.scrollTo({
            top: 0,
          });
        } catch (err) {
          offersError(BX.message('TWINPX_JS_ERROR'));
        }
      }

      function onPopupShow(pvzId) {
        ydPopupContainer = document.querySelector(
          '#newDeliveryPvz .yd-popup-container'
        );
        ydPopupList = ydPopupContainer.querySelector('.yd-popup-list');
        ydPopupWrapper = ydPopupList.querySelector('.yd-popup-list-wrapper');
        ydPopupDetail = ydPopupList.querySelector('.yd-popup-list-detail');

        pointsArray = [];

        //choose offer event
        ydPopupDetail.addEventListener('click', async (e) => {
          e.preventDefault();
          if (e.target.classList.contains('twpx-ui-btn')) {
            let btn = e.target;
            let offerElem = btn.closest('.yd-popup-offer');
            let jsonStr = offerElem.getAttribute('data-json'); //string

            let result = await sendOffer(jsonStr);

            if (result && result.STATUS === 'Y') {
              if (result.RELOAD) {
                document.location.href = result.RELOAD;
              } else {
                document.location.reload(); //��������� ��������
              }
            }
          }
        });

        //ymaps
        if (window.ymaps && window.ymaps.ready) {
          ymaps.ready(() => {
            //geo code
            const myGeocoder = ymaps.geocode(regionName, {
              results: 1,
            });

            myGeocoder.then((res) => {
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
                  controls: ['searchControl', 'zoomControl'],
                },
                {
                  suppressMapOpenBlock: true,
                }
              );

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
              //objectManager.clusters.events.add(['click'], onClusterEvent);

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
                }, 20000);

                try {
                  let response = await fetch(window.twinpxYadeliveryFetchURL, {
                    method: 'POST',
                    body: formData,
                  });
                  let result = await response.json();

                  //remove preloader
                  elemLoader(ydPopupWrapper, false);

                  if (result && result.STATUS === 'Y' && result.POINTS) {
                    //fill pointsArray
                    pointsArray = result.POINTS;

                    //list
                    let pointsFlag,
                      objectsArray = [],
                      featureOptions = {};

                    result.POINTS.forEach(
                      ({ id, title, type, schedule, address, coords }) => {
                        if (!id) return;

                        pointsFlag = true;

                        if (pvzId && id === pvzId) {
                          featureOptions.iconImageHref =
                            '/bitrix/images/twinpx.yadelivery/chosenPlacemark.svg';
                          featureOptions.iconImageSize = [48, 63];
                          featureOptions.iconImageOffset = [-24, -63];
                        } else {
                          featureOptions = {};
                        }

                        //placemark
                        objectsArray.push({
                          type: 'Feature',
                          id: id,
                          geometry: {
                            type: 'Point',
                            coordinates: coords,
                          },
                          options: featureOptions,
                        });

                        //list
                        let item = document.createElement('div');
                        item.className = 'yd-popup-list__item';
                        item.setAttribute('data-id', id);

                        item.innerHTML = `
                            <div class="yd-popup-list__title">${title}</div>
                            <div class="yd-popup-list__text">
                              <span>${type}</span> ${schedule}<br>
                              ${address}
                            </div>
                            <div class="twpx-ui-btn">${BX.message(
                              'TWINPX_JS_SELECT'
                            )}</div>
                          `;
                        item.addEventListener('click', (e) => {
                          if (e.target.classList.contains('twpx-ui-btn')) {
                            //click button
                            clickPlacemark(id, address, map, coords);
                          } else if (
                            window.matchMedia('(min-width: 1077px)').matches
                          ) {
                            //pan map on desktop
                            map.panTo(coords).then(() => {
                              map.setZoom(16);
                            });
                          }
                        });
                        ydPopupWrapper.appendChild(item);

                        //push to nodes array
                        pointsNodesArray[id] = {
                          node: item,
                          sorted: false,
                        };
                      }
                    );

                    objectManager.add(objectsArray);

                    if (!pointsFlag) {
                      pointsError();
                    }

                    if (pvzId) {
                      let chosenObject = pointsArray.find(
                        (p) => p.id === pvzId
                      );
                      if (chosenObject) {
                        clickPlacemark(
                          pvzId,
                          chosenObject.address,
                          map,
                          chosenObject.coords
                        );
                      } else {
                        let chosenBtn = ydPopupList.querySelector(
                          '.yd-popup-list__chosen'
                        );
                        chosenBtn.removeEventListener('click', clickChosen);
                        chosenBtn.textContent = `${BX.message(
                          'TWINPX_JS_CHOSEN_ERROR'
                        )} ${chosenAddress ? chosenAddress : ''}`;
                        chosenBtn.className = 'yd-popup-list__chosen-error';
                        ydPopupWrapper.style.height = `calc(100% - ${
                          ydPopupList.querySelector(
                            '.yd-popup-list__chosen-error'
                          ).clientHeight
                        }px - 15px)`;
                      }
                    } else if (
                      ydPopupWrapper.classList.contains(
                        'yd-popup-list-wrapper--sorted'
                      )
                    ) {
                      //if the map was moved while offices were loading
                      onBoundsChange();
                    }

                    //map bounds
                    if (map) {
                      centerCoords = map.getCenter();
                    }
                  } else {
                    pointsError(result.ERRORS);
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
            ydPopupContainer.classList.remove('yd-popup--detail');
            ydPopupContainer.classList.remove('yd-popup--map');
            ydPopupContainer.classList.add('yd-popup--list');
            //show sorted
            ydPopupWrapper.classList.add('yd-popup-list-wrapper--sorted');
          });

        //chosen button event
        let chosenBtn = ydPopupList.querySelector('.yd-popup-list__chosen');
        if (chosenBtn) {
          chosenBtn.addEventListener('click', clickChosen);
        }

        function clickChosen(e) {
          e.preventDefault();
          if (pvzId) {
            let chosenObject = pointsArray.find((p) => p.id === pvzId);
            if (chosenObject) {
              clickPlacemark(
                pvzId,
                chosenObject.address,
                map,
                chosenObject.coords
              );
            }
          }
        }
      }
    }
  });

  async function sendOffer(jsonStr) {
    let formData, controller, response;

    formData = new FormData();
    formData.set('action', 'setOfferPriceAdmin');
    formData.set('fields', jsonStr);

    controller = new AbortController();

    setTimeout(() => {
      if (!response) {
        controller.abort();
      }
    }, 20000);

    response = await fetch(window.twinpxYadeliveryFetchURL, {
      method: 'POST',
      body: formData,
    });

    return response.json();
  }
};

window.twinpxYadeliveryPaysystemSelect = function (ydForm) {
  let paysystemSelect = ydForm.querySelector('select');

  if (!paysystemSelect) {
    return;
  }

  paysystemSelect.addEventListener('change', async () => {
    let formData,
      response,
      result,
      orderInput = ydForm.querySelector('[name="ORDER_ID"]');

    formData = new FormData();
    formData.set('action', 'getSumm');
    formData.set('paysystem', paysystemSelect.value);
    formData.set('ORDER_ID', orderInput.value);

    try {
      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
      });

      result = await response.json();

      if (result && typeof result === 'object') {
        if (result.STATUS === 'Y' && result.SUMM) {
          let priceInput = ydForm.querySelector('#ydFormPrice');
          if (priceInput) {
            priceInput.value = result.SUMM;
            priceInput.parentNode
              .querySelector('label')
              .classList.add('active');
          }
        }
      }
    } catch (err) {
      //throw err;
    }
  });
};

window.twinpxYadeliveryFillbutton = function (ydForm) {
  ydForm
    .querySelector('.yd-popup-form-fillbutton')
    .addEventListener('click', async (e) => {
      e.preventDefault();

      let formData,
        response,
        result,
        orderInput = ydForm.querySelector('[name="ORDER_ID"]');

      function elemLoader(elem, flag) {
        flag
          ? elem.classList.add('load-circle')
          : elem.classList.remove('load-circle');
      }

      //preloader
      elemLoader(ydForm, true);

      formData = new FormData();
      formData.set('action', 'getOrderData');
      formData.set('id', orderInput.value);

      response = await fetch(window.twinpxYadeliveryFetchURL, {
        method: 'POST',
        body: formData,
      });

      result = await response.json();

      elemLoader(ydForm, false);

      if (result && typeof result === 'object') {
        if (result.STATUS === 'Y') {
          if (result.FIELDS) {
            //fill the form controls
            Object.keys(result.FIELDS).forEach((key) => {
              let formControl = ydForm.querySelector(`[name="${key}"]`);
              if (
                formControl &&
                Boolean(result.FIELDS[key]) &&
                result.FIELDS[key].trim() !== ''
              ) {
                let block = formControl.closest('.b-float-label'),
                  label = block.querySelector('label');
                block.classList.remove('invalid');
                formControl.value = result.FIELDS[key];
                label ? label.classList.add('active') : undefined;
              }
            });
          }
        } else {
          orderInput.focus();
          orderInput.closest('.b-float-label').classList.add('invalid');
        }
      }
    });
};

window.twinpxYadeliveryPopupSettings = {
  width: 'auto',
  height: 'auto',
  min_width: 300,
  min_height: 300,
  zIndex: 100,
  autoHide: true,
  offsetTop: 1,
  offsetLeft: 0,
  lightShadow: true,
  closeIcon: true,
  closeByEsc: true,
  draggable: {
    restrict: false,
  },
  overlay: {
    backgroundColor: 'black',
    opacity: '80',
  },
};

//function from twinpx_delivery_offers.php
function newOffer(id) {
  function offersError(error) {
    document.getElementById(
      'popup-window-content-newOffer'
    ).innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
  }

  var Confirmer = new BX.PopupWindow('newOffer', null, {
    content: `<div id="context_${id}"><div id="showOffer_${id}" class="loading list__offer">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_SELECT_PERIOD')), //BX.create("span", {html: BX.message('TWINPX_JS_SELECT_PERIOD'), 'props': {'className': 'popup-window-titlebar-text'}})
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: async function () {
        function newOfferElemLoader(flag) {
          flag
            ? newOfferElem.classList.add('load-circle')
            : newOfferElem.classList.remove('load-circle');
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
                  <a href="#" class="twpx-ui-btn">${selectMessage}</a>
                </div>`;
          });

          html += '</div>';

          return html;
        }

        document
          .getElementById('popup-window-content-newOffer')
          .addEventListener('click', (e) => {
            if (
              e.target.classList.contains('yd-popup-offers__item') ||
              e.target.closest('.yd-popup-offers__item')
            ) {
              let itemNode = e.target.classList.contains(
                  'yd-popup-offers__item'
                )
                  ? e.target
                  : e.target.closest('.yd-popup-offers__item'),
                fields = itemNode.getAttribute('data-json');

              setPrice(fields, id);
              Confirmer.destroy();
            }
          });

        let formData = new FormData(),
          controller = new AbortController(),
          response,
          result,
          html = '';

        //fetch request
        formData.set('action', 'new');
        formData.set('itemID', id);

        setTimeout(() => {
          if (!response) {
            controller.abort();
          }
        }, 20000);

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
              } else {
                if (result.OFFERS) {
                  newOfferElem = document.getElementById(`showOffer_${id}`);
                  //remove preloader
                  newOfferElemLoader(false);

                  //html
                  html = createOffersHtml(result.OFFERS);
                  newOfferElem.innerHTML = html;

                  //effect
                  setTimeout(() => {
                    showOfferElem.classList.add('yd-popup-offers--animate');
                  }, 0);

                  Confirmer.adjustPosition();
                } else {
                  offersError(BX.message('TWINPX_JS_EMPTY_OFFER'));
                }
              }
            } else {
              offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
            }
          }
        } catch (err) {
          offersError(BX.message('TWINPX_JS_NO_RESPONSE'));
        }
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function updateOffer(id) {
  var Confirmer = new BX.PopupWindow(`update${id}`, null, {
    content: `<div id="context_${id}"><div id="showOffer_${id}" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_UPDATES')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { itemID: id, action: 'update' },
          function (data) {
            node = document.getElementById(`showOffer_${id}`);
            node.innerHTML = data;
            node.classList.remove('loading');

            Confirmer.adjustPosition();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function cancelOffer(id) {
  var Confirmer = new BX.PopupWindow(`cancel_${id}`, null, {
    content: `<div id="cancel_context_${id}"><div id="showOffer_${id}" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_CANCEL')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { itemID: id, action: 'cancel' },
          function (data) {
            node = document.getElementById(`showOffer_${id}`);
            node.innerHTML = data;
            node.classList.remove('loading');

            Confirmer.adjustPosition();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function printBarcode(id) {
  var Confirmer = new BX.PopupWindow(`barcode${id}`, null, {
    content: `<div id="context_${id}"><div id="printBarcode_${id}" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_BARKOD')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { itemID: id, action: 'barcode' },
          function (data) {
            node = document.getElementById(`printBarcode_${id}`);
            node.innerHTML = data;
            node.classList.remove('loading');

            Confirmer.adjustPosition();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function printDocument(id) {
  var Confirmer = new BX.PopupWindow(`document${id}`, null, {
    content: `<div id="context_${id}"><div id="printDocument_${id}" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_ACT')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { itemID: id, action: 'document' },
          function (data) {
            node = document.getElementById(`printDocument_${id}`);
            node.innerHTML = data;
            node.classList.remove('loading');

            Confirmer.adjustPosition();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function updateAll() {
  var Confirmer = new BX.PopupWindow('updateAll', null, {
    content: `<div id="context"><div id="update_content" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_UPDATE')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { action: 'updateAll' },
          function (data) {
            node = document.getElementById(`update_content`);
            node.innerHTML = data;
            node.classList.remove('loading');
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
        document.location.reload();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

function archiveOffer(id) {
  var Confirmer = new BX.PopupWindow(`archive_${id}`, null, {
    content: `<div id="archive_context_${id}"><div id="showOffer_${id}" class="loading">${BX.message(
      'TWINPX_JS_LOADING'
    )}</div></div>`,
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_ARHIVE')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        $.post(
          window.twinpxYadeliveryFetchURL,
          { itemID: id, action: 'archive' },
          function (data) {
            node = document.getElementById(`showOffer_${id}`);
            node.innerHTML = data;
            node.classList.remove('loading');

            Confirmer.adjustPosition();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
      },
    },
    buttons: [
      new BX.PopupWindowButton({
        text: BX.message('TWINPX_JS_CLOSE'),
        className: 'link-cancel',
        events: {
          click: function () {
            this.popupWindow.close();
            document.location.reload();
          },
        },
      }),
    ],
  });
  Confirmer.show();
}

async function setPrice(fields, id) {
  let formData = new FormData();
  formData.set('action', 'offer');
  formData.set('fields', fields);
  formData.set('id', id);

  await fetch(window.twinpxYadeliveryFetchURL, {
    method: 'POST',
    body: formData,
  });

  document.location.reload();
}

function newDelivery(orderId) {
  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  window.ydConfirmer = new BX.PopupWindow('newDelivery', null, {
    content:
      '<div id="newDeliveryContent" class="yd-popup-content load-circle"></div>',
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_CREATE')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        pageScroll(false);
        $.post(
          window.twinpxYadeliveryFetchURL,
          { action: 'newDelivery', id: orderId },
          function (data) {
            node = document.getElementById(`newDeliveryContent`);
            node.innerHTML = data;
            elemLoader(node, false);
            window.ydConfirmer.adjustPosition();
            newDeliveryPopupOnload();
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
        pageScroll(true);
      },
    },
    buttons: [],
  });
  window.ydConfirmer.show();
}

function newDeliveryPvz(orderId, pvzId, chosenAddress) {
  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  window.ydConfirmerPvz = new BX.PopupWindow('newDeliveryPvz', null, {
    content:
      '<div id="newDeliveryContentPvz" class="yd-popup-content load-circle"></div>',
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_CREATE')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        pageScroll(false);
        
        //show error if there is no api ymaps key
        if (!window.twinpxYadeliveryYmapsAPI) {
          document
            .querySelector('#newDeliveryContentPvz').classList.remove('load-circle');
          document
            .querySelector('#newDeliveryContentPvz').innerHTML = `<div class="yd-popup-error__message">
            <i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>
            ${BX.message('TWINPX_JS_NO_YMAP_KEY')}
          </div>`;
          
          return;
        }

        $.post(
          window.twinpxYadeliveryFetchURL,
          { action: 'newDeliveryPvz', id: orderId },
          function (data) {
            node = document.getElementById(`newDeliveryContentPvz`);
            node.innerHTML = data;
            elemLoader(node, false);
            window.ydConfirmerPvz.adjustPosition();
            newDeliveryPvzPopupOnload(orderId, pvzId, chosenAddress);
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
        pageScroll(true);
      },
    },
  });
  window.ydConfirmerPvz.show();
}

function pageScroll(flag) {
  flag
    ? document.querySelector('body').classList.remove('no-scroll')
    : document.querySelector('body').classList.add('no-scroll');
}

function getTitleContent(text) {
  return BX.create('span', {
    html: text,
    props: { className: 'popup-window-titlebar-text' },
  });
}

//sale delivery
function saleDelivery(id, type) {
  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  window.twinpxYadeliveryFetchURL =
    '/bitrix/tools/twinpx.yadelivery/admin/ajax.php';
  window.ydConfirmer = new BX.PopupWindow('saleDelivery', null, {
    content:
      '<div id="newDeliveryContent" class="yd-popup-content load-circle"></div>',
    titleBar: {
      content: getTitleContent(BX.message('TWINPX_JS_CREATE')),
    },
    ...twinpxYadeliveryPopupSettings,
    events: {
      onPopupShow: function () {
        pageScroll(false);
        $.post(
          window.twinpxYadeliveryFetchURL,
          {
            action: 'saleNewDelivery',
            itemID: id,
            type: type,
          },
          function (data) {
            ydNewDeliveryContent =
              document.getElementById(`newDeliveryContent`);
            ydNewDeliveryContent.innerHTML = data;
            elemLoader(ydNewDeliveryContent, false);
            window.ydConfirmer.adjustPosition();
            /*newDeliveryPopupOnload();*/

            //fill button
            twinpxYadeliveryFillbutton(
              ydNewDeliveryContent.querySelector('form')
            );

            //paysystem select
            twinpxYadeliveryPaysystemSelect(ydForm);

            //float label input
            ydNewDeliveryContent
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
                    validate(item, control);
                  }
                });
              });

            function validate(item, control) {
              let regExp = {
                email: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
                //tel: /^[\+][0-9]?[-\s\.]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im,//+9 (999) 999 9999
              };

              //required
              if (
                control.getAttribute('required') === '' &&
                control.value.trim() !== ''
              ) {
                item.classList.remove('invalid');
              }

              Object.keys(regExp).forEach((key) => {
                if (control.getAttribute('type') === key) {
                  if (
                    (control.value.trim() !== '' &&
                      regExp[key].test(control.value)) ||
                    (control.getAttribute('required') !== '' &&
                      control.value.trim() === '')
                  ) {
                    item.classList.remove('invalid');
                  }
                }
              });
            }
          }
        );
      },
      onPopupClose: function (Confirmer) {
        Confirmer.destroy();
        pageScroll(true);
      },
    },
    buttons: [],
  });
  window.ydConfirmer.show();
}

$(document).on('submit', '#saleNewDelivery', function (e) {
  e.preventDefault();
  action = $(this).data('action');

  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  $.post(
    window.twinpxYadeliveryFetchURL,
    {
      action: action,
      form: $(this).serialize(),
    },
    function (data) {
      node = document.getElementById(`newDeliveryContent`);
      node.innerHTML = data;
      elemLoader(node, false);
      window.ydConfirmer.adjustPosition(); //������������ ���������
    }
  );
  return false;
});

//self points
function setPlatformId(inputId) {
  let ydContent,
    ydPopupContainer,
    ydPopupList,
    ydPopupWrapper,
    map,
    objectManager,
    bounds,
    firstGeoObjectCoords,
    regionName = 'Moscow',
    pointsArray,
    pointsNodesArray = {},
    newBounds = [],
    content = `<div id="setPlatformContentPvz" class="yd-popup-content load-circle">
      <div class="yd-popup-error"></div>
      <div class="yd-popup-body yd-popup-body--result">
        <div class="yd-popup-map-container">
          <div class="yd-popup-container yd-popup--map">
            <div id="ydPopupMap" class="yd-popup-map load-circle"></div>
            <div class="yd-popup-list">
              <div class="yd-popup-list-wrapper load-circle"></div>
            </div>
          </div>
        </div>
      </div>
    </div>`;

  setPlatformPvz();

  ydContent = document.querySelector('#setPlatformPvz .yd-popup-content');
  ydPopupContainer = ydContent.querySelector('.yd-popup-container');
  ydPopupList = ydPopupContainer.querySelector('.yd-popup-list');
  ydPopupWrapper = ydPopupList.querySelector('.yd-popup-list-wrapper');

  function setPlatformPvz() {
    window.ydSetPlatformrPvz = new BX.PopupWindow('setPlatformPvz', null, {
      content: content,
      titleBar: {
        content: getTitleContent(BX.message('TWINPX_JS_SELFPLATFORM')),
      },
      ...twinpxYadeliveryPopupSettings,
      events: {
        onPopupShow: function () {
          pageScroll(false);
          onPopupShow();
          window.ydSetPlatformrPvz.adjustPosition();
        },
        onPopupClose: function (Confirmer) {
          Confirmer.destroy();
          pageScroll(true);
        },
      },
    });
    window.ydSetPlatformrPvz.show();
  }

  function onPopupShow() {
    pointsArray = [];

    //ymaps
    if (window.ymaps && window.ymaps.ready) {
      ymaps.ready(() => {
        //geo code
        const myGeocoder = ymaps.geocode(regionName, {
          results: 1,
        });

        myGeocoder.then((res) => {
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
              controls: ['searchControl', 'zoomControl'],
            },
            {
              suppressMapOpenBlock: true,
            }
          );

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
          //objectManager.clusters.events.add(['click'], onClusterEvent);

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
            formData.set('action', 'getReception');

            let controller = new AbortController();
            let response;

            setTimeout(() => {
              if (!response) {
                controller.abort();
              }
            }, 20000);

            try {
              let response = await fetch(window.twinpxYadeliveryFetchURL, {
                method: 'POST',
                body: formData,
              });
              let result = await response.json();

              //remove preloader
              elemLoader(ydPopupWrapper, false);

              if (result && result.STATUS === 'Y' && result.POINTS) {
                //fill pointsArray
                pointsArray = result.POINTS;

                //list
                let pointsFlag,
                  objectsArray = [],
                  featureOptions = {};

                result.POINTS.forEach(
                  ({ id, title, schedule, address, coords }) => {
                    if (!id) return;

                    pointsFlag = true;

                    featureOptions = {};

                    //placemark
                    objectsArray.push({
                      type: 'Feature',
                      id: id,
                      geometry: {
                        type: 'Point',
                        coordinates: coords,
                      },
                      options: featureOptions,
                    });

                    //list
                    let item = document.createElement('div');
                    item.className = 'yd-popup-list__item';
                    item.setAttribute('data-id', id);

                    item.innerHTML = `
                        <div class="yd-popup-list__title">${title}</div>
                        <div class="yd-popup-list__text">
                          ${schedule}<br>
                          ${address}
                        </div>
                        <div class="twpx-ui-btn">${BX.message(
                          'TWINPX_JS_SELECT'
                        )}</div>
                      `;

                    item.addEventListener('click', (e) => {
                      if (e.target.classList.contains('twpx-ui-btn')) {
                        //set id value
                        document.getElementById(inputId).value = id;
                        window.ydSetPlatformrPvz.destroy();
                        pageScroll(true);
                      } else {
                        clickPlacemark(map, coords);
                      }
                    });

                    ydPopupWrapper.appendChild(item);

                    //push to nodes array
                    pointsNodesArray[id] = {
                      node: item,
                      sorted: false,
                    };
                  }
                );

                objectManager.add(objectsArray);

                if (!pointsFlag) {
                  pointsError();
                }

                if (
                  ydPopupWrapper.classList.contains(
                    'yd-popup-list-wrapper--sorted'
                  )
                ) {
                  //if the map was moved while offices were loading
                  onBoundsChange();
                }

                //map bounds
                if (map) {
                  centerCoords = map.getCenter();
                }
              } else {
                pointsError(result.ERRORS);
              }
            } catch (err) {
              pointsError();
            }
          })();
        });
      });
    }
  }

  function elemLoader(elem, flag) {
    flag
      ? elem.classList.add('load-circle')
      : elem.classList.remove('load-circle');
  }

  function pointsError(error) {
    ydPopupWrapper.innerHTML = `<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>${error}</div>`;
  }

  function onObjectEvent(e) {
    let id = e.get('objectId');

    let pointObject = pointsArray.find((p) => {
      return p.id === id;
    });

    clickPlacemark(map, pointObject.coords);
  }

  async function clickPlacemark(map, coords) {
    map.panTo(coords).then(() => {
      map.setZoom(16);
    });
  }
}
