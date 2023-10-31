import ReferenceField from "../reference/field";
import { htmlToElement } from "../utils";
import './print.css';

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		url: null,
		width: 400,
		height: 300,
		items: [],
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	bindDocumentsClick() {
		this._wrapper.querySelectorAll('.yamarket-print__link').forEach((link) => {
			link.addEventListener('click', this.onDocumentClick);
		});
	}

	onDocumentClick = (evt) => {
		const link = evt.currentTarget;
		const type = link.dataset.type;
		const item = this.getItem(type);
		const url = this.buildDocumentUrl(type);
		const dialog = this.createDialog(url, item);

		dialog.Show();

		evt.preventDefault();
	}

	getItem(type) {
		let result;

		for (const document of this.options.items) {
			if (document['TYPE'] === type) {
				result = document;
				break;
			}
		}

		return result;
	}

	buildDocumentUrl(type) {
		const url = this.options.url;

		return (
			url
			+ (url.indexOf('?') === -1 ? '?' : '&')
			+ 'type=' + type
		);
	}

	createDialog(url, item) {
		return new BX.YandexMarket.PrintDialog({
			title: item.DIALOG_TITLE || item.TITLE,
			content_url: url,
			width: item.WIDTH || this.options.width,
			height: item.HEIGHT || this.options.height,
			buttons: [
				BX.YandexMarket.PrintDialog.btnSave,
				BX.YandexMarket.PrintDialog.btnCancel
			]
		});
	}

	render(value) {
		this.extendOptions(value);

		this.renderIntro();
		this.renderDocuments(value['ITEMS']);

		this.bindDocumentsClick();
	}

	extendOptions(value) {
		this.options = Object.assign(this.options, {
			url: value['URL'],
			items: value['ITEMS'],
		});
	}

	renderIntro() {
		const element = htmlToElement(`<p class="yamarket-print__intro">${this.getMessage('INTRO')}</p>`);

		this._wrapper.appendChild(element);
	}

	renderDocuments(items) {
		if (!Array.isArray(items)) { return; }

		const element = htmlToElement(`<ul class="yamarket-print__documents">
			${items.map((document) => `<li class="yamarket-print__document">
				<a class="yamarket-print__link" href="#" data-type="${document['TYPE']}">${document['TITLE']}</a>
			</li>`).join('')}
		</ul>`);

		this._wrapper.appendChild(element);
	}
}