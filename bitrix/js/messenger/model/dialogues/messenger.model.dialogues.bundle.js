(function (exports) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * Message model (Vuex module)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
	var ModelDialogues =
	/*#__PURE__*/
	function () {
	  function ModelDialogues() {
	    babelHelpers.classCallCheck(this, ModelDialogues);
	  }

	  babelHelpers.createClass(ModelDialogues, [{
	    key: "getStore",
	    value: function getStore() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return {
	        namespaced: true,
	        state: {
	          host: params.host || location.protocol + '//' + location.host,
	          collection: {}
	        },
	        actions: {
	          set: function set(store, payload) {
	            if (payload instanceof Array) {
	              payload = payload.map(function (dialog) {
	                return Object.assign({}, ModelDialogues.getDialogBlank(), ModelDialogues.validate(Object.assign({}, dialog), {
	                  host: store.state.host
	                }), {
	                  init: true
	                });
	              });
	            } else {
	              var result = [];
	              result.push(Object.assign({}, ModelDialogues.getDialogBlank(), ModelDialogues.validate(Object.assign({}, payload), {
	                host: store.state.host
	              }), {
	                init: true
	              }));
	              payload = result;
	            }

	            store.commit('set', payload);
	          },
	          update: function update(store, payload) {
	            if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	              return true;
	            }

	            store.commit('update', {
	              dialogId: payload.dialogId,
	              fields: ModelDialogues.validate(Object.assign({}, payload.fields), {
	                host: store.state.host
	              })
	            });
	            return true;
	          },
	          delete: function _delete(store, payload) {
	            store.commit('delete', payload.dialogId);
	            return true;
	          },
	          updateWriting: function updateWriting(store, payload) {
	            if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	              return true;
	            }

	            var index = store.state.collection[payload.dialogId].writingList.findIndex(function (el) {
	              return el.userId == payload.userId;
	            });

	            if (payload.action) {
	              if (index >= 0) {
	                return true;
	              } else {
	                var writingList = [].concat(store.state.collection[payload.dialogId].writingList);
	                writingList.unshift({
	                  userId: payload.userId,
	                  userName: payload.userName
	                });
	                store.commit('update', {
	                  actionName: 'updateWriting/1',
	                  dialogId: payload.dialogId,
	                  fields: {
	                    writingList: writingList
	                  }
	                });
	              }
	            } else {
	              if (index >= 0) {
	                var _writingList = store.state.collection[payload.dialogId].writingList.filter(function (el) {
	                  return el.userId != payload.userId;
	                });

	                store.commit('update', {
	                  actionName: 'updateWriting/2',
	                  dialogId: payload.dialogId,
	                  fields: {
	                    writingList: _writingList
	                  }
	                });
	                return true;
	              } else {
	                return true;
	              }
	            }

	            return false;
	          },
	          increaseCounter: function increaseCounter(store, payload) {
	            if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	              return true;
	            }

	            var counter = store.state.collection[payload.dialogId].counter;
	            var increasedCounter = counter + payload.count;
	            var fields = {
	              counter: increasedCounter
	            };

	            if (typeof payload.unreadLastId !== 'undefined') {
	              fields.unreadLastId = payload.unreadLastId;
	            }

	            store.commit('update', {
	              actionName: 'increaseCounter',
	              dialogId: payload.dialogId,
	              fields: fields
	            });
	            return false;
	          },
	          decreaseCounter: function decreaseCounter(store, payload) {
	            if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	              return true;
	            }

	            var counter = store.state.collection[payload.dialogId].counter;
	            var decreasedCounter = counter - payload.count;

	            if (decreasedCounter < 0) {
	              decreasedCounter = 0;
	            }

	            var unreadId = payload.unreadId > store.state.collection[payload.dialogId].unreadId ? payload.unreadId : store.state.collection[payload.dialogId].unreadId;
	            store.commit('update', {
	              actionName: 'decreaseCounter',
	              dialogId: payload.dialogId,
	              fields: {
	                counter: decreasedCounter,
	                unreadId: unreadId
	              }
	            });
	            return false;
	          }
	        },
	        mutations: {
	          initCollection: function initCollection(state, payload) {
	            if (typeof state.collection[payload.dialogId] === 'undefined') {
	              BX.Vue.set(state.collection, payload.dialogId, ModelDialogues.getDialogBlank());

	              if (payload.fields) {
	                state.collection[payload.dialogId] = Object.assign(state.collection[payload.dialogId], ModelDialogues.validate(Object.assign({}, payload.fields), {
	                  host: state.host
	                }));
	              }
	            }
	          },
	          set: function set(state, payload) {
	            var _iteratorNormalCompletion = true;
	            var _didIteratorError = false;
	            var _iteratorError = undefined;

	            try {
	              for (var _iterator = payload[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	                var element = _step.value;

	                if (typeof state.collection[element.dialogId] === 'undefined') {
	                  BX.Vue.set(state.collection, element.dialogId, element);
	                }

	                state.collection[element.dialogId] = element;
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
	          },
	          update: function update(state, payload) {
	            if (typeof state.collection[payload.dialogId] === 'undefined') {
	              BX.Vue.set(state.collection, payload.dialogId, ModelDialogues.getDialogBlank());
	            }

	            state.collection[payload.dialogId] = Object.assign(state.collection[payload.dialogId], payload.fields);
	          },
	          delete: function _delete(state, payload) {
	            delete state.collection[payload.dialogId];
	          }
	        }
	      };
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      return new ModelDialogues();
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return 'messengerDialogues';
	    }
	  }, {
	    key: "getDialogBlank",
	    value: function getDialogBlank() {
	      return {
	        dialogId: 0,
	        chatId: 0,
	        counter: 0,
	        unreadId: 0,
	        unreadLastId: 0,
	        readedList: [],
	        writingList: [],
	        init: false,
	        name: "",
	        owner: 0,
	        extranet: false,
	        avatar: "",
	        color: "#17A3EA",
	        type: "chat",
	        entityType: "",
	        entityId: "",
	        entityData1: "",
	        entityData2: "",
	        entityData3: "",
	        dateCreate: new Date()
	      };
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var result = {};
	      options.host = options.host || location.protocol + '//' + location.host;

	      if (typeof fields.dialog_id !== 'undefined') {
	        fields.dialogId = fields.dialog_id;
	      }

	      if (typeof fields.dialogId === "number" || typeof fields.dialogId === "string") {
	        result.dialogId = fields.dialogId.toString();
	      }

	      if (typeof fields.chat_id !== 'undefined') {
	        fields.chatId = fields.chat_id;
	      } else if (typeof fields.id !== 'undefined') {
	        fields.chatId = fields.id;
	      }

	      if (typeof fields.chatId === "number" || typeof fields.chatId === "string") {
	        result.chatId = parseInt(fields.chatId);
	      }

	      if (typeof fields.counter === "number" || typeof fields.counter === "string") {
	        result.counter = parseInt(fields.counter);
	      }

	      if (typeof fields.unread_id !== 'undefined') {
	        fields.unreadId = fields.unread_id;
	      }

	      if (typeof fields.unreadId === "number" || typeof fields.unreadId === "string") {
	        result.unreadId = parseInt(fields.unreadId);
	      }

	      if (typeof fields.unread_last_id !== 'undefined') {
	        fields.unreadLastId = fields.unread_last_id;
	      }

	      if (typeof fields.unreadLastId === "number" || typeof fields.unreadLastId === "string") {
	        result.unreadLastId = parseInt(fields.unreadLastId);
	      }

	      if (typeof fields.readed_list !== 'undefined') {
	        fields.readedList = fields.readed_list;
	      }

	      if (typeof fields.readedList !== 'undefined') {
	        result.readedList = [];

	        if (fields.readedList instanceof Array) {
	          fields.readedList.forEach(function (element) {
	            var record = {};

	            if (typeof element.user_id !== 'undefined') {
	              element.userId = element.user_id;
	            }

	            if (typeof element.user_name !== 'undefined') {
	              element.userName = element.user_name;
	            }

	            if (typeof element.message_id !== 'undefined') {
	              element.messageId = element.message_id;
	            }

	            if (!element.userId || !element.userName || !element.messageId) {
	              return false;
	            }

	            record.userId = parseInt(element.userId);
	            record.userName = element.userName.toString();
	            record.messageId = parseInt(element.messageId);

	            if (fields.date instanceof Date) {
	              record.date = fields.date;
	            } else if (typeof fields.date === "string") {
	              record.date = new Date(fields.date);
	            } else {
	              record.date = new Date();
	            }

	            result.readedList.push(record);
	          });
	        }
	      }

	      if (typeof fields.writing_list !== 'undefined') {
	        fields.writingList = fields.writing_list;
	      }

	      if (typeof fields.writingList !== 'undefined') {
	        result.writingList = [];

	        if (fields.writingList instanceof Array) {
	          fields.writingList.forEach(function (element) {
	            var record = {};

	            if (!element.userId) {
	              return false;
	            }

	            record.userId = parseInt(element.userId);
	            record.userName = element.userName;
	            result.writingList.push(record);
	          });
	        }
	      }

	      if (typeof fields.mute_list !== 'undefined') {
	        fields.muteList = fields.mute_list;
	      }

	      if (typeof fields.muteList !== 'undefined') {
	        result.muteList = [];

	        if (fields.muteList instanceof Array) {
	          fields.muteList.forEach(function (userId) {
	            userId = parseInt(userId);

	            if (userId > 0) {
	              result.muteList.push(userId);
	            }
	          });
	        }
	      }

	      if (typeof fields.title !== 'undefined') {
	        fields.name = fields.title;
	      }

	      if (typeof fields.name === "string" || typeof fields.name === "number") {
	        result.name = fields.name.toString();
	      }

	      if (typeof fields.owner !== 'undefined') {
	        fields.ownerId = fields.owner;
	      }

	      if (typeof fields.ownerId === "number" || typeof fields.ownerId === "string") {
	        result.ownerId = parseInt(fields.ownerId);
	      }

	      if (typeof fields.extranet === "boolean") {
	        result.extranet = fields.extranet;
	      }

	      if (typeof fields.avatar === 'string') {
	        if (!fields.avatar || fields.avatar.startsWith('http')) {
	          result.avatar = fields.avatar;
	        } else {
	          result.avatar = options.host + fields.avatar;
	        }
	      }

	      if (typeof fields.color === "string") {
	        result.color = fields.color.toString();
	      }

	      if (typeof fields.type === "string") {
	        result.type = fields.type.toString();
	      }

	      if (typeof fields.entity_type !== 'undefined') {
	        fields.entityType = fields.entity_type;
	      }

	      if (typeof fields.entityType === "string") {
	        result.entityType = fields.entityType.toString();
	      }

	      if (typeof fields.entity_id !== 'undefined') {
	        fields.entityId = fields.entity_id;
	      }

	      if (typeof fields.entityId === "string" || typeof fields.entityId === "number") {
	        result.entityId = fields.entityId.toString();
	      }

	      if (typeof fields.entity_data_1 !== 'undefined') {
	        fields.entityData1 = fields.entity_data_1;
	      }

	      if (typeof fields.entityData1 === "string") {
	        result.entityData1 = fields.entityData1.toString();
	      }

	      if (typeof fields.entity_data_2 !== 'undefined') {
	        fields.entityData2 = fields.entity_data_2;
	      }

	      if (typeof fields.entityData2 === "string") {
	        result.entityData2 = fields.entityData2.toString();
	      }

	      if (typeof fields.entity_data_3 !== 'undefined') {
	        fields.entityData3 = fields.entity_data_3;
	      }

	      if (typeof fields.entityData3 === "string") {
	        result.entityData3 = fields.entityData3.toString();
	      }

	      if (typeof fields.date_create !== 'undefined') {
	        fields.dateCreate = fields.date_create;
	      }

	      if (fields.dateCreate instanceof Date) {
	        result.dateCreate = fields.dateCreate;
	      } else if (typeof fields.dateCreate === "string") {
	        result.dateCreate = new Date(fields.dateCreate);
	      }

	      return result;
	    }
	  }]);
	  return ModelDialogues;
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

	if (typeof window.BX.Messenger.Model.Dialogues == 'undefined') {
	  BX.Messenger.Model.Dialogues = ModelDialogues;
	}

}((this.window = this.window || {})));
//# sourceMappingURL=messenger.model.dialogues.bundle.js.map
