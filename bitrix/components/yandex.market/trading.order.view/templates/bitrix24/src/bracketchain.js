export class BracketChain {

	static toTree(values, base = null) {
		const result = {};

		for (const key in values) {
			if (!values.hasOwnProperty(key)) { continue; }

			const keyRelative = this.keyRelative(key, base);

			if (keyRelative == null) { continue; }

			const keyChain = this.valueKeyChain(keyRelative);

			this.setValueByKeyChain(result, keyChain, values[key]);
		}

		return result;
	}

	static keyRelative(key, base) {
		if (base == null) { return key; }
		if (key.indexOf(base) !== 0) { return null; }

		return key.replace(base, '');
	}

	static valueKeyChain(key) {
		const parts = key.split('[');
		const result = [];

		for (const part of parts) {
			if (part === '' && result.length === 0) { continue; }

			const name = part.replace(/]$/, '');

			result.push(name);
		}

		return result;
	}

	static setValueByKeyChain(result, chain, value) {
		const lastKey = chain.pop();
		let level = result;

		for (let index = 0; index < chain.length; ++index) {
			const key = chain[index];
			const nextKey = (index + 1 === chain.length) ? lastKey : chain[index + 1];

			if (level[key] == null) {
				level[key] = /^\d+$/.test(nextKey) ? [] : {};
			}

			level = level[key];
		}

		level[lastKey] = value;
	}

}