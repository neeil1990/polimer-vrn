(function() {

	function ymAddNewRow(tableId, regexp, button) {
		var targetId = resolveTable(tableId, button);

		addNewRow(targetId, regexp);
		clearNewRowValues(targetId);
		restoreTable(targetId, tableId);
	}

	function resolveTable(tableId, button) {
		if (button == null) { return tableId; }

		var table = button.closest('table');
		var origin = document.getElementById(tableId);
		var result = tableId;

		if (table != null && table !== origin) {
			result = BX.util.getRandomString(5);
			table.id = result;
		}

		return result;
	}

	function restoreTable(tableId, originId) {
		if (tableId === originId) { return; }

		var table = document.getElementById(tableId);

		table.id = originId;
	}

	function clearNewRowValues(tableId) {
		var table = document.getElementById(tableId);
		var rowsCount = table.rows.length;
		var newRow = table.rows[rowsCount - 2];
		var inputs = newRow ? newRow.querySelectorAll('input, textarea, select') : null;
		var inputIndex;
		var input;

		if (inputs) {
			for (inputIndex = inputs.length - 1; inputIndex >= 0; --inputIndex) {
				input = inputs[inputIndex];

				if (isInputEditable(input)) {
					clearInputValue(input);
				}
			}
		}
	}

	function isInputEditable(input) {
		var tagName = input.tagName.toLocaleLowerCase();
		var inputType = (input.type || '').toLowerCase();

		return (
			tagName !== 'button'
			&& inputType !== 'button'
			&& inputType !== 'submit'
		);
	}

	function clearInputValue(input) {
		var tagName = input.tagName.toLocaleLowerCase();
		var inputType = (input.type || '').toLowerCase();

		if (tagName === 'select') {
			clearSelectValue(input);
		} else if (inputType === 'checkbox' || inputType === 'radio') {
			input.checked = false;
		} else {
			input.value = '';
		}
	}

	 function clearSelectValue(select) {
		var options = select.querySelectorAll('option');
		var optionIndex;
		var option;

		for (optionIndex = options.length - 1; optionIndex >= 0; --optionIndex) {
			option = options[optionIndex];

			if (option.selected) {
				option.selected = false;
			}
		}
	 }

	window.ymAddNewRow = ymAddNewRow;

})();
