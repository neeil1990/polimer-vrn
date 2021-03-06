;(function()
{
	"use strict";

	BX.namespace("BX.Landing");
	BX.Landing.EmbedForms = function()
	{
		/**
		 * @type {BX.Landing.EmbedFormEntry[]}
		 */
		this.forms = [];
	}

	BX.Landing.EmbedForms.prototype = {
		add: function(formNode)
		{
			var form = new BX.Landing.EmbedFormEntry(formNode);
			this.forms.push(form);
			form.init();
		},

		remove: function(formNode)
		{
			var formToRemove = this.getFormByNode(formNode);
			if (formToRemove)
			{
				formToRemove.unload();

				this.forms = this.forms.filter(function(form)
				{
					return form !== formToRemove;
				});
			}
		},

		reload: function(formNode)
		{
			this.remove(formNode);
			this.add(formNode);
		},

		getFormByNode: function(formNode)
		{
			var result = null;
			this.forms.forEach(function(form)
			{
				if (formNode === form.getNode())
				{
					result = form;
					return true;
				}
			});

			return result;
		}
	}

	BX.Landing.EmbedFormEntry = function(formNode)
	{
		this.node = formNode;
		this.formObject = null;
		this.init();
	};

	BX.Landing.EmbedFormEntry.ATTR_FORM_ID = 'b24form';
	BX.Landing.EmbedFormEntry.ATTR_FORM_ID_STR = 'data-b24form';
	BX.Landing.EmbedFormEntry.ATTR_USE_STYLE = 'b24formUseStyle';
	BX.Landing.EmbedFormEntry.ATTR_USE_STYLE_STR = 'data-b24form-use-style';
	BX.Landing.EmbedFormEntry.ATTR_DESIGN = 'b24formDesign';
	BX.Landing.EmbedFormEntry.ATTR_IS_CONNECTOR = 'b24formConnector';

	BX.Landing.EmbedFormEntry.prototype = {
		init: function()
		{
			var formParams = this.node.dataset.b24form;
			if(!formParams)
			{
				this.showNoFormsMessage();
				return;
			}
			formParams = formParams.split('|');
			if (formParams.length !== 3)
			{
				this.showNoFormsMessage();
				return;
			}
			this.id = formParams[0];
			this.sec = formParams[1];
			this.url = formParams[2];
			this.useStyle = (this.node.dataset[BX.Landing.EmbedFormEntry.ATTR_USE_STYLE] === 'Y');
			this.design = this.node.dataset[BX.Landing.EmbedFormEntry.ATTR_DESIGN]
				? JSON.parse(this.node.dataset[BX.Landing.EmbedFormEntry.ATTR_DESIGN])
				: {};
			this.primaryOpacityMatcher = new RegExp("--primary([\\da-fA-F]{2})");

			this.load();
		},

		showNoFormsMessage: function()
		{
			var errorText =
				(
					this.node.dataset[BX.Landing.EmbedFormEntry.ATTR_IS_CONNECTOR]
					&& this.node.dataset[BX.Landing.EmbedFormEntry.ATTR_IS_CONNECTOR] === 'Y'
				)
				? BX.message('LANDING_BLOCK_WEBFORM_NO_FORM_BUS_NEW')
				: BX.message('LANDING_BLOCK_WEBFORM_NO_FORM_CP')
			;
			this.node.innerHTML = this.createErrorMessage(BX.message('LANDING_BLOCK_WEBFORM_NO_FORM'), errorText);
		},

		createErrorMessage: function (title, message)
		{
			// show alert only in edit mode
			if (BX.Landing.getMode() === "view")
			{
				return;
			}

			if (title === undefined || title === null || !title)
			{
				title = BX.message('LANDING_BLOCK_WEBFORM_ERROR');
			}

			if (message === undefined || message === null || !message)
			{
				message = BX.message('LANDING_BLOCK_WEBFORM_CONNECT_SUPPORT');
			}

			var alertHtml = '<h2 class="u-form-alert-title"><i class="fa fa-exclamation-triangle g-mr-15"></i>'
				+ title
				+ '</h2><hr class="u-form-alert-divider">'
				+ '<p class="u-form-alert-text">' + message + '</p>';

			return '<div class="u-form-alert">' + alertHtml + '</div>';
		},

		load: function()
		{
			this.node.innerHTML = '';	//clear "no form" alert
			this.loadScript();
		},

		unload: function()
		{
			if (!b24form || !b24form.App || !this.formObject)
			{
				return;
			}

			b24form.App.remove(this.formObject.getId());
		},

		getNode: function()
		{
			return this.node;
		},

		setFormObject: function(object)
		{
			this.formObject = object;
		},

		loadScript: function()
		{
			var cacheTime = (BX.Landing.getMode() === "edit")
				? Date.now() / 1000 | 0
				: Date.now() / 60000 | 0;
			var script = document.createElement('script');
			script.setAttribute('data-b24-form', 'inline/' + this.id + '/' + this.sec);
			script.setAttribute('data-skip-moving', 'true');
			script.innerText =
				"(function(w,d,u){" +
				"var s=d.createElement('script');s.async=true;s.src=u+'?'+(" + cacheTime + ");" +
				"var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);" +
				"})(window,document,'" + this.url + "')"
			;
			this.node.append(script);
		},

		onFormLoad: function(formObject)
		{
			this.setFormObject(formObject);
			if (this.useStyle)
			{
				this.formObject.adjust(this.getParams());
			}
		},

		getParams: function()
		{
			var params = {
				design: {
					shadow: false
				}
			};

			var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
			var design = this.design;
			for (var property in design.color)
			{
				if (
					design.color[property] === '--primary'
					|| design.color[property].match(this.primaryOpacityMatcher) !== null
				)
				{
					design.color[property] = design.color[property].replace('--primary', primaryColor);
				}
			}
			params.design = Object.assign(params.design, design);
			return params;
		}
	};

	var embedForms = new BX.Landing.EmbedForms();

	window.addEventListener('b24:form:init', function(event)
	{
		var form = embedForms.getFormByNode(event.detail.object.node.parentElement)
		if (!!form && event.detail.object)
		{
			form.onFormLoad(event.detail.object);
		}
	});

	BX.addCustomEvent("BX.Landing.Block:init", function(event)
	{
		var formNode = event.block.querySelector(event.makeRelativeSelector(".bitrix24forms"));
		if (formNode)
		{
			embedForms.add(formNode);
		}
	});

	BX.addCustomEvent("BX.Landing.Block:remove", function(event)
	{
		var formNode = event.block.querySelector(event.makeRelativeSelector(".bitrix24forms"));
		if (formNode)
		{
			embedForms.remove(formNode);
		}
	});

	BX.addCustomEvent("BX.Landing.Block:Node:updateAttr", function(event)
	{
		var formNode = event.block.querySelector(event.makeRelativeSelector(".bitrix24forms"));
		if (formNode)
		{
			embedForms.reload(formNode);
		}
	});
})();