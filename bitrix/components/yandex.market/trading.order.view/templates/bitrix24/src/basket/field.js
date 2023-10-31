import ReferenceField from "../reference/field";
import ItemView from "./itemview";
import ItemEdit from "./itemedit";
import { htmlToElement, kebabCase } from "../utils";
import './basket.css';

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		name: 'BASKET',
		actions: [],
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	constructor() {
		super();
		this.items = [];
	}

	countChanges() {
		const result = [];

		for (const item of this.items) {
			if (!(item instanceof ItemEdit)) { continue; }

			const diff = item.getCountDiff();

			if (diff > 0) {
				result.push({
					name: item.getTitle(),
					diff: diff,
				});
			}
		}

		return result;
	}

	render(payload) {
		const basket = payload['VALUE'];
		const table = this.renderTable(basket);
		const body = table.querySelector('tbody');

		this.extendOptions(payload);

		this.renewItems(basket.ITEMS).forEach((basketItem, index) => {
			basketItem.render(basket.ITEMS[index], basket);
			basketItem.mount(body);
		});

		this._wrapper.appendChild(table);
	}

	extendOptions(payload) {
		this.options = Object.assign(this.options, {
			actions: payload['ACTIONS'],
		});
	}

	renewItems(payloadItems) {
		this.destroyItems();

		return this.createItems(payloadItems);
	}

	destroyItems() {
		for (const item of this.items) {
			item.destroy();
		}

		this.items = [];
	}

	createItems(payloadItems) {
		let itemIndex = 0;

		this.items = [];

		for (const payloadItem of payloadItems) {
			this.items.push(this.createItem(itemIndex));
			++itemIndex;
		}

		return this.items;
	}

	createItem(index) {
		const options = {
			messages: Field.messages,
			name: `${this.options.name}[${index}]`,
			actions: this.options.actions,
			onChange: this.hasAction(ItemEdit.ACTION_CIS) || this.hasAction(ItemEdit.ACTION_DIGITAL)
				? () => {
					if (this._mode !== BX.UI.EntityEditorMode.edit) {
						this._mode = BX.UI.EntityEditorMode.edit;
						this._editor.showToolPanel();
						this._editor.registerActiveControl(this);
					}

					this._changeHandler();
				}
				: this._changeHandler,
		};

		if (this._mode === BX.UI.EntityEditorMode.edit) {
			return new ItemEdit(options);
		}

		return new ItemView(options);
	}

	renderTable(basket) {
		return htmlToElement(`<div class="yamarket-basket">
			<div class="yamarket-basket-table-viewport">
				<table class="yamarket-basket-table">
					${this.renderHeader(basket)}
					<tbody></tbody>
				</table>
			</div>
			${this.renderSummary(basket)}
		</div>`);
	}

	renderHeader(basket) {
		return `<thead>
			<tr>
				<td class="for--index">&numero;</td>
				${Object.keys(basket.COLUMNS)
					.map((key) => `<td class="for--${kebabCase(key)}">${this.columnTitle(basket, key)}</td>`)
					.join('')}
				${this.isInEditMode() && this.hasAction(ItemEdit.ACTION_ITEM) ? '<td class="for--delete">&nbsp;</td>' : ''}
			</tr>
		</thead>`;
	}

	columnTitle(basket, key) {
		const langKey = 'HEADER_' + key;
		const lang = this.getMessage(langKey);

		return lang !== langKey ? lang : basket.COLUMNS[key];
	}

	renderSummary(basket) {
		if (basket.SUMMARY.length === 0) { return ''; }

		return `<div class="yamarket-basket-summary">
			${basket.SUMMARY
				.map((item) => {
					return `<div class="yamarket-basket-summary__row">
						<div class="yamarket-basket-summary__label">${item['NAME']}:</div>
						<div class="yamarket-basket-summary__value">${item['VALUE']}</div>
					</div>`;
				})
				.join('')}
		</div>`;
	}

	hasAction(type) {
		return this.options.actions.indexOf(type) !== -1;
	}
}