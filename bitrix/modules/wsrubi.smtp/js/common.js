BX.ready(function(){
	arProfile = {
		'yandex':{'settings_smtp_type_auth':'login','settings_smtp_host':'smtp.yandex.ru','settings_smtp_port':465,'settings_smtp_type_encryption':'ssl'},
		'google':{'settings_smtp_type_auth':'login','settings_smtp_host':'smtp.gmail.com','settings_smtp_port':465,'settings_smtp_type_encryption':'ssl'},
		'mail.ru':{'settings_smtp_type_auth':'login','settings_smtp_host':'smtp.mail.ru','settings_smtp_port':465,'settings_smtp_type_encryption':'ssl'},
	};
    $('#wsrubismtpoptionsform select[name=type_profile]').change(function(){
		nameProfile = $(this).val();
		mainProfile = arProfile[nameProfile];
		if(mainProfile != undefined){
			$('#wsrubismtpoptionsform select[name=settings_smtp_type_auth]').val(mainProfile.settings_smtp_type_auth);
			$('#wsrubismtpoptionsform input[name=settings_smtp_host]').val(mainProfile.settings_smtp_host);
			$('#wsrubismtpoptionsform input[name=settings_smtp_port]').val(mainProfile.settings_smtp_port);
			$('#wsrubismtpoptionsform select[name=settings_smtp_type_encryption]').val(mainProfile.settings_smtp_type_encryption);
		}
	});
});