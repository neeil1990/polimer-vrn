import ReferenceField from "../reference/field";
import BoxCollection from "../box/collection";
import {htmlToElement} from "../utils";

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		name: 'SHIPMENT',
		actions: [],
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	initialize(id, settings) {
		super.initialize(id, settings);
		this.handleTitleActions(true);
	}

	release() {
		this.handleTitleActions(false);
		super.release();
	}

	handleTitleActions(dir) {
		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](window, 'BX.UI.EntityEditorSection:onLayout', this.onTitleActions);
	}

	handleUseDimensionsChange(node, dir) {
		node[dir ? 'addEventListener' : 'removeEventListener']('change', this.onUseDimensionsChange);
	}

	onTitleActions = (field, context) => {
		if (field !== this.getParent()) { return; }

		const useDimensionsNode = field.isInEditMode()
			? this.renderUseDimensions(this.useDimensions)
			: this.definedUseDimensions(this.useDimensions);

		context.customNodes.push(useDimensionsNode);
	}

	onUseDimensionsChange = (evt) => {
		const enabled = evt.target.checked;

		this.toggleUseDimensions(enabled);
		this.markAsChanged();
	}

	render(payload) {
		this.syncUseDimensions(payload['USE_DIMENSIONS']);
		this.renderShipments(payload['VALUE'], {
			fulfilmentBase: payload['FULFILMENT_BASE'],
			boxProperties: payload['BOX_PROPERTIES'],
			boxDimensions: payload['BOX_DIMENSIONS'],
			useDimensions: payload['USE_DIMENSIONS'],
		});
	}

	definedUseDimensions(enabled = false) {
		return htmlToElement(`<input type="hidden" name="USE_DIMENSIONS" value="${enabled ? 'Y' : 'N'}" />`);
	}

	renderUseDimensions(enabled = false) {
		const node = htmlToElement(`<div class="ui-entity-editor-field-checkbox">
			<input type="hidden" name="USE_DIMENSIONS" value="N" />
			<label class="ui-ctl ui-ctl-xs ui-ctl-wa ui-ctl-checkbox yamarket-boxes-use-dimensions">
				<input class="ui-ctl-element" type="checkbox" name="USE_DIMENSIONS" value="Y" ${enabled ? 'checked' : ''} />
				<div class="ui-ctl-label-text">${this.getMessage('USE_DIMENSIONS')}</div>
			</label>
		</div>`);
		const checkbox = node.querySelector('input[type="checkbox"]');

		this.handleUseDimensionsChange(checkbox, true);

		return node;
	}

	syncUseDimensions(enabled) {
		this.useDimensions = enabled;
	}

	toggleUseDimensions(enabled) {
		if (this.shipmentBoxes == null) { return; }

		for (const boxCollection of this.shipmentBoxes) {
			boxCollection.toggleUseDimensions(enabled);
		}
	}

	renderShipmentTitle(shipment) {
		const html = `<div class="ui-entity-editor-block-title">
			<span class="ui-entity-editor-block-title-text">${this.getMessage('SHIPMENT', { 'ID': shipment['ID'] })}</span>
		</div>`;

		this._wrapper.insertAdjacentHTML('beforeend', html);
	}

	renderShipments(shipmentCollection, options) {
		this.shipmentBoxes = [];

		if (!Array.isArray(shipmentCollection)) { shipmentCollection = []; }

		const hasFew = shipmentCollection.length > 1;
		let index = 0;

		for (const shipment of shipmentCollection) {
			if (hasFew) {
				this.renderShipmentTitle(shipment);
			}

			this.renderShipment(shipment, Object.assign(options, {
				name: `${this.options.name}[${index}]`,
			}));

			++index;
		}
	}

	renderShipment(shipment, options) {
		this.renderShipmentDefined(shipment, options);
		this.renderBoxCollection(shipment['BOX'], options);
	}

	renderShipmentDefined(shipment, options) {
		const keys = [
			'ID',
		];

		for (const key of keys) {
			if (shipment[key] == null) { continue; }

			this._wrapper.insertAdjacentHTML(
				'afterbegin',
				`<input type="hidden" name="${options.name}[${key}]" value="${shipment[key]}" />`
			);
		}
	}

	renderBoxCollection(shipment, options) {
		const boxCollection = new BoxCollection(Object.assign(options, {
			messages: Field.messages,
			mode: this._mode,
			name: options.name + '[BOX]',
			onChange: this._changeHandler,
		}));

		boxCollection.render(shipment);
		boxCollection.mount(this._wrapper);

		this.shipmentBoxes.push(boxCollection);
	}
}
