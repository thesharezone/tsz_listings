function isEmail(email) {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}

function cryptEmail(event, fieldName, rsa_n, rsa_e) {
	if(isEmail(jQuery("#" + fieldName).val())) {
	    var rsa = new RSAKey();
		rsa.setPublic(rsa_n, rsa_e)

		// encrypt using RSA
		var data = rsa.encrypt(jQuery("#" + fieldName).val());
		if(data)
			jQuery("#" + fieldName).val(data);
		else
		 	event.preventDefault();

		return data;
	}

	return false;



}