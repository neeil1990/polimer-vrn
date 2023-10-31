(function(BX) {
	BX.namespace('BX.SidePanel');

	if (BX.SidePanel.Instance == null) { return; }

	BX.SidePanel.Instance.bindAnchors({
		rules: [
			{
				condition: [
					/^\/yandexmarket\/marketplace\//i,
				],
				validate: function(link) {
					return false;
				},
			},
		],
	});
})(BX);