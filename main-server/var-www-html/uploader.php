<?php
	// First, Request by GET METHOD (from browser) will be forbidden
	if ($_SERVER["REQUEST_METHOD"] != "GET") {
		session_start();
		session_regenerate_id(TRUE);
		// Login status check:
		if (!isset($_SESSION['USER'])) {
			header("Location: logout.php");
			exit;
		}
		// and Jump sorry page!
		header("Location: ../sorry.php");
	}
	// in this php page, SESSION function is not used
	// by POST METHOD, running below:




	// Connect MySQL
	// User must be limited querying (only INSERT)
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// Get ACCESSKEY and identify the device
	$esc_ACCESSKEY = $_POST['ACCESSKEY']; // *need escape var
	$query = mysqli_query($db, "SELECT * FROM device WHERE accesskey = '$esc_ACCESSKEY' ");
	$devq_field = mysqli_fetch_row($query);
	$deviceid = $devq_field[0];
	
	$_SESSION['USER_ID'] = $q_field[1];
	$query = mysqli_query($db, "SELECT name FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['GROUP'] = $q_field[0];


	// Get user level (is admin or not)
	// 0 = only "guest"
	// 1 = public user (authorized)
	// 2 = admin like user
	// 3 = only "admin"
	$query = mysqli_query($db, "SELECT admin_level FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['ADMIN_LEVEL'] = $q_field[0];
	// Get user grants (enable view hidden contents or not)
	$query = mysqli_query($db, "SELECT view_hidden FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['VIEW_HIDDEN'] = $q_field[0];


	// Get the number of devices, and listing up
	if ($_SESSION['VIEW_HIDDEN'] == 1) {
		$devicequery = mysqli_query($db, "SELECT * FROM device");
		$row_cnt = mysqli_num_rows($devicequery);
	} else {
		$devicequery = mysqli_query($db, "SELECT * FROM device WHERE is_hidden = '0' ");
		$row_cnt = mysqli_num_rows($devicequery);
	}


	// Device info page redirect (session var?)
	if (isset($_SESSION['DEVINFO_ID'])) {
		$esc_DID = $_SESSION['DEVINFO_ID'];
		unset($_SESSION['DEVINFO_ID']);
		header("Location: dev.php?id=".$esc_DID);
		exit;
	}






	mysqli_close($dblink);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<meta charset="UTF-8">
<meta name="author" content="">
<meta name="keywords" content="">
<meta name="description" content="">
<link rel="stylesheet" href="./css/headfoot.css">
<link rel="stylesheet" href="./css/main.css">
<title>SyBOAR Mainpage / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>







<?php
	$tempf = "./photo/latest.jpg";
	
	//----- timestamp sliding
	$fmt = filemtime("./photo/l-4.jpg"); copy("./photo/l-4.jpg", "./photo/l-5.jpg"); touch("./photo/l-5.jpg", $fmt);
	$fmt = filemtime("./photo/l-3.jpg"); copy("./photo/l-3.jpg", "./photo/l-4.jpg"); touch("./photo/l-4.jpg", $fmt);
	$fmt = filemtime("./photo/l-2.jpg"); copy("./photo/l-2.jpg", "./photo/l-3.jpg"); touch("./photo/l-3.jpg", $fmt);
	$fmt = filemtime("./photo/l-1.jpg"); copy("./photo/l-1.jpg", "./photo/l-2.jpg"); touch("./photo/l-2.jpg", $fmt);
	$fmt = filemtime($tempf); copy($tempf, "./photo/l-1.jpg"); touch("./photo/l-1.jpg", $fmt);
	
	
	
	//----- save in var as raw streaming data
	$allrawdat = file_get_contents("php://input");
	$rawf = "./rawdata.txt";
	file_put_contents($rawf, $allrawdat);

	/*//----- temp, hum, bright
	$otok = strtok($allrawdat, ';');
	$mdata = "";
	while ($otok !== false) {
		$mdata = $mdata + "$otok<br />";
		$otok = strtok(';');
	}
	file_put_contents("./tokendata.txt", $mdata);
	*/
	
	//----- photo streaming data
	$b64photo1 = strstr($allrawdat, "PHOTOSTREAM=");
	$b64photo2 = substr($b64photo1, 12);
	$b64photo3 = rtrim($b64photo2, ";");
	$lastphoto = base64_decode($b64photo3);
	file_put_contents($tempf, $lastphoto);







	
	//----- check and if none then make daily directory
	$todaypathr = "./datlog/".date('Y-m-d');
	if (file_exists($todaypathr) == FALSE) {
		mkdir($todaypathr, 0775, true);
	}
	
	//----- logging raw file to timely
	$slogf = $todaypathr."/3GupdLOG".date('YmdHis').".txt";
	copy($rawf,$slogf);

	//----- directory for photo
	$todaypathp = "./photo/".date('Y-m-d');
	if (file_exists($todaypathp) == FALSE) {
		mkdir($todaypathp, 0775, true);
	}

	//----- copy temp file to day-named file
	$setf = $todaypathp."/3Guploaded".date('YmdHis').".jpg";
	copy($tempf, $setf);
	echo 'Data recieved successfully.<br>';
?>
<br>





</body>
</html>
<!-- EOF -->