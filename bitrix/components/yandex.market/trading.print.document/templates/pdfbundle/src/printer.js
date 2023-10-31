/**
 * @original https://github.com/Munawwar/merge-pdfs-on-browser/blob/master/public/pdfjs-approach/pdf-merger-js-adapted.js
 *
 * Thanks
 * https://www.npmjs.com/package/pdfjs
 * https://www.npmjs.com/package/pdf-merger-js
 */
const pdfjs = window.pdfjs;

window.PdfPrinter = class PdfPrinter {
	defaults = {
		loadingElement: '.js-pdf-bundler__loading',
		readyElement: '.js-pdf-bundler__ready',
		totalElement: '.js-pdf-bundler__total',
		processingElement: '.js-pdf-bundler__processing',
		errorElement: '.js-pdf-bundler__error',

		parallel: 2,
	}

	constructor(selector, options) {
		this.el = document.querySelector(selector);
		this.options = Object.assign({}, this.defaults, options);
		this.readyCount = 0;
		this.lockCount = 0;
		this.waitPromise = null;
		this.waitResolve = null;
	}

	async load(urls) {
		try {
			this.setTotal(urls.length);
			this.showElement('loading');

			const files = await this.fetchFiles(urls);

			this.hideElement('loading');
			this.showElement('processing');

			const document = this.makeDocument(files);
			const buffer = await this.readDocument(document);

			this.showDocument(buffer);
		} catch (error) {
			this.hideElement('loading');
			this.hideElement('processing');
			this.showError(error.message);
		}
	}

	setTotal(total) {
		this.getElement('total').textContent = total;
	}

	showElement(key) {
		this.getElement(key).style.display = '';
	}

	hideElement(key) {
		this.getElement(key).style.display = 'none';
	}

	increaseReady() {
		this.getElement('ready').textContent = ++this.readyCount;
	}

	showError(message) {
		const errorNode = this.getElement('error');

		errorNode.style.display = '';
		errorNode.textContent += ' ' + message;
	}

	async fetchFiles(urls) {
		let queue = [];
		let error;
		let finished = false;

		for (const url of urls) {
			const request = this.fetchFile(url)
				.then((file) => {
					this.unlock();
					this.increaseReady();

					return file;
				})
				.catch((fileError) => {
					if (finished) {
						throw fileError;
					} else {
						error = fileError;
					}

					this.unlock();
				});

			queue.push(request);
			await this.lock();

			if (error != null) { throw error; }
		}

		finished = true;

		return await Promise.all(queue);
	}

	fetchFile(url) {
		return fetch(url)
			.then(async (response) => {
				if (!response.ok) {
					throw new Error("HTTP error, status = " + response.status);
				}

				if (response.headers.get('Content-Type') !== 'application/pdf') {
					let contents = await response.text();
					let message = this.stripHtml(contents);

					throw new Error(message);
				}

				return response.arrayBuffer();
			});
	}

	stripHtml(text) {
		let tmp = document.createElement('div');
		tmp.innerHTML = text;

		return (tmp.textContent || tmp.innerText || '').trim();
	}

	lock() {
		++this.lockCount;

		return this.lockCount >= this.options.parallel
			? this.wait()
			: null;
	}

	unlock() {
		--this.lockCount;

		if (this.lockCount < this.options.parallel) {
			this.release();
		}
	}

	wait() {
		if (this.waitPromise === null) {
			this.waitPromise = new Promise((resolve) => {
				this.waitResolve = resolve;
			});
		}

		return this.waitPromise;
	}

	release() {
		if (this.waitResolve === null) { return; }

		const resolve = this.waitResolve;

		this.waitPromise = null;
		this.waitResolve = null;

		resolve();
	}

	makeDocument(files) {
		const document = new pdfjs.Document();

		files.forEach((fileBuffer) => {
			const ext = new pdfjs.ExternalDocument(fileBuffer);
			document.setTemplate(ext);
			document.addPagesOf(ext);
		});

		document.end();

		return document;
	}

	async readDocument(readable) {
		const chunks = [];
		for await (const chunk of readable) {
			chunks.push(chunk);
		}
		return Buffer.concat(chunks);
	}

	showDocument(buffer) {
		const blob = new Blob([buffer], {type : 'application/pdf'});

		window.location.href = window.URL.createObjectURL(blob);
	}

	getElement(key) {
		const selector = this.options[key + 'Element'];

		return this.el.querySelector(selector);
	}
}