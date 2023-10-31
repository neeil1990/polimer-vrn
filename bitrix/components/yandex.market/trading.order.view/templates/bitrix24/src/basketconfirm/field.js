import ReferenceField from "../reference/field";
import { htmlToElement, copyValues } from "../utils";

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		name: 'BASKET_CONFIRM',
		reasons: [],
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	validate(validator) {
		const basket = this._parent.getChildById('BASKET');

		if (basket == null) { return; }

		const countChanges = basket.countChanges();

		if (countChanges.length === 0) { return; }

		return this.showDialog(countChanges, validator);
	}

	showDialog(countChanges, validator) {
		return new Promise((resolve) => {
			const messageBox = BX.UI.Dialogs.MessageBox.create({
				title: this.getMessage('MODAL_TITLE'),
				message: this.dialogBody(countChanges),
			});

			messageBox.setButtons([
				new BX.UI.SendButton({
					events: {
						click: this.onSendClick.bind(this, messageBox, resolve),
					}
				}),
				new BX.UI.CancelButton({
					events: {
						click: this.onCancelClick.bind(this, messageBox, validator, resolve),
					}
				})
			]);

			messageBox.show();
		});
	}

	dialogBody(countChanges) {
		return `
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${this.getMessage('REASON')}</div>
				</div>
				<div class="ui-form-content">
					${this.reasonControl()}
				</div>
			</div>
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${this.getMessage('PRODUCTS')}</div>
				</div>
				<div class="ui-form-content">
					${this.productsControl(countChanges)}
				</div>
			</div>
			<div class="ui-alert ui-alert-warning">${this.getMessage('FORM_INTRO')}</div>
		`;
	}

	reasonControl() {
		return `<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
			<select class="ui-ctl-element" name="${this.options.name}[REASON]">
				${this.options.reasons.map((option) => `<option value="${option['ID']}">${option['VALUE']}</option>`).join('')}
			</select>
		</div>`
	}

	productsControl(changes) {
		return changes
			.map((change) => {
				return this.getMessage('ITEM_CHANGE', {
					NAME: change.name,
					COUNT: change.diff,
				});
			})
			.join('<hr />');
	}

	onSendClick(messageBox, resolve) {
		copyValues(messageBox.popupWindow.contentContainer, this.el);
		messageBox.close();
		resolve();
	}

	onCancelClick(messageBox, validator, resolve) {
		const error = BX.UI.EntityValidationError.create({
			field: this,
		});

		validator.addError(error);
		messageBox.close();
		resolve();
	}

	render(payload) {
		this.extendOptions(payload);

		this.el = htmlToElement(`<div class="ui-helper-hidden">
			<input type="hidden" name="${this.options.name}[REASON]" value="" />
		</div>`)

		this._wrapper.appendChild(this.el);
	}

	extendOptions(payload) {
		this.options = Object.assign(this.options, {
			reasons: payload['ITEMS_CHANGE_REASON'],
		});
	}
}