/**
 * Bitrix Vuex manager
 *
 * @package bitrix
 * @subpackage ui
 * @copyright 2001-2019 Bitrix
 */

class BitrixVuex
{
	/**
	 * Create new Vuex instance
	 *
	 * @param {Object} params - definition
	 *
	 * @see https://vuex.vuejs.org/api/#vuex-store
	 */
	static store(params)
	{
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
	static mapState(...params)
	{
		return Vuex.mapState(...params);
	}

	/**
	 * Create component computed options that return the evaluated value of a getter.
	 *
	 * @param params
	 * @returns {*}
	 *
	 * @see https://vuex.vuejs.org/api/#mapgetters
	 */
	static mapGetters(...params)
	{
		return Vuex.mapGetters(...params);
	}

	/**
	 * Create component methods options that dispatch an action.
	 *
	 * @param params
	 * @returns {*}
	 *
	 * @see https://vuex.vuejs.org/api/#mapactions
	 */
	static mapActions(...params)
	{
		return Vuex.mapActions(...params);
	}

	/**
	 * Create component methods options that commit a mutation.
	 *
	 * @param params
	 * @returns {*}
	 *
	 * @see https://vuex.vuejs.org/api/#mapactions
	 */
	static mapMutations(...params)
	{
		return Vuex.mapMutations(...params);
	}

	/**
	 * Create namespaced component binding helpers.
	 *
	 * @param params
	 * @returns {*}
	 *
	 * @see https://vuex.vuejs.org/api/#createnamespacedhelpers
	 */
	static createNamespacedHelpers(...params)
	{
		return Vuex.createNamespacedHelpers(...params);
	}

	/**
	 * Provides the installed version of Vuex as a string.
	 *
	 * @returns {String}
	 */
	static version()
	{
		return Vuex.version;
	}
}

if (!window.BX)
{
	window.BX = {};
}

if (!window.BX.Vuex)
{
	window.BX.Vuex = BitrixVuex;
}