import {copyValues, replaceTemplateVariables} from "../utils";
import './summary.css';

export default class SummarySkeleton {

	static STATUS_READY = 'READY';
	static STATUS_WAIT = 'WAIT';
	static STATUS_EMPTY = 'EMPTY';

	static defaults = {
		modalElement: '.yamarket-item-summary__modal',
		statusElement: '.yamarket-item-summary__status',
		inputElement: 'input',

		name: null,
		messages: {},
		onChange: null,
	};

	constructor(options) {
		this.options = Object.assign({}, this.constructor.defaults, options);
		this.el = null;
	}

	bind() {
		this.handleStatusClick(true);
	}

	handleStatusClick(dir) {
		const status = this.getElement('status');

		if (status == null) { return; }

		status[dir ? 'addEventListener' : 'removeEventListener']('click', this.onStatusClick);
	}

	onStatusClick = (evt) => {
		this.openDialog();
		evt.preventDefault();
	}

	setup(element) {
		this.el = element;
		this.bind();
	}

	build() {
		return `<div class="yamarket-item-summary">
			${this.buildStatus()}
			<div class="yamarket-item-summary__modal" hidden>
				${this.buildForm()}
			</div>
		</div>`;
	}

	buildStatus() {
		const cises = this.optionValue();
		const status = this.getStatus(cises);

		return `<a class="yamarket-item-summary__status" href="#" data-status="${status}">${this.getMessage('SUMMARY_' + status)}</a>`
	}

	reflowStatus() {
		const value = this.formValue();
		const status = this.getStatus(value);
		const element = this.getElement('status');

		element.setAttribute('data-status', status);
		element.textContent = this.getMessage('SUMMARY_' + status);
	}

	/**
	 * @param {any} value
	 *
	 * @return {string}
	 */
	getStatus(value) {
		throw new Error('not implemented');
	}

	/** @return {string} */
	buildForm(useFormValue = false) {
		throw new Error('not implemented');
	}

	reflowForm() {
		const modal = this.el.querySelector('.yamarket-item-summary__modal');

		modal.innerHTML = this.buildForm(true);
	}

	/** @return {any} */
	optionValue() {
		throw new Error('not implemented');
	}

	/** @return {any} */
	formValue() {
		throw new Error('not implemented');
	}

	openDialog() {
		const modal = this.getElement('modal').firstElementChild;
		const options  = this.dialogOptions();
		const messageBox = BX.UI.Dialogs.MessageBox.create(options);

		messageBox.setMessage(modal.cloneNode(true));
		messageBox.setButtons([
			new BX.UI.SaveButton({
				events: {
					click: this.onSaveClick.bind(this, messageBox),
				}
			}),
			new BX.UI.CancelButton({
				events: {
					click: this.onCancelClick.bind(this, messageBox),
				}
			}),
		]);

		messageBox.show();
		copyValues(this.el, messageBox.popupWindow.contentContainer);
	}

	dialogOptions() {
		return {
			title: this.getMessage('MODAL_TITLE'),
		};
	}

	onSaveClick(messageBox) {
		const isChanged = copyValues(messageBox.popupWindow.contentContainer, this.el);

		if (isChanged && this.options.onChange) {
			this.options.onChange();
		}

		this.reflowStatus();

		messageBox.close();
	}

	onCancelClick(messageBox) {
		messageBox.close();
	}

	getMessage(key, replaces = null) {
		let result = this.options.messages[key] || key;

		if (replaces != null) {
			result = replaceTemplateVariables(result, replaces);
		}

		return result;
	}

	fewElements(key) {
		const selector = this.getElementSelector(key);

		return this.el.querySelectorAll(selector);
	}

	getElement(key) {
		const selector = this.getElementSelector(key);

		return this.el.querySelector(selector);
	}

	getElementSelector(key) {
		return this.options[key + 'Element'];
	}

}