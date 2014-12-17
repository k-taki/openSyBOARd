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
	// Responce as textfile
	header("Content-Type: text/plain");




	// Connect MySQL
	// User must be limited querying (only INSERT)
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// original data language parser
	//$data_ALLRAW = file_get_contents("php://input");
	//$data_PART = explode("[", $data_ALLRAW); //[
	//$data_NUM = count($data_PART);
	//for ($i=1; $i<$data_NUM; $i++) {
	//	$data_part_split = explode("]", $data_PART[$i]); //]
	//	$_POST["$data_part_split[0]"] = $data_part_split[1];
	//}


	// Authentication with accesskey
	$esc_ACCESSKEY = $_POST['ACCESSKEY']; // *need escape var
	$query = mysqli_query($db, "SELECT * FROM device WHERE accesskey = '$esc_ACCESSKEY' ");
	$devq_field = mysqli_fetch_row($query);
	// If accesskey mismatch
	if (!isset($devq_field[2])) {
		echo "Authentication failure!\r";
		echo "Please check your ACCESSKEY.\r";
		echo "[EOF]";
		exit;
	}
	// Auth OK:
	echo "Authentication completed successfully,\r";
	echo "Device name is ".$devq_field[2].".\r";


	// Get device's default location (position)
	$esc_DEVID = $devq_field[0];
	$posquery = mysqli_query($db, "SELECT X(location), Y(location) FROM device WHERE device_id = '$esc_DEVID' ");
	$posq_field = mysqli_fetch_row($posquery);


	// Check available or not Location data
	// #1 Location unavailable flag set
	if (!isset($_POST['LOCATION_UNAV'])) { $_POST['LOCATION_UNAV'] = "false"; } // flag NULL -> AVAILABLE
	// #2 Location set
	if ($_POST['LOCATION_UNAV'] == "true") { // UNAVAILABLE
		$_POST['LOCATION'] = "0 0";
	} else { // AVAILABLE
		if (!isset($_POST['LOCATION'])) { // If LOCATION is NULL:
			if ($devq_field[6] == 1) {
				$_POST['LOCATION'] = $posq_field[0]." ".$posq_field[1]; // If device is fixed, copy the position
			} else {
				$_POST['LOCATION'] = "0 0"; // not fixed but no location data from device, set pos=0,0 (default value)
			}
		}
	}


	// Save content, and return the saved filename
	// Check content and available flag

	if (!isset($_POST['MIMETYPE'])) { $_POST['MIMETYPE'] = "www/unknown"; }
	if ((is_uploaded_file($_FILES["CONTENT"]["tmp_name"])) and $devq_field[15] == 0) {
		// mkdir a day subfolder
		$todaydirectory = "../../contents/".date('Y-m-d');
		if (file_exists($todaydirectory) == FALSE) {
			mkdir($todaydirectory, 0775, true);
		}
		
		
		// get filetype
		$esc_MTYP = $_POST['MIMETYPE'];
		$mtquery = mysqli_query($db, "SELECT extension FROM system_content_type WHERE type = '$esc_MTYP' ");
		$mtq_field = mysqli_fetch_row($mtquery);
		// make filename
		$esc_WHOSE = $devq_field[1];
		$userquery = mysqli_query($db, "SELECT username FROM user WHERE user_id = '$esc_WHOSE' ");
		$userq_field = mysqli_fetch_row($userquery);
		$contentf = date('Y-m-d')."/".$esc_DEVID."-".date('His')."-".$userq_field[0]."_".rand(100,999).".".$mtq_field[0]; // <- extension here!
		// save file
		if (move_uploaded_file($_FILES["CONTENT"]["tmp_name"], "../../contents/".$contentf)) {
    		chmod("../../contents/".$contentf, 0655);
    	}
		//$fbuf = base64_decode($_POST["CONTENT"]);
		//file_put_contents("../../contents/".$contentf, $fbuf);
		
		
	} else {
		$contentf = "DEVICE_DISABLE";
		$_POST['MIMETYPE'] = "";
		if ($devq_field[15] == 0) { $contentf = "NO_CONTENT"; }
	}


	// Local date-time
	if (!isset($_POST['TIMEZONE'])) { $_POST['TIMEZONE'] = $devq_field[4]; }
	$fixhours = (string) $_POST['TIMEZONE'];
	$fixhours = $fixhours." hour";
	if (!isset($_POST['DATE'])) { $_POST['DATE'] = date('Y-m-d' , strtotime($fixhours)); };
	if (!isset($_POST['TIME'])) { $_POST['TIME'] = date('H:i:s' , strtotime($fixhours)); };


	// Escape vars
	$esc_DATE = $_POST['DATE']; 
	$esc_TIME = $_POST['TIME'];
	$esc_TZON = $_POST['TIMEZONE'];
	$esc_LOCA = $_POST['LOCATION'];      //p.d.
	$esc_LUNA = $_POST['LOCATION_UNAV']; //p.d.
	if (!isset($devq_field[7])) { $devq_field[7] = 'NULL'; } //default-altitude
	if (!isset($_POST['ALTITUDE'])) { $_POST['ALTITUDE'] = $devq_field[7]; } // if posted
	$esc_ALTI = $_POST['ALTITUDE'];
	if (!isset($devq_field[8])) { $devq_field[8] = 'NULL'; } //default-groundheight
	if (!isset($_POST['G_HEIGHT'])) { $_POST['G_HEIGHT'] = $devq_field[8]; } //if posted
	$esc_GHGT = $_POST['G_HEIGHT'];
	if (!isset($devq_field[9])) { $devq_field[9] = 'NULL'; } //default-place
	if (!isset($_POST['PLACE'])) { $_POST['PLACE'] = $devq_field[9]; } //if posted
	$esc_PLCE = $_POST['PLACE'];
	$esc_CNTN = $contentf; //p.d.
	$esc_MTYP = $_POST['MIMETYPE']; //p.d.
	$esc_DATA = $_POST['DATA'];
	$esc_TAG  = $_POST['TAG'];
	$esc_TAXN = $_POST['TAXONOMY'];
	$esc_COMM = $_POST['COMMENT'];
	$esc_HIDE = 0;
	if ($_POST['HIDDEN'] == 'true') { $esc_HIDE = 1; }
	// INSERT data to MySQL
	$Q_STRING = "INSERT INTO data VALUES ('',$esc_DEVID,'$esc_DATE','$esc_TIME',NULL,$esc_TZON,GeomFromText('POINT($esc_LOCA)'),$esc_LUNA,$esc_ALTI,$esc_GHGT,'$esc_PLCE','$esc_CNTN','$esc_MTYP','$esc_DATA','$esc_TAG','$esc_TAXN','$esc_COMM',$esc_HIDE)";
	echo "STRING: { ".$Q_STRING." }\r";


	// Check device is disabled or not
	if ($devq_field[15] == 0) {
		$datquery = mysqli_query($db, $Q_STRING);
		$result = "Unknown Error";
		if ($datquery) { $result = "Succeeded"; }
	} else {
		$result = "The device is disabled";
	}


	// Output responce as plaintext
	echo "QUERY: [".$result."]\r";
	//print_r(error_get_last());
	echo "[EOF]";


	mysqli_close($dblink);
	exit;
?>
<!-- EOF -->