import ItemView from "./itemview";
import {kebabCase} from "../utils";

export default class ItemEdit extends ItemView {
	static ACTION_ITEM = 'item';
	static ACTION_CIS = 'cis';
	static ACTION_DIGITAL = 'digital';

	static defaults = Object.assign({}, ItemView.defaults)

	destroy() {
		this.unbind();
		super.destroy();
	}

	unbind() {
		this.handleInputChange(false);
		this.handleDeleteToggle(false);
	}

	handleInputChange(dir) {
		if (!this.options.onChange) { return; }

		this.el.querySelectorAll('input').forEach((input) => {
			if (input.name === this.getName('COUNT')) {
				input[dir ? 'addEventListener' : 'removeEventListener']('change', this.onCountChange);
			}

			input[dir ? 'addEventListener' : 'removeEventListener']('change', this.options.onChange);
		});
	}

	handleDeleteToggle(dir) {
		const input = this.el.querySelector('.yamarket-delete-toggle__input');

		if (!this.hasAction(ItemEdit.ACTION_ITEM) || !input) { return; }

		input[dir ? 'addEventListener' : 'removeEventListener']('change', this.onDeleteToggle);
	}

	onCountChange = (evt) => {
		const count = parseInt(evt.target.value);

		if (isNaN(count)) { return; }

		this.callWires('updateTotal', [count]);
	}

	onDeleteToggle = (evt) => {
		const isDelete = !!evt.target.checked;

		this.el.classList.toggle('is--delete', isDelete);
		this.disableInput(isDelete);
	}

	disableInput(dir) {
		this.el.querySelectorAll('input').forEach((input) => {
			if (input.name === this.getName('DELETE')) { return; }

			input.readonly = dir;

			if (input.classList.contains('ui-ctl-element')) {
				input.parentElement.classList.toggle('ui-ctl-disabled', dir);
			}
		});
	}

	getCountDiff() {
		const needDelete = this.getInputValue('DELETE');
		const initialCount = parseInt(this.getInputValue('INITIAL_COUNT'));
		const count = parseInt(this.getInputValue('COUNT'));
		let result;

		if (needDelete) {
			result = initialCount;
		} else {
			result = initialCount - count;
		}

		return result;
	}

	render(item, basket) {
		super.render(item, basket);
		this.handleInputChange(true);
		this.handleDeleteToggle(true);
	}

	columnCount(item, key) {
		if (!this.hasAction(ItemEdit.ACTION_ITEM)) { return super.columnCount(item, key); }

		const value = this.value(item, key);
		const valueSanitized = parseFloat(value) || '';

		return `<td class="for--${kebabCase(key)}">
			<input type="hidden" name="${this.getName('INITIAL_COUNT')}" value="${valueSanitized}" />
			<div class="ui-ctl ui-ctl-sm ui-ctl-textbox ui-ctl-w100">
				<input
					class="ui-ctl-element"
					type="number"
					name="${this.getName('COUNT')}"
					value="${valueSanitized}"
					min="1"
					max="${valueSanitized}"
					step="1"
				/>
			</div>
		</td>`;
	}

	renderActions() {
		if (!this.hasAction(ItemEdit.ACTION_ITEM)) { return ''; }

		return `<td class="for--delete">
			<label class="yamarket-delete-toggle">
				<input class="yamarket-delete-toggle__input" type="checkbox" name="${this.getName('DELETE')}" value="Y" />
				<span class="yamarket-delete-toggle__icon icon--delete" title="${this.getMessage('ITEM_DELETE')}">${this.getMessage('ITEM_DELETE')}</span>
				<span class="yamarket-delete-toggle__icon icon--restore" title="${this.getMessage('ITEM_RESTORE')}">${this.getMessage('ITEM_RESTORE')}</span>
			</label>
		</td>`;
	}

	getInputValue(field) {
		const input = this.getInput(field);
		let result;

		if (input == null) { return null; }

		if (input.type === 'checkbox') {
			result = input.checked ? input.value : null;
		} else {
			result = input.value;
		}

		return result;
	}

	getInput(field) {
		const name = this.getName(field);

		return this.el.querySelector(`input[name="${name}"]`);
	}
}