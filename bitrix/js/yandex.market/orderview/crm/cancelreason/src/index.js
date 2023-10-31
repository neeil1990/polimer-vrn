// @flow

export class CancelReason {

	static defaults = {
		entityType: 'ORDER',
		entityId: null,
		variants: [],
	}

	constructor(options: Object = {}) {
		this.options = Object.assign({}, this.constructor.defaults, options);

		this.bind();
	}

	bind() {
		BX.addCustomEvent('CrmProcessFailureDialogContentCreated', this.onFailureDialogCreated);
		BX.addCustomEvent('CrmProgressControlBeforeFailureDialogClose', this.onBeforeFailureDialogClose);
	}

	onFailureDialogCreated = (dialog) => {
		if (!this.isMatchDialog(dialog)) { return; }

		const wrapper = dialog.getWrapper();
		const previous = wrapper.querySelector('textarea[name="REASON_CANCELED"]');

		if (previous == null) { return; }

		previous.insertAdjacentHTML('afterend', this.buildSelect(previous.value));
		previous.remove();
	}

	onBeforeFailureDialogClose = (control, dialog) => {
		if (!this.isMatchDialog(dialog)) { return; }

		const statusManager = BX.CrmOrderStatusManager.current;
		const wrapper = dialog.getWrapper();
		const select = wrapper.querySelector('select[name="REASON_CANCELED"]');

		if (select == null) { return; }

		if (typeof statusManager.saveParams !== 'object')
		{
			console.warn('missing statusManager.saveParams');
			return;
		}

		statusManager.saveParams[select.name] = select.value;
	}

	isMatchDialog(dialog) {
		const wrapper = dialog.getWrapper();
		const entityType = dialog.getEntityType();
		const entityId = dialog.getEntityId();

		return (
			wrapper
			&& entityType === this.options.entityType
			&& entityId != null
			&& ('' + entityId) === ('' + this.options.entityId)
		);
	}

	buildSelect(selected: ?string) {
		let foundSelected = false;

		return `<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
			<select class="ui-ctl-element" name="REASON_CANCELED" style="max-width: 100%">
				${this.options.variants.map((variant) => {
					const isSelected = (variant['ID'] === selected);
					
					if (isSelected) { foundSelected = true; }
					
					return `<option value="${variant['ID']}" ${isSelected ? 'selected' : ''}>${variant['VALUE']}</option>`;
				}).join('')}
				${!foundSelected && selected != null && selected !== '' ? `<option selected>${selected}</option>` : ''}
			</select>
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
		</div>`;
	}

}