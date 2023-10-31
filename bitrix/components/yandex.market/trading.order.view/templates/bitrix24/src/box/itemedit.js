import ItemView from "./itemview";
import './box.css';

export default class ItemEdit extends ItemView {

	destroy() {
		this.unbind();
	}

	unbind() {
		this.handleInputChange(false);
		this.handleDeleteClick(false);
	}

	handleInputChange(dir) {
		if (!this.options.onChange) { return; }

		this.el.querySelectorAll('input').forEach((input) => {
			if (input.type === 'hidden') { return; }

			input[dir ? 'addEventListener' : 'removeEventListener']('change', this.options.onChange);
		});
	}

	handleDeleteClick(dir) {
		const button = this.el.querySelector('.yamarket-box__delete');

		if (!button) { return; }

		button[dir ? 'addEventListener' : 'removeEventListener']('click', this.onDeleteClick);
	}

	onDeleteClick = (evt) => {
		this.remove();
		evt.preventDefault();
	}

	toggleUseDimensions(enabled) {
		super.toggleUseDimensions(enabled);

		if (this.el == null) { return; }

		const body = this.el.querySelector('.yamarket-box__body');

		if (body == null) { return; }

		if (enabled) {
			body.classList.remove('is--disabled');
		} else {
			body.classList.add('is--disabled');
		}
	}

	remove() {
		this.fireRemove();
		this.destroy();
		this.el.remove();
	}

	fireRemove() {
		this.el.dispatchEvent(new CustomEvent('yamarketBoxDelete', {
			detail: {
				item: this,
			},
			bubbles: true,
		}));
	}

	render(value) {
		super.render(value);
		this.handleInputChange(true);
		this.handleDeleteClick(true);
	}

	disabledProperties() {
		return [
			'SIZE',
			'WEIGHT',
		];
	}

	buildActions(value) {
		return `<div class="yamarket-box__actions">
			<span class="yamarket-box__delete ui-entity-editor-header-edit-lnk">${this.getMessage('BOX_DELETE')}</span>
		</div>`;
	}

	buildBody(value) {
		return `<div class="yamarket-box__body ${this.options.useDimensions ? '' : 'is--disabled'}">
			${this.buildSizes(value)}
		</div>`;
	}

	buildSizes(value) {
		return `<div class="yamarket-box__sizes">
			${Object.entries(this.options.boxDimensions).map(([name, property]) => {
				const inputName = this.getName('DIMENSIONS') + `[${name}]`;
				const inputValue = value?.DIMENSIONS?.[name]?.['VALUE'] || '';
	
				return `<div class="yamarket-box__size">
					<div class="ui-entity-editor-content-block ui-entity-editor-field-text">
						<div class="ui-entity-editor-block-title ui-entity-widget-content-block-title-edit">
				            <label class="ui-entity-editor-block-title-text">${property['NAME']}${property['UNIT_FORMATTED'] ? ', ' + property['UNIT_FORMATTED'] : ''}</label>
				        </div>
				        <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				            <input class="ui-ctl-element" type="text" name="${inputName}" value="${BX.util.htmlspecialchars(inputValue)}" size="6" data-name="${name}" />
				        </div>
			       </div>
				</div>`;
			}).join('')}
		</div>`;
	}
}