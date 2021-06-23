(function() {

	var BX = window.BX || top.BX;
	var CHECK_FORM_ELEMENTS = {tagName: /^INPUT|SELECT|TEXTAREA|BUTTON$/i};

	if (BX == null) { return; }

	BX.findFormElements = function(form)
	{
		if (BX.type.isString(form))
			form = document.forms[form]||BX(form);

		var res = [];

		if (BX.type.isElementNode(form))
		{
			if (form.tagName.toUpperCase() === 'FORM')
			{
				res = form.elements;
			}
			else if (form.querySelectorAll != null)
			{
				res = form.querySelectorAll('input, select, textarea, button');
			}
			else
			{
				res = BX.findChildren(form, CHECK_FORM_ELEMENTS, true);
			}
		}

		return res;
	};

})();