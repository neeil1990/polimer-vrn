/**
 * Bitrix Vuex manager
 *
 * @package bitrix
 * @subpackage ui
 * @copyright 2001-2019 Bitrix
 */

class BitrixVueRouter
{
	/**
	 * Create new VueRouter instance
	 *
	 * @param {Object} params - route config
	 *
	 * @see https://router.vuejs.org/
	 */

	static create(params)
	{
		return new VueRouter(params);
	}

	/**
	 * Provides the installed version of Vuex as a string.
	 *
	 * @returns {String}
	 */
	static version()
	{
		return VueRouter.version;
	}
}

if (!window.BX)
{
	window.BX = {};
}

if (!window.BX.VueRouter)
{
	window.BX.VueRouter = BitrixVueRouter;
}