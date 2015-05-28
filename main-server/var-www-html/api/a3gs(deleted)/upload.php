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
	header("Content-Type: text/plain");




	// Connect MySQL
	// User must be limited querying (only INSERT)
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// a3gs unique data language parser
	$data_ALLRAW = file_get_contents("php://input");
	$data_PART = explode("[", $data_ALLRAW); //[
	$data_NUM = count($data_PART);
	for ($i=1; $i<$data_NUM; $i++) {
		$data_part_split = explode("]", $data_PART[$i]); //]
		$mydata["$data_part_split[0]"] = $data_part_split[1];
	}


	// Authentication with accesskey
	$esc_ACCESSKEY = $mydata['ACCESSKEY']; // *need escape var
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
	if (!isset($mydata['LOCATION_UNAV'])) { $mydata['LOCATION_UNAV'] = "false"; } // flag NULL -> AVAILABLE
	// #2 Location set
	if ($mydata['LOCATION_UNAV'] == "true") { // UNAVAILABLE
		$mydata['LOCATION'] = "0 0";
	} else { // AVAILABLE
		if (!isset($mydata['LOCATION'])) { // If LOCATION is NULL:
			if ($devq_field[6] == 1) {
				$mydata['LOCATION'] = $posq_field[0]." ".$posq_field[1]; // If device is fixed, copy the position
			} else {
				$mydata['LOCATION'] = "0 0"; // not fixed but no location data from device, set pos=0,0 (default value)
			}
		}
	}


	// Save content, b64decode, and return the saved filename
	if (isset($mydata['CONTENT']) and $devq_field[15] == 0) {
		// mkdir a day subfolder
		$todaydirectory = "../../contents/".date('Y-m-d');
		if (file_exists($todaydirectory) == FALSE) {
			mkdir($todaydirectory, 0775, true);
		}
		
		
		// get filetype
		if (!isset($mydata['MIMETYPE'])) { $mydata['MIMETYPE'] = "www/unknown"; }
		$esc_MTYP = $mydata['MIMETYPE'];
		$mtquery = mysqli_query($db, "SELECT extension FROM system_content_type WHERE type = '$esc_MTYP' ");
		$mtq_field = mysqli_fetch_row($mtquery);
		// make filename
		$contentf = date('Y-m-d')."/".$esc_DEVID."_a3gs_".date('His')."_".rand(100,999).".".$mtq_field[0];
		// save file
		$fbuf = base64_decode($mydata["CONTENT"]);
		file_put_contents("../../contents/".$contentf, $fbuf);
		file_put_contents("../../contents/".date('Y-m-d')."/latest-dat.txt", $data_ALLRAW); // for debug
		
		
	} else {
		$contentf = "DEVICE_DISABLE";
		if ($devq_field[15] == 0) { $contentf = "NO_CONTENT"; }
	}


	// Escape vars
	if (!isset($mydata['DATE'])) { $mydata['DATE'] = 'NULL'; }
	$esc_DATE = $mydata['DATE']; 
	if (!isset($mydata['TIME'])) { $mydata['TIME'] = 'NULL'; }
	$esc_TIME = $mydata['TIME'];
	if (!isset($mydata['TIMEZONE'])) { $mydata['TIMEZONE'] = 'NULL'; }
	$esc_TZON = $mydata['TIMEZONE'];
	$esc_LOCA = $mydata['LOCATION'];
	$esc_LUNA = $mydata['LOCATION_UNAV'];
	if (!isset($mydata['ALTITUDE'])) { $mydata['ALTITUDE'] = 'NULL'; }
	$esc_ALTI = $mydata['ALTITUDE'];
	if (!isset($mydata['G_HEIGHT'])) { $mydata['G_HEIGHT'] = 'NULL'; }
	$esc_GHGT = $mydata['G_HEIGHT'];
	$esc_PLCE = $mydata['PLACE'];
	$esc_CNTN = $contentf;
	$esc_MTYP = $mydata['MIMETYPE'];
	$esc_DATA = $mydata['DATA'];
	$esc_TAG  = $mydata['TAG'];
	$esc_TAXN = $mydata['TAXONOMY'];
	$esc_COMM = $mydata['COMMENT'];
	$esc_HIDE = 0;
	if ($mydata['HIDDEN'] == 'true') { $esc_HIDE = 1; }
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
	print_r(error_get_last());
	echo "[EOF]";


	mysqli_close($dblink);
	exit;
?>
<!-- EOF -->