navigator.geolocation.getCurrentPosition(okCB, erCB);


function okCB(position) {
	res = position.coords.longitude + " " + position.coords.latitude + "/" + position.coords.altitude + "/";
	document.forms.webui.elements.LOCATION.value = position.coords.longitude + " " + position.coords.latitude;
	document.forms.webui.elements.ALTITUDE.value = position.coords.altitude;
}


function erCB(error) {
	var msg = "";
	switch(error.code) {
		case 1:
			msg = "Error: Not permitted.";
			break;
		case 2:
			msg = "Error: Unknown location.";
			break;
		case 3:
			msg = "Error: Timed out.";
			break;
	}
	res = "//" + msg ;
	document.forms.webui.elements.LOCATION.placeholder = msg;
}




