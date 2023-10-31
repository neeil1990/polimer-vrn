(function (exports) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * File model (Vuex module)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
	var InsertType = Object.freeze({
	  after: 'after',
	  before: 'before'
	});

	var ModelFiles =
	/*#__PURE__*/
	function () {
	  function ModelFiles() {
	    babelHelpers.classCallCheck(this, ModelFiles);
	  }

	  babelHelpers.createClass(ModelFiles, [{
	    key: "getStore",
	    value: function getStore() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return {
	        namespaced: true,
	        state: {
	          host: params.host || location.protocol + '//' + location.host,
	          created: 0,
	          collection: {},
	          index: {}
	        },
	        actions: {
	          set: function set(store, payload) {
	            if (payload instanceof Array) {
	              payload = payload.map(function (file) {
	                var result = ModelFiles.validate(Object.assign({}, file), {
	                  host: store.state.host
	                });
	                result.templateId = result.id;
	                return Object.assign({}, ModelFiles.getFileBlank(), result);
	              });
	            } else {
	              var result = ModelFiles.validate(Object.assign({}, payload), {
	                host: store.state.host
	              });
	              result.templateId = result.id;
	              payload = [];
	              payload.push(Object.assign({}, ModelFiles.getFileBlank(), result));
	            }

	            store.commit('set', {
	              insertType: InsertType.after,
	              data: payload
	            });
	          },
	          setBefore: function setBefore(store, payload) {
	            if (payload instanceof Array) {
	              payload = payload.map(function (message) {
	                var result = ModelFiles.validate(Object.assign({}, message), {
	                  host: store.state.host
	                });
	                result.templateId = result.id;
	                return Object.assign({}, ModelFiles.getFileBlank(), result);
	              });
	            } else {
	              var result = ModelFiles.validate(Object.assign({}, payload), {
	                host: store.state.host
	              });
	              result.templateId = result.id;
	              payload = [];
	              payload.push(Object.assign({}, ModelFiles.getFileBlank(), result));
	            }

	            store.commit('set', {
	              actionName: 'setBefore',
	              insertType: InsertType.before,
	              data: payload
	            });
	          },
	          update: function update(store, payload) {
	            var result = ModelFiles.validate(Object.assign({}, payload.fields), {
	              host: store.state.host
	            });

	            if (typeof store.state.collection[payload.chatId] === 'undefined') {
	              BX.Vue.set(store.state.collection, payload.chatId, []);
	              BX.Vue.set(store.state.index, payload.chatId, {});
	            }

	            var index = store.state.collection[payload.chatId].findIndex(function (el) {
	              return el.id == payload.id;
	            });

	            if (index < 0) {
	              return false;
	            }

	            store.commit('update', {
	              id: payload.id,
	              chatId: payload.chatId,
	              index: index,
	              fields: result
	            });

	            if (payload.fields.blink) {
	              setTimeout(function () {
	                store.commit('update', {
	                  id: payload.id,
	                  chatId: payload.chatId,
	                  fields: {
	                    blink: false
	                  }
	                });
	              }, 1000);
	            }

	            return true;
	          },
	          delete: function _delete(store, payload) {
	            store.commit('delete', {
	              id: payload.id,
	              chatId: payload.chatId
	            });
	            return true;
	          }
	        },
	        mutations: {
	          initCollection: function initCollection(state, payload) {
	            if (typeof state.collection[payload.chatId] === 'undefined') {
	              BX.Vue.set(state.collection, payload.chatId, []);
	              BX.Vue.set(state.index, payload.chatId, {});
	            }
	          },
	          set: function set(state, payload) {
	            if (payload.insertType == InsertType.after) {
	              var _iteratorNormalCompletion = true;
	              var _didIteratorError = false;
	              var _iteratorError = undefined;

	              try {
	                var _loop = function _loop() {
	                  var element = _step.value;

	                  if (typeof state.collection[element.chatId] === 'undefined') {
	                    BX.Vue.set(state.collection, element.chatId, []);
	                    BX.Vue.set(state.index, element.chatId, {});
	                  }

	                  var index = state.collection[element.chatId].findIndex(function (el) {
	                    return el.id === element.id;
	                  });

	                  if (index > -1) {
	                    state.collection[element.chatId][index] = element;
	                  } else {
	                    state.collection[element.chatId].push(element);
	                  }

	                  state.index[element.chatId][element.id] = element;
	                };

	                for (var _iterator = payload.data[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	                  _loop();
	                }
	              } catch (err) {
	                _didIteratorError = true;
	                _iteratorError = err;
	              } finally {
	                try {
	                  if (!_iteratorNormalCompletion && _iterator.return != null) {
	                    _iterator.return();
	                  }
	                } finally {
	                  if (_didIteratorError) {
	                    throw _iteratorError;
	                  }
	                }
	              }
	            } else {
	              var _iteratorNormalCompletion2 = true;
	              var _didIteratorError2 = false;
	              var _iteratorError2 = undefined;

	              try {
	                var _loop2 = function _loop2() {
	                  var element = _step2.value;

	                  if (typeof state.collection[element.chatId] === 'undefined') {
	                    BX.Vue.set(state.collection, element.chatId, []);
	                    BX.Vue.set(state.index, element.chatId, {});
	                  }

	                  var index = state.collection[element.chatId].findIndex(function (el) {
	                    return el.id === element.id;
	                  });

	                  if (index > -1) {
	                    state.collection[element.chatId][index] = element;
	                  } else {
	                    state.collection[element.chatId].unshift(element);
	                  }

	                  state.index[element.chatId][element.id] = element;
	                };

	                for (var _iterator2 = payload.data[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
	                  _loop2();
	                }
	              } catch (err) {
	                _didIteratorError2 = true;
	                _iteratorError2 = err;
	              } finally {
	                try {
	                  if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
	                    _iterator2.return();
	                  }
	                } finally {
	                  if (_didIteratorError2) {
	                    throw _iteratorError2;
	                  }
	                }
	              }
	            }
	          },
	          update: function update(state, payload) {
	            if (typeof state.collection[payload.chatId] === 'undefined') {
	              BX.Vue.set(state.collection, payload.chatId, []);
	              BX.Vue.set(state.index, payload.chatId, {});
	            }

	            var index = -1;

	            if (typeof payload.index !== 'undefined' && state.collection[payload.chatId][payload.index]) {
	              index = payload.index;
	            } else {
	              index = state.collection[payload.chatId].findIndex(function (el) {
	                return el.id == payload.id;
	              });
	            }

	            if (index >= 0) {
	              var element = Object.assign(state.collection[payload.chatId][index], payload.fields);
	              state.collection[payload.chatId][index] = element;
	              state.index[payload.chatId][element.id] = element;
	            }
	          },
	          delete: function _delete(state, payload) {
	            if (typeof state.collection[payload.chatId] === 'undefined') {
	              BX.Vue.set(state.collection, payload.chatId, []);
	              BX.Vue.set(state.index, payload.chatId, {});
	            }

	            state.collection[payload.chatId] = state.collection[payload.chatId].filter(function (element) {
	              return element.id != payload.id;
	            });
	            delete state.index[payload.chatId][payload.id];
	          }
	        }
	      };
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      return new ModelFiles();
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return 'messengerFiles';
	    }
	  }, {
	    key: "convertToArray",
	    value: function convertToArray(object) {
	      var result = [];

	      for (var i in object) {
	        if (object.hasOwnProperty(i)) {
	          result.push(object[i]);
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "getFileBlank",
	    value: function getFileBlank() {
	      return {
	        id: 0,
	        templateId: 0,
	        chatId: 0,
	        date: new Date(),
	        type: 'file',
	        name: "",
	        extension: "",
	        icon: "empty",
	        size: 0,
	        image: false,
	        status: 'done',
	        progress: 100,
	        authorId: 0,
	        authorName: "",
	        urlPreview: "",
	        urlShow: "",
	        urlDownload: ""
	      };
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var result = {};
	      options.host = options.host || location.protocol + '//' + location.host;

	      if (typeof fields.id === "number" || typeof fields.id === "string") {
	        result.id = parseInt(fields.id);
	      }

	      if (typeof fields.chatId === "number" || typeof fields.chatId === "string") {
	        result.chatId = parseInt(fields.chatId);
	      }

	      if (fields.date instanceof Date) {
	        result.date = fields.date;
	      } else if (typeof fields.date === "string") {
	        result.date = new Date(fields.date);
	      }

	      if (typeof fields.type === "string") {
	        result.type = fields.type;
	      }

	      if (typeof fields.extension === "string") {
	        result.extension = fields.extension.toString();

	        if (result.type === 'image') {
	          result.icon = 'img';
	        } else if (result.type === 'video') {
	          result.icon = 'mov';
	        } else if (result.extension === 'docx' || result.extension === 'doc') {
	          result.icon = 'doc';
	        } else if (result.extension === 'xlsx' || result.extension === 'xls') {
	          result.icon = 'xls';
	        } else if (result.extension === 'pptx' || result.extension === 'ppt') {
	          result.icon = 'ppt';
	        } else if (result.extension === 'rar') {
	          result.icon = 'rar';
	        } else if (result.extension === 'zip') {
	          result.icon = 'zip';
	        } else if (result.extension === 'pdf') {
	          result.icon = 'pdf';
	        } else if (result.extension === 'txt') {
	          result.icon = 'txt';
	        } else if (result.extension === 'php') {
	          result.icon = 'php';
	        } else if (result.extension === 'conf' || result.extension === 'ini' || result.extension === 'plist') {
	          result.icon = 'set';
	        }
	      }

	      if (typeof fields.name === "string" || typeof fields.name === "number") {
	        result.name = fields.name.toString();
	      }

	      if (typeof fields.size === "number" || typeof fields.size === "string") {
	        result.size = parseInt(fields.size);
	      }

	      if (typeof fields.image === 'boolean') {
	        result.image = false;
	      } else if (babelHelpers.typeof(fields.image) === 'object' && fields.image) {
	        result.image = {
	          width: 0,
	          height: 0
	        };

	        if (typeof fields.image.width === "number") {
	          result.image.width = fields.image.width;
	        }

	        if (typeof fields.image.height === "number") {
	          result.image.height = fields.image.height;
	        }
	      }

	      if (typeof fields.status === "string") {
	        result.status = fields.status;
	      }

	      if (typeof fields.progress === "number" || typeof fields.progress === "string") {
	        result.progress = parseInt(fields.progress);
	      }

	      if (typeof fields.authorId === "number" || typeof fields.authorId === "string") {
	        result.authorId = parseInt(fields.authorId);
	      }

	      if (typeof fields.authorName === "string" || typeof fields.authorName === "number") {
	        result.authorName = fields.authorName.toString();
	      }

	      if (typeof fields.urlPreview === 'string') {
	        if (!fields.urlPreview || fields.urlPreview.startsWith('http')) {
	          result.urlPreview = fields.urlPreview;
	        } else {
	          result.urlPreview = options.host + fields.urlPreview;
	        }
	      }

	      if (typeof fields.urlDownload === 'string') {
	        if (!fields.urlDownload || fields.urlDownload.startsWith('http')) {
	          result.urlDownload = fields.urlDownload;
	        } else {
	          result.urlDownload = options.host + fields.urlDownload;
	        }
	      }

	      if (typeof fields.urlShow === 'string') {
	        if (!fields.urlShow || fields.urlShow.startsWith('http')) {
	          result.urlShow = fields.urlShow;
	        } else {
	          result.urlShow = options.host + fields.urlShow;
	        }
	      }

	      return result;
	    }
	  }]);
	  return ModelFiles;
	}();

	if (!window.BX) {
	  window.BX = {};
	}

	if (typeof window.BX.Messenger == 'undefined') {
	  window.BX.Messenger = {};
	}

	if (typeof window.BX.Messenger.Model == 'undefined') {
	  window.BX.Messenger.Model = {};
	}

	if (typeof window.BX.Messenger.Model.Files == 'undefined') {
	  BX.Messenger.Model.Files = ModelFiles;
	}

}((this.window = this.window || {})));
//# sourceMappingURL=messenger.model.files.bundle.js.map
