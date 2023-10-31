(function(BX, window) {

	'use strict';

	var Components = BX.namespace('YandexMarket.Components');

	var constructor = Components.AutocompleteLookup = function(params) {
		constructor.superclass.constructor.apply(this, [params]);
	};

	BX.extend(constructor, JCMainLookupAdminSelector);

	constructor.prototype.Init = function() {
		if (!!this.bInit) { return; }

		constructor.superclass.Init.call(this);

		this.modifyVisual();

		if (this.arParams.VALUE == null) {
			this.resolveInitialValue();
		}
	}

	constructor.prototype.modifyVisual = function() {
		var visual = this.VISUAL;

		visual.__split_reg = /([;\n])/;
		visual.__check_reg = /^(.*?) \[(\d+)]/m;

		visual.onUnidentifiedTokenFound = function(str) {
			var matches = this.__check_reg.exec(str);

			if (matches) {
				this.SetTokenData(str, {
					ID: matches[2],
					NAME: matches[1]
				});
			}
		}
	};

	constructor.prototype.resolveInitialValue = function() {
		var visual = this.VISUAL;
		var tokens = this.VISUAL.__parse(visual.TEXT.value, visual.__split_reg, visual.__check_reg, visual.arTokens);
		var tokenIndex;
		var token;
		var tokenValue;
		var tokenMatches;
		var values = [];

		for (tokenIndex = 0; tokenIndex < tokens.length; tokenIndex++) {
			token = tokens[tokenIndex];
			tokenValue = token.tok.trim();
			tokenMatches = visual.__check_reg.exec(tokenValue);

			if (tokenMatches != null) {
				values.push({
					ID: tokenMatches[2],
					NAME: tokenMatches[1]
				});
			}
		}

		if (values.length > 0) {
			this.SetValue(values);
		}
	};

})(BX, window);

