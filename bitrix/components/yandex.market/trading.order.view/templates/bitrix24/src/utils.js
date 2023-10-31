export function htmlToElement(html, tag = 'div') {
	const renderer = document.createElement(tag);

	renderer.innerHTML = html;

	return renderer.firstElementChild;
}

export function pascalCase(str) {
	return str
		.split('_')
		.map((word) => word.substr(0, 1).toUpperCase() + word.substr(1).toLowerCase())
		.join('');
}

export function camelCase(str) {
	return str
		.split('_')
		.map((word, index) => index === 0 ? word.toLowerCase() : word.substr(0, 1).toUpperCase() + word.substr(1).toLowerCase())
		.join('');
}

export function kebabCase(str) {
	return str
		.split('_')
		.map((word, index) =>  word.toLowerCase())
		.join('-');
}

export function copyValues(from, to) {
	const fromValues = collectValues(from);

	return fillValues(to, fromValues);
}

export function collectValues(container) {
	const inputs = findInputs(container);
	const result = {};

	for (const [name, input] of Object.entries(inputs)) {
		if (input.tagName.toLowerCase() === 'select') {
			for (const option of input.querySelectorAll('option')) {
				if (!option.selected) { continue; }

				result[name] = option.value;
			}
		} else {
			result[name] = input.value;
		}
	}

	return result;
}

export function fillValues(container, values) {
	const inputs = findInputs(container);
	let isChanged = false;

	for (const [name, value] of Object.entries(values)) {
		if (inputs[name] == null) { continue; }

		const input = inputs[name];

		if (input.tagName.toLowerCase() === 'select') {
			for (const option of input.querySelectorAll('option')) {
				const selected = (option.value === value);

				if (selected === Boolean(option.selected)) { continue; }

				option.selected = selected;
				isChanged = true;
			}
		} else {
			if (input.value === value) { continue; }

			isChanged = true;
			input.value = value;
		}
	}

	return isChanged;
}

export function findInputs(container) {
	const inputs = container.querySelectorAll('input, select, textarea');
	const result = {};

	for (const input of inputs) {
		if (!input.name) { continue; }

		result[input.name] = input;
	}

	return result;
}

export function replaceTemplateVariables(template, variables) {
	let result = template;

	if (variables == null) { return result; }

	for (const [key, value] of Object.entries(variables)) {
		result = result.replace('#' + key + '#', value);
	}

	return result;
}