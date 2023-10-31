(function (exports) {
	'use strict';

	/**
	 * Bitrix Vuex manager
	 *
	 * @package bitrix
	 * @subpackage ui
	 * @copyright 2001-2019 Bitrix
	 */
	var BitrixVueRouter =
	/*#__PURE__*/
	function () {
	  function BitrixVueRouter() {
	    babelHelpers.classCallCheck(this, BitrixVueRouter);
	  }

	  babelHelpers.createClass(BitrixVueRouter, null, [{
	    key: "create",

	    /**
	     * Create new VueRouter instance
	     *
	     * @param {Object} params - route config
	     *
	     * @see https://router.vuejs.org/
	     */
	    value: function create(params) {
	      return new VueRouter(params);
	    }
	    /**
	     * Provides the installed version of Vuex as a string.
	     *
	     * @returns {String}
	     */

	  }, {
	    key: "version",
	    value: function version() {
	      return VueRouter.version;
	    }
	  }]);
	  return BitrixVueRouter;
	}();

	if (!window.BX) {
	  window.BX = {};
	}

	if (!window.BX.VueRouter) {
	  window.BX.VueRouter = BitrixVueRouter;
	}

}((this.window = this.window || {})));
//# sourceMappingURL=ui.vue.router.bundle.js.map
