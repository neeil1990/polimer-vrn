import Factory from "./factory";
import Basket from "./basket/field";
import BasketConfirm from "./basketconfirm/field";
import Shipment from "./shipment/field";
import Property from "./property/field";
import Print from "./print/field";
import Notification from "./notification/field";
import Editor from "./compatible/editor";
import './common.css';

// factory

const factory = new Factory({
	map: {
		notification: Notification,
		basket: Basket,
		basket_confirm: BasketConfirm,
		shipment: Shipment,
		property: Property,
		print: Print,
	},
});

factory.register();

// compatible

const editor = new Editor();

editor.start();

export {
	Notification,
	Basket,
	BasketConfirm,
	Shipment,
	Property,
	Print,
};