(function($, window) {

	$.fn.select2.amd.define('select2/data/search', [
		'./array',
		'../utils',
		'jquery'
	], function(ArrayAdapter, Utils, $) {
		function SearchAdapter ($element, options) {
			this.searchVariants = options.get('data');

			options.set('data', null); // reset data for skip inside array

			SearchAdapter.__super__.constructor.call(this, $element, options);
		}

		Utils.Extend(SearchAdapter, ArrayAdapter);

		SearchAdapter.prototype.query = function (params, callback) {
			var data = [];
			var self = this;
			var term = params.term || '';

			if (term.length > 0) {
				if (typeof this.searchVariants === 'function') {
					this.searchVariants = this.searchVariants();
				}

				this.searchVariants.forEach(function (option) {
					var matches = self.matches(params, option);

					if (matches !== null) {
						data.push(matches);
					}
				});
			}

			callback({
				results: data
			});
		};

		return SearchAdapter;
	});

})(jQuery, window);