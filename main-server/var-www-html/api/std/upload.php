<?php
	// error?
	error_reporting(E_ERROR);
	ini_set( 'display_errors', 1 );
?>

<?php
	
	// First, Request by GET METHOD (from browser) will be forbidden
	if ($_SERVER["REQUEST_METHOD"] != "POST") {
		session_start();
		session_regenerate_id(TRUE);
		// Login status check:
		if (!isset($_SESSION['USER'])) {
			header("Location: ../../logout.php");
			exit;
		}
		// and Jump sorry page!
		$_SESSION['ERROR'] = "Permitted POST method only at the API.";
		header("Location: ../../sorry.php");
		exit;
	}
	// in this php page, SESSION function is not used
	// by POST METHOD, running below:
	header('Access-Control-Allow-Origin:*');
	// Responce as json
	header("Content-Type: application/json");




	// Connect MySQL
	// User must be limited querying (only INSERT)
	$db = new mysqli('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error()); exit();
	}
	$db->set_charset("utf8");


	// Authentication with accesskey
	$ACCESSKEY = $_POST['ACCESSKEY']; // *need escape var
	$_device = $db->query("SELECT * FROM device WHERE accesskey = '$ACCESSKEY' ")->fetch_assoc();
	// If accesskey mismatch
	if (!isset($_device['accesskey'])) {
		$status = array('retCode'=> -1, 'retMessage'=>"Invalid ACCESSKEY.");
		echo json_encode($status);
		exit;
	}


	// Get device's default location (position)
	$DEVICE_ID = $_device['device_id'];
	$_geo = $db->query("SELECT X(location), Y(location) FROM device WHERE device_id = '".$_device['device_id']."' ")->fetch_row();


	// Check available or not Location data
	// #1 Location unavailable flag set
	if (!isset($_POST['LOCATION_UNAV'])) { $_POST['LOCATION_UNAV'] = 0; } // flag NULL -> AVAILABLE
	// #2 Location set
	if ($_POST['LOCATION_UNAV'] == 1) { // UNAVAILABLE
		$_POST['LOCATION'] = "0 0";
	} else { // AVAILABLE
		if (!isset($_POST['LOCATION']) or $_POST['LOCATION'] == "") { // If LOCATION is NULL:
			if ($_device['is_fixed'] == 1) {
				$_POST['LOCATION'] = $_geo[0]." ".$_geo[1]; // If device is fixed, copy the position
			} else {
				$_POST['LOCATION'] = "0 0"; // not fixed but no location data from device, set pos=0,0 (default value)
			}
		}
	}


	// Save content, and return the saved filename
	// Check content and available flag

	$finfo = new finfo(FILEINFO_MIME);
	if ((is_uploaded_file($_FILES["FILE"]["tmp_name"])) and $_device['is_disabled'] == 0) {
		if (!isset($_POST['MIMETYPE'])) {
			$mime = explode(";", $finfo->file($_FILES["FILE"]["tmp_name"]));
			$_POST['MIMETYPE'] = $mime[0];
		}
		// mkdir a day subfolder
		$todaydirectory = "../../contents/".date('Y-m-d');
		if (file_exists($todaydirectory) == FALSE) {
			mkdir($todaydirectory, 0775, true);
		}
		// get filetype
		$_system_content_type = $db->query("SELECT extension FROM system_content_type WHERE type = '".$_POST['MIMETYPE']."' ")->fetch_row();
		if (is_null($_system_content_type)) { $_system_content_type[0] = "dat"; $_POST['MIMETYPE'] = "www/unknown"; }
		// make filename
		$_user = $db->query("SELECT username FROM user WHERE user_id = '".$_device['user_id']."' ")->fetch_row();
		$contentfile = date('Y-m-d')."/".$_device['device_id']."-".date('His')."-".$_user[0]."_".rand(100,999).".".$_system_content_type[0]; // <- extension here!
		// save file
		if (move_uploaded_file($_FILES["FILE"]["tmp_name"], "../../contents/".$contentfile)) {
			chmod("../../contents/".$contentfile, 0644);
		}
	} else {
		$contentfile = "DEVICE_DISABLE";
		$_POST['MIMETYPE'] = "";
		if ($_device['is_disabled'] == 0) { $contentfile = "NO_CONTENT"; }
	}


	// Local date-time set from UTC
	if (!isset($_POST['TIMEZONE'])) { $_POST['TIMEZONE'] = $_device['timezone']; }
	$fixhours = (string) $_POST['TIMEZONE'];
	$fixhours = $fixhours." hour";
	if (!isset($_POST['DATE'])) { $_POST['DATE'] = date('Y-m-d' , strtotime($fixhours)); };
	if (!isset($_POST['TIME'])) { $_POST['TIME'] = date('H:i:s' , strtotime($fixhours)); };


	// Escape vars
	$DATE = $_POST['DATE']; 
	$TIME = $_POST['TIME'];
	$TIMEZONE = $_POST['TIMEZONE'];
	$LOCATION = $_POST['LOCATION']; //p.d.
	$LOCATION_UNAV = (int)$_POST['LOCATION_UNAV']; //p.d.
		if (!isset($_device['altitude'])) { $_device['altitude'] = 'NULL'; } //default-altitude
		if (!isset($_POST['ALTITUDE']) or $_POST['ALTITUDE'] == "") { $_POST['ALTITUDE'] = $_device['altitude']; } // if not posted
	$ALTITUDE = $_POST['ALTITUDE'];
		if (!isset($_device['attached_height'])) { $_device['attached_height'] = 'NULL'; } //default-attached_height
		if (!isset($_POST['G_HEIGHT'])) { $_POST['G_HEIGHT'] = $_device['attached_height']; } //if not posted
	$G_HEIGHT = $_POST['G_HEIGHT'];
		if (!isset($_device['place'])) { $_device['place'] = 'NULL'; } //default-place
		if (!isset($_POST['PLACE'])) { $_POST['PLACE'] = $_device['place']; } //if not posted
		if ($_POST['PLACE_AUTO'] == 1 and $LOCATION_UNAV == 0 and $LOCATION != "0 0") {
			$ch = curl_init();
			$latlng = explode(" ", $LOCATION);
			$uri = "http://maps.google.com/maps/api/geocode/json?sensor=false&latlng=".$latlng[1].",".$latlng[0];
			curl_setopt($ch, CURLOPT_URL, $uri);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$rescurl = curl_exec($ch);
			curl_close($ch);
			$autoplace = JSON_decode($rescurl, true);
			$_POST['PLACE'] = $autoplace['results'][0]['formatted_address'];
		}
	$PLACE = $_POST['PLACE'];
	$CONTENTFILE = $contentfile; //p.d.
	$MIMETYPE = $_POST['MIMETYPE']; //p.d.
	$DATA = $_POST['DATA'];
	$TAG  = $_POST['TAG'];
	$TAXONOMY = $_POST['TAXONOMY'];
	$COMMENT = $_POST['COMMENT'];
		if (!isset($_POST['HIDDEN'])) { $_POST['HIDDEN'] = $_device['is_hidden']; } // if not posted
	$IS_HIDDEN = (int)$_POST['HIDDEN'] ;
	$FROM_WEB = 0; if ($_POST['FROM_WEB'] == 1) { $FROM_WEB = 1; }
	// INSERT data to MySQL
	$Q_STRING = "INSERT INTO data VALUES ('',$DEVICE_ID,'$DATE','$TIME',NULL,$TIMEZONE,GeomFromText('POINT($LOCATION)'),$LOCATION_UNAV,$ALTITUDE,$G_HEIGHT,'$PLACE','$CONTENTFILE','$MIMETYPE','$DATA','$TAG','$TAXONOMY','$COMMENT',$IS_HIDDEN,$FROM_WEB)";
	$R_STRING = array('date'=>$DATE, 'time'=>$TIME, 'timezone'=>(int)$TIMEZONE, 'longitude'=>(float)$_geo[0], 'latitude'=>(float)$_geo[1], 'location_unav'=>(bool)$LOCATION_UNAV, 'altitude'=>(float)$ALTITUDE, 'g_height'=>(int)$G_HEIGHT, 'place'=>$PLACE, 'file'=>$CONTENTFILE, 'data'=>$DATA, 'tag'=>$TAG, 'taxonomy'=>$TAXONOMY, 'comment'=>$COMMENT, 'is_hidden'=>(bool)$IS_HIDDEN);


	// Check device is disabled or not
	if ($_device['is_disabled'] == 0) {
		if ($db->query($Q_STRING)) { $status = array('retCode'=> 0, 'retMessage'=>"Success.", 'devicename'=>$_device['devicename']); }
		else { $status = array('retCode'=> -1, 'retMessage'=>"DATABASE Error - ".$db->error, 'query'=>$Q_STRING); }
	} else {
		$status = array('retCode'=> -1, 'retMessage'=>"Device is set as disabled.", 'devicename'=>$_device['devicename']);
	}
	$result = array_merge($status, $R_STRING);
	$db->close();


	// Output responce as plaintext
	echo json_encode($result);


	// Jump to device page from_web
	if ($_POST['FROM_WEB'] == true) { header("Location: ../../dev.php?id=".$DEVICE_ID); }


	exit;
?>
<!-- EOF -->