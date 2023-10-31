(function (exports) {
	'use strict';

	/**
	 * Bitrix Vuex manager
	 *
	 * @package bitrix
	 * @subpackage ui
	 * @copyright 2001-2019 Bitrix
	 */
	var BitrixVuex =
	/*#__PURE__*/
	function () {
	  function BitrixVuex() {
	    babelHelpers.classCallCheck(this, BitrixVuex);
	  }

	  babelHelpers.createClass(BitrixVuex, null, [{
	    key: "store",

	    /**
	     * Create new Vuex instance
	     *
	     * @param {Object} params - definition
	     *
	     * @see https://vuex.vuejs.org/api/#vuex-store
	     */
	    value: function store(params) {
	      return new Vuex.Store(params);
	    }
	    /**
	     * Create component computed options that return the sub tree of the Vuex store.
	     *
	     * @param params
	     * @returns {*}
	     *
	     * @see https://vuex.vuejs.org/api/#mapstate
	     */

	  }, {
	    key: "mapState",
	    value: function mapState() {
	      var _Vuex;

	      return (_Vuex = Vuex).mapState.apply(_Vuex, arguments);
	    }
	    /**
	     * Create component computed options that return the evaluated value of a getter.
	     *
	     * @param params
	     * @returns {*}
	     *
	     * @see https://vuex.vuejs.org/api/#mapgetters
	     */

	  }, {
	    key: "mapGetters",
	    value: function mapGetters() {
	      var _Vuex2;

	      return (_Vuex2 = Vuex).mapGetters.apply(_Vuex2, arguments);
	    }
	    /**
	     * Create component methods options that dispatch an action.
	     *
	     * @param params
	     * @returns {*}
	     *
	     * @see https://vuex.vuejs.org/api/#mapactions
	     */

	  }, {
	    key: "mapActions",
	    value: function mapActions() {
	      var _Vuex3;

	      return (_Vuex3 = Vuex).mapActions.apply(_Vuex3, arguments);
	    }
	    /**
	     * Create component methods options that commit a mutation.
	     *
	     * @param params
	     * @returns {*}
	     *
	     * @see https://vuex.vuejs.org/api/#mapactions
	     */

	  }, {
	    key: "mapMutations",
	    value: function mapMutations() {
	      var _Vuex4;

	      return (_Vuex4 = Vuex).mapMutations.apply(_Vuex4, arguments);
	    }
	    /**
	     * Create namespaced component binding helpers.
	     *
	     * @param params
	     * @returns {*}
	     *
	     * @see https://vuex.vuejs.org/api/#createnamespacedhelpers
	     */

	  }, {
	    key: "createNamespacedHelpers",
	    value: function createNamespacedHelpers() {
	      var _Vuex5;

	      return (_Vuex5 = Vuex).createNamespacedHelpers.apply(_Vuex5, arguments);
	    }
	    /**
	     * Provides the installed version of Vuex as a string.
	     *
	     * @returns {String}
	     */

	  }, {
	    key: "version",
	    value: function version() {
	      return Vuex.version;
	    }
	  }]);
	  return BitrixVuex;
	}();

	if (!window.BX) {
	  window.BX = {};
	}

	if (!window.BX.Vuex) {
	  window.BX.Vuex = BitrixVuex;
	}

}((this.window = this.window || {})));
//# sourceMappingURL=ui.vue.vuex.bundle.js.map
