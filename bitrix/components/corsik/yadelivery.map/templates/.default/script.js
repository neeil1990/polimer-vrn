const moduleID = "yadelivery";
const prefixClassName = "corsik_yaDeliveryMap";
const prefixModuleID = "corsik_yaDelivery";
const classNames = {
	map: `${prefixClassName}__map`,
	address: `${prefixClassName}__addressDelivery`,
	orderPrice: `${prefixClassName}__orderPrice`,
	orderWeight: `${prefixClassName}__orderWeight`,
	total: `${prefixModuleID}__total`,
	totalPrice: `${prefixModuleID}__total__price`,
	route: `${prefixModuleID}__route`,
	routeValue: `${prefixModuleID}__route__value`,
};

class YaDeliveryMapComponent {
	constructor(api) {
		this.api = api;
		this.total = {};
		this.addressInput = document.getElementById(classNames.address);
		this.setSuggestionSettings();
		this.api.afterCalculate = this.onAfterCalculate;
	}

	setSuggestionSettings = () => {
		const { typePrompts } = this.getParameters();
		const isYandexSuggestions = typePrompts === "yandex";
		const isDadataSuggestions = typePrompts === "dadata";

		this.api.setSettings({
			...this.getSettings(),
			yandex: {
				controls: true,
				options: {
					search: {
						size: "auto",
					},
				},
				suggestions: isYandexSuggestions ? this.getYandexSuggestionsSettings() : {},
			},
			dadata: {
				fields: isDadataSuggestions ? this.getDadataSuggestionsSettings() : [],
			},
		});
	};

	getDadataSuggestionsSettings = () => [
		{
			code: "ADDRESS",
			selector: `#${classNames.address}`,
		},
	];

	getYandexSuggestionsSettings = () => [
		{
			code: "ADDRESS",
			selector: `#${classNames.address}`,
			options: {
				provider: {
					suggest: (request) => {
						//Enter the restriction for your region
						const restrictionRegion = "Московская область, ";
						return ymaps.suggest(restrictionRegion + request);
					},
				},
			},
		},
	];

	init = () => {
		this.initEvents();
	};

	/**
	 * result after delivery calculation
	 * @param resultCalculate
	 */
	onAfterCalculate = (resultCalculate) => {
		console.log(resultCalculate);
	};

	getSettings = () => {
		const parameters = this.getParameters();

		if (_.isEmpty(parameters)) {
			return null;
		}

		const isPageMap = parameters?.displayMap === "PAGE";
		if (isPageMap) {
			parameters.selectors = {
				map: `#${classNames.map}`,
			};
		}

		return parameters;
	};

	getParameters = () => window.jsonMapsParameters;

	initEvents = () => {
		document
			.getElementById(classNames.orderPrice)
			.addEventListener("change", this.setTotalValue("PRICE_WITHOUT_DISCOUNT_VALUE"));
		document.getElementById(classNames.orderWeight).addEventListener("change", this.setTotalValue("ORDER_WEIGHT"));
	};

	recalculateAddress = () => {
		const address = _.trim(this.addressInput.value);
		if (address) {
			window.onCalculateRoute(address, true);
		}
	};

	setTotalValue = (name) => (e) => {
		const target = e.target;
		if (target.value > 0) {
			this.total[name] = target.value;
			this.api.setResult({ TOTAL: this.total });
			this.recalculateAddress();
		}
	};
}

BX.ready(() => {
	const getApiYaDelivery = (name) => _.get(window, [moduleID, name], null);

	try {
		const yaDelivery = getApiYaDelivery("run");
		yaDelivery(true).then(() => {
			const api = getApiYaDelivery("api");
			const component = new YaDeliveryMapComponent(api);
			api.setSettings({ isCustomCalculate: true });
			api.ready(async () => {
				component.init();
			});
		});
	} catch (e) {
		console.error(e);
	}
});
