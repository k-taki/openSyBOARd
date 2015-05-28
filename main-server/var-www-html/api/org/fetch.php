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


	// Escape vars
	$esc_DEVID = $devq_field[0];
	// INSERT data to MySQL
	$Q_STRING = "SELECT fetch_string FROM device_config WHERE device_id = '$esc_DEVID' ";
	$datquery = mysqli_query($db, $Q_STRING);
	$qconf = mysqli_fetch_row($datquery);
	if (!$datquery) { echo "Config read error!\r"; }


	// Output responce as plaintext
	echo $qconf[0]."\r";
	echo "[EOF]";
	//print_r(error_get_last());


	mysqli_close($dblink);
	exit;
?>
<!-- EOF -->