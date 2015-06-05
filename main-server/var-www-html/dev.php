<?php
	// error?
	error_reporting(E_ERROR);
	ini_set( 'display_errors', 1 );
?>

<?php
	session_start();
	session_regenerate_id(TRUE);

	// Device selected by url?
	// e.g. "/deviceinfo.php?id=4"
	if (!isset($_GET['id'])) {
		header("Location: ./");
		exit;
	}


	// Login status check:
	if (!isset($_SESSION['USER'])) {
		// ?id=x -> SESSION var and jump login page
		$_SESSION['DEVINFO_ID'] = $_GET['id'];
		header("Location: login.php");
		exit;
	}


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// Init basic vars
	$esc_USERID = $_SESSION['USER_ID'];
	$esc_USERNAME = $_SESSION['USER'];
	$esc_DEVID = $_GET['id'];


	// Regenerate:ACCESSKEY
	if ($_POST['regenerate'] == "do!" and $_POST['devuserid'] == $esc_USERID) {
		$esc_regenSTR = "".$esc_USERNAME.$_POST['devownname'].openssl_random_pseudo_bytes(rand(2,10));
		$esc_regenACK = hash('sha1', $esc_regenSTR);
		$Q_STRING = "UPDATE device SET accesskey = '$esc_regenACK' WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
		unset($_POST['regenerate']);
	}


	// Change:Status
	if (isset($_POST['isdisabled']) and $_POST['devuserid'] == $esc_USERID) {
		$esc_ISD = $_POST['isdisabled'];
		$Q_STRING = "UPDATE device SET is_disabled = '$esc_ISD' WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
		unset($_POST['isdisabled']);
	}


	// Change:FetchString
	if (isset($_POST['fstr']) and $_POST['devuserid'] == $esc_USERID) {
		$esc_FSTR = $_POST['fstr'];
		$Q_STRING = "UPDATE device_fetch SET fetch_string = '$esc_FSTR' WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
		unset($_POST['fstr']);
	}


	// Change:General
	if (isset($_POST['conf_dnam']) and $_POST['devuserid'] == $esc_USERID) {
		$esc_DNAM = $_POST['conf_dnam'];
		$esc_GLON = $_POST['conf_lon']; $esc_GLAT = $_POST['conf_lat'];
		$esc_LOCA = $esc_GLON." ".$esc_GLAT ;
		$esc_FIXD = 0; if (isset($_POST['conf_fixed'])) { $esc_FIXD = $_POST['conf_fixed'] ; }
		$esc_PLCE = $_POST['conf_plc'];
		$esc_TZON = $_POST['conf_tz'];
		$esc_ALTI = $_POST['conf_alt'];
		$esc_ATHI = $_POST['conf_ath'];
		$esc_USER = 0; if (isset($_POST['conf_isuser'])) { $esc_USER = $_POST['conf_isuser'] ; }
		$esc_HIDE = $_POST['conf_ishidden'];
		$esc_HIDE = 0; if (isset($_POST['conf_ishidden'])) { $esc_HIDE = $_POST['conf_ishidden'] ; }
		$Q_STRING = "UPDATE device SET devicename = '$esc_DNAM', timezone = $esc_TZON, location = GeomFromText('POINT($esc_LOCA)'), is_fixed = $esc_FIXD, altitude = $esc_ALTI, attached_height = $esc_ATHI, place = '$esc_PLCE', is_user = $esc_USER, is_hidden = $esc_HIDE WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
	}


	// Change:Image
	if (is_uploaded_file($_FILES['conf_devimg']['tmp_name']) and $_POST['devuserid'] == $esc_USERID) {
		$imgname = hash('md5', $_POST['devownname']."+".$esc_USERNAME);
		if (move_uploaded_file($_FILES['conf_devimg']['tmp_name'], "./device_images/".$imgname.".jpg")) {
    		chmod("./device_images/".$imgname, 0755);
    	}
		$Q_STRING = "UPDATE device SET device_imgfilename = '$imgname' WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
		unset($_FILES['conf_devimg']);
	}


	// Change:Comment
	if (isset($_POST['conf_comm']) and $_POST['devuserid'] == $esc_USERID) {
		$esc_COMM = $_POST['conf_comm'];
		$Q_STRING = "UPDATE device SET comment = '$esc_COMM' WHERE device_id = $esc_DEVID";
		$chk = mysqli_query($db, $Q_STRING);
		unset($_POST['conf_comm']);
	}


	// Delete
	if (isset($_POST['conf_delpass']) and $_POST['conf_delconf'] == 1 and $_POST['devuserid'] == $esc_USERID) {
		$inpasshash = hash('sha256', $_POST['conf_delpass']);
		$ph = mysqli_query($db, "SELECT password FROM user WHERE user_id = $esc_USERID"); $qf = mysqli_fetch_row($ph);
		if ($inpasshash == $qf[0]) {
			$Q_STRING = "DELETE FROM device WHERE device_id = $esc_DEVID"; $chk = mysqli_query($db, $Q_STRING);
			$Q_STRING = "DELETE FROM device_fetch WHERE device_id = $esc_DEVID"; $chk2 = mysqli_query($db, $Q_STRING);
			if ($chk) { $_SESSION['ALERT'] = '<p id="alert">Device: 正常に削除されました。</p>'; header("Location: my.php"); } else { header("Location: dev.php?id=".$esc_DEVID."#del"); }
		} else {
			header("Location: dev.php?id=".$esc_DEVID."#del");
		}
		exit;
	}


	// Get about selected device
	$devicequery = mysqli_query($db, "SELECT * FROM device WHERE device_id = $esc_DEVID "); //get selected device's row
	$devq_field = mysqli_fetch_row($devicequery);
	if (!isset($devq_field[0])) { // if no device id on database
		$_SESSION['ERROR'] = "Device ID is not exist on this database.";
		header("Location: sorry.php");
		exit;
	}


	// Get device fetch string
	$devf = mysqli_query($db, "SELECT fetch_string FROM device_fetch WHERE device_id = $esc_DEVID ");
	$ftcq = mysqli_fetch_row($devf);


	// Select article color
	$articletype = "device_normal";
	if ($devq_field[12] == 1) { $articletype = "device_user"; } //is_user=1
	if ($devq_field[13] == 1) { $articletype = "device_hidden"; } //is_hidden=1


	// Get who is owner
	$esc_OWNID = $devq_field[1];
	$userquery = mysqli_query($db, "SELECT username FROM user WHERE user_id = $esc_OWNID ");
	$uq_field = mysqli_fetch_row($userquery);


	// Redirect general user if they access hidden device (except owner)
	if ($devq_field[13] == 1 and $_SESSION['VIEW_HIDDEN'] == 0 and $esc_OWNID != $esc_USERID) {
		$_SESSION['ERROR'] = "The request is referring hidden device.";
		header("Location: sorry.php");
		exit;
	}


	// Get device's location (position)
	$posquery = mysqli_query($db, "SELECT X(location), Y(location) FROM device WHERE device_id = $esc_DEVID ");
	$posq_field = mysqli_fetch_row($posquery);


	// Get data rows by the device from data table
	$dataquery = mysqli_query($db, "SELECT * FROM data WHERE device_id = $esc_DEVID ORDER BY timestamp DESC");
	$strquery = mysqli_query($db, "SELECT date, time, data FROM data WHERE device_id = $esc_DEVID ORDER BY timestamp DESC");
	$datrow_cnt = mysqli_num_rows($dataquery);
	// Get data location (position)
	$dpquery = mysqli_query($db, "SELECT X(location), Y(location) FROM data WHERE device_id = $esc_DEVID ORDER BY timestamp DESC");


	// Timezone list array
	$tzlist = array("+14:00"=>14, "+13:00"=>13, "+12:45"=>12.75, "+12:00"=>12, "+11:30"=>11.5, "+11:00"=>11, "+10:30"=>10.5, "+10:00"=>10, "+9:30"=>9.5, "+9:00"=>9, "+8:45"=>8.75, "+8:00"=>8, "+7:00"=>7, "+6:30"=>6.5, "+6:00"=>6, "+5:45"=>5.75, "+5:30"=>5.5, "+5:00"=>5, "+4:30"=>4.5, "+4:00"=>4, "+3:30"=>3.5, "+3:00"=>3, "+2:00"=>2, "+1:00"=>1, "±0"=>0, "-1:00"=>-1, "-2:00"=>-2, "-3:00"=>-3, "-3:30"=>-3.5, "-4:00"=>-4, "-4:30"=>-4.5, "-5:00"=>-5, "-6:00"=>-6, "-7:00"=>-7, "-8:00"=>-8, "-9:00"=>-9, "-9:30"=>-9.5, "-10:00"=>-10, "-11:00"=>-11, "-12:00"=>-12);


	// more device info..
	$e_ACK = $devq_field[3]; // ACCESSKEY
	$e_NAM = $devq_field[2]; // Device name
	$g_LON = $posq_field[0]; // Longitude
	$g_LAT = $posq_field[1]; // Latitude
	$e_PLC = $devq_field[9]; // Place
	$e_TZN = $devq_field[4]; // Timezone
	$e_FIX = $devq_field[6]; // is_fixed flag
	$e_ALT = $devq_field[7]; // Altitude
	$e_AHI = $devq_field[8]; // AttachedHeight
	$e_USR = $devq_field[12]; // is_user flag
	$e_HID = $devq_field[13]; // is_hidden flag
	$e_COM = $devq_field[10]; // Comment
	$e_IMG = "./device_images/".$devq_field[11]; // Device Image
	$e_FSTR = $ftcq[0]; // Fetch String



















?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>Device information of <?php echo $e_NAM; ?> / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<script type="text/javascript" src="./js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="./js/clock.js"></script>
<script type="text/javascript">var devid = <?php echo $esc_DEVID; ?>;</script>
<?php if ($e_USR == 1) { echo '<script type="text/javascript" src="./js/geo.js"></script>'; } ?>
<!-- X ---------------------------------------------------------------------- X -->











	<?php echo '<article id="device" class="' . $articletype . '">'; ?>
		<?php echo '<img src="' . $e_IMG . '" alt="Device image">'; ?>


		<!-- Device information table -->
		<table id="info">
			<tr><th>Device ID</th>
				<td><?php echo $devq_field[0]; ?></td></tr>
			<tr><th>Owner</th>
				<td><?php echo $uq_field[0]; ?></td></tr>
			<tr><th>Place</th>
				<td><?php echo $e_PLC; ?></td></tr>
			<tr><th>Location</th>
				<td>
				<?php if ($devq_field[6] == 0) : ?>
					<?php echo 'Indefinite'; ?>
				<?php else : ?>
					<?php echo '<a href="http://maps.google.com/maps?q='.$g_LAT.','.$g_LON.'" target="_blank">'.$g_LON.', '.$g_LAT.'</a>'; ?></td></tr>
				<?php endif; ?>
			<tr><th>Altitude</th>
				<td><?php echo $e_ALT; ?> m</td></tr>
			<tr><th>Attached Height</th>
				<td><?php echo $e_AHI; ?> mm</td></tr>
			<tr><th>Time Zone</th>
				<td>UTC<?php echo array_search($e_TZN, $tzlist); ?></td></tr>
			<tr><th>Status</th>
				<?php $enb = 'Error'; if ($devq_field[14] == 0) { $enb = 'Available'; }; if ($devq_field[14] == 1) { $enb = 'Unavailable'; }; ?>
				<td><?php echo $enb; ?></td></tr>
		</table>


		<!-- Device name -->
		<h2><?php echo "Device: ".$e_NAM; ?></h2>
		<!-- Device description -->
		<p><?php echo $e_COM; ?></p>


		<!-- Datalist -->
		<h2>Latest Data</h2>
		<table id="data">
<?php
			// Data field name
			if ($e_USR == 1) { echo '<tr><th>LOCALTIME</th><th>GEO INFO</th><th>FILE</th><th>COMMENT</th><th>TAGS</th></tr>'; }
			else { echo '<tr><th>LOCALTIME</th><th>GEO INFO</th><th>FILE</th><th>DATA STRING</th><th>TAGS</th></tr>'; }
			// Data body
			for ($i=1; $i<=$datrow_cnt; $i++) {
				// RENEW data
				$datq_field = mysqli_fetch_row($dataquery); // DATA-ENTRY
				$dpq_field = mysqli_fetch_row($dpquery); // PLACE
				// table row
				echo "<tr>";
					// timestamp
					echo "<td>".$datq_field[2]." ".$datq_field[3]."</td>";
					// geoinfo = location+place
					echo "<td>";
					if ($datq_field[7] == 1) {
						echo " - ";
					} else {
						if ($datq_field[10] == "") { echo '<a href="http://maps.google.com/maps?q='.$dpq_field[1].','.$dpq_field[0].'" target="_blank">[undef]</a>'; }
						else { echo '<a href="http://maps.google.com/maps?q='.$dpq_field[1].','.$dpq_field[0].'" target="_blank">'.$datq_field[10].'</a>'; }
					}
					echo "</td>";
					// contentfile
					if ($datq_field[11] == "NO_CONTENT") {
						echo "<td> - </td>";
					} else {
						echo '<td><a href="../../contents/'.$datq_field[11].'" target="_blank">'.$datq_field[12].'</a></td>';
					}
					// datastring or comment
					if ($e_USR == 1) { echo "<td>".$datq_field[16]."</td>"; }
					else { echo "<td>".$datq_field[13]."</td>"; }
					// tags
					echo "<td>".$datq_field[14]."</td>";
				echo "</tr>";
				if ($i >=5) { break; } // latest 5 entries
			}
?>
		</table>
		
		
		<!-- For Data String -->
		<section id="dstr"><table id="dstr">
<?php
			if (!isset($_GET['samples'])) { $graph_timeband = 100; } else { $graph_timeband = $_GET['samples']; }
			$dstr_KEYS = array("KEYS");
			for ($i=1; $i<=$datrow_cnt; $i++) {
				// RENEW data
				$strq_field = mysqli_fetch_row($strquery);
				$dstr_ALL = $strq_field[2];
				// Check style format
				switch ( substr($dstr_ALL, -1) ) {
					case ")":
					// data string parser (1)
					$dstr_PART = explode(")", $dstr_ALL); // (key-left split) ex: "KEY1(VAL1"+"KEY2(VAL2"+...
					$dstr_NUM = count($dstr_PART);
					for ($j=0; $j<($dstr_NUM-1); $j++) {
						$dstr_part_split = explode("(", $dstr_PART[$j]); // (key-right split) ex: "KEY(j)"+"VAL(j)"
						if (!in_array("$dstr_part_split[0]", $dstr_KEYS)) { $dstr_KEYS[] = $dstr_part_split[0]; } // Indexing Allkey
						$x = array_search("$dstr_part_split[0]", $dstr_KEYS);
						$mydstr[$i][$x] = $dstr_part_split[1]; // define key($x)=value
					} break;
					case ";":
					// data string parser (2)
					$dstr_PART = explode(";", $dstr_ALL); // (key-left split) ex: "KEY1(VAL1"+"KEY2(VAL2"+...
					$dstr_NUM = count($dstr_PART);
					for ($j=0; $j<($dstr_NUM-1); $j++) {
						$dstr_part_split = explode("=", $dstr_PART[$j]); // (key-right split) ex: "KEY(j)"+"VAL(j)"
						if (!in_array("$dstr_part_split[0]", $dstr_KEYS)) { $dstr_KEYS[] = $dstr_part_split[0]; } // Indexing Allkey
						$x = array_search("$dstr_part_split[0]", $dstr_KEYS);
						$mydstr[$i][$x] = $dstr_part_split[1]; // define key($x)=value
					} break;
					default:
					// no data string
					if (!in_array("NO DATA", $dstr_KEYS)) { $dstr_KEYS[] = "NO DATA"; } // Indexing Allkey
					$x = array_search("NO DATA", $dstr_KEYS);
					$mydstr[$i][$x] = ""; // define key($x)=value
					break;
				}
				// Indexing DateTime
				$dstr_DT[$i]["date"] = $strq_field[0];
				$dstr_DT[$i]["time"] = $strq_field[1];
				if ($i >=$graph_timeband) { break; } // latest N entries
			}
			
			// echo table header
			$allkeynum = count($dstr_KEYS);
			echo "<tr><th>LOCALTIME</th>";
			for ($k=1; $k<$allkeynum; $k++) { echo "<th>".$dstr_KEYS[$k]."</th>"; }
			echo "</tr>";
			
			// echo strings
			for ($l=1; $l<=$datrow_cnt; $l++) {
				// table row
				echo "<tr>";
					// timestamp
					echo "<td>".$dstr_DT[$l]["date"]." ".$dstr_DT[$l]["time"]."</td>";
					// datastring
					for ($m=1; $m<$allkeynum; $m++) { echo "<td>".$mydstr[$l][$m]."</td>"; }
				echo "</tr>";
				if ($l >=$graph_timeband) { break; } // latest N entries
			}
			
			// send vars and json strings to use javascript
			$j_keys = json_encode($dstr_KEYS);
			$j_datetime = json_encode(array_reverse($dstr_DT));
			$j_mydstr = json_encode(array_reverse($mydstr));
			if (!isset($_GET['f'])) { $serF = 1; } else { $serF = $_GET['f']; }
			if (!isset($_GET['g'])) { $serG = 2; } else { $serG = $_GET['g']; }
?>
		</table></section>










		<!-- Graph -->
		<h2>Graph View</h2><a name="graphview"></a>
		<form action="<?php echo $_SERVER['PHP_SELF'].'#graphview'; ?>" method="GET">
			<input type="hidden" name="id" value=<?php echo '"'.$esc_DEVID.'"'; ?>>
			Line1:<select name="f"><?php for ($k=1; $k<$allkeynum; $k++) { echo '<option value="'.$k.'" '; if($k==$serF){echo 'selected="selected"';} echo '>'.$dstr_KEYS[$k].'</option>'; } ?></select>
			Line2:<select name="g"><?php for ($k=1; $k<$allkeynum; $k++) { echo '<option value="'.$k.'" '; if($k==$serG){echo 'selected="selected"';} echo '>'.$dstr_KEYS[$k].'</option>'; } ?></select>
			 | <select name="samples"><option value="50">50</option><option value="100" selected="selected">100</option><option value="150">150</option><option value="200">200</option><option value="250">250</option><option value="300">300</option></select>Samples
			<input type="submit" value="Update">
		</form>
		<section id="graph">
		<script type="text/javascript" src="./js/flotr2.min.js"></script>
		<script type="text/javascript">
		
		<?php echo "var mykeys = JSON.parse('$j_keys');" ; ?>
		<?php echo "var datetime = JSON.parse('$j_datetime');" ; ?>
		<?php echo "var mydata = JSON.parse('$j_mydstr');" ; ?>
		<?php echo "var timeband = $graph_timeband;" ; ?>
		<?php echo "var seriesF = $serF;" ; ?>
		<?php echo "var seriesG = $serG;" ; ?>
		
	$(function advanced_titles(container){
		var
		d1 = [],
		d2 = [],
		ticks_X = [],
		i, graph, options;
		for (i=0; i<timeband; i++) {
			c_date = datetime[i]["date"].split("-");
			c_time = datetime[i]["time"].split(":");
			x = new Date(Number(c_date[0]), Number(c_date[1])-1, Number(c_date[2]), Number(c_time[0]), Number(c_time[1]), Number(c_time[2]));
			d1.push([x, parseFloat(mydata[i][seriesF])]);
			d2.push([x, parseFloat(mydata[i][seriesG])]);
			if (i == mydata.length-1)
				break;
		}
		options = {
			title: "",
			HtmlText: false,
			xaxis: { mode: 'time', timeMode: 'local', title: "Date/Time", labelsAngle : 30, autoscale: true },
			selection : { mode: 'x' },
			yaxis: { title: mykeys[seriesF], autoscale: true },
			y2axis: { title: mykeys[seriesG], autoscale: true },
			legend: { position: "nw" }
		};
		function drawGraph (opts) {
			var o = Flotr._.extend(Flotr._.clone(options), opts || {});
			return Flotr.draw($('#graph').get(0), [ {data: d1, label: mykeys[seriesF], yaxis: 1}, {data: d2, label: mykeys[seriesG], yaxis: 2} ], o);
		}
		graph = drawGraph();
		Flotr.EventAdapter.observe($('#graph').get(0), 'flotr:select', function (area) {
			graph = drawGraph({
			HtmlText: false,
			xaxis: { min:area.x1, max:area.x2, mode: 'time', timeMode: 'local', title: "Date/Time", labelsAngle : 30, autoscale: true },
			yaxis: { min:area.y1, max:area.y2, title: mykeys[seriesF], autoscale: true },
			y2axis: { min:null, max:null, title: mykeys[seriesG], autoscale: true }
			});
		});
		Flotr.EventAdapter.observe($('#graph').get(0), 'flotr:click', function () { graph = drawGraph(); });
	});
		
		</script>
		</section>
	</article>








	<!-- isUser Interface -->
<?php
		if ($esc_OWNID == $esc_USERID and $e_USR == 1) {
	echo '<article id="webui">';
		echo '<h2>User-mode Data Uploader</h2>';
		echo '<form action="./api/std/upload.php" method="POST" name="webui"><table>';
		//-HIDDENPARTS-
		echo '<input type="hidden" name="ACCESSKEY" value="'.$e_ACK.'">';
		echo '<input type="hidden" name="FROM_WEB" value="1">';
		//-LOCALTIME-
		echo '<tr><th>LOCAL TIME </th><td> <span id="c_daytime" style="float:none;margin:0px 2px;"></span> <span id="c_clock" style="float:none;margin:0px;"></span></td></tr>';
		//-AUTO LOCATION-
		echo '<script type="text/javascript" src="./js/geo.js"></script>';
		echo '<tr><th>AUTO LOCATION </th><td> [Lon Lat], [Alt] = <input type="text" placeholder="" name="LOCATION" value="" style="width:300px;" maxlength="127" readonly="readonly">, <input type="text" placeholder="" name="ALTITUDE" value="" style="width:40px;" maxlength="63" readonly="readonly"></td></tr>';
		//-PLACE-
		echo '<tr><th>PLACE </th><td> <input type="text" placeholder="'.$e_PLC.'[undefined]" name="PLACE" value="'.$e_PLC.'" style="width:300px;" maxlength="127"> <input type="checkbox" name="PLACE_AUTO" value="1">Auto Detect</td></tr>';
		//-DATA-
		echo '<tr><th>DATA STRING </th><td> <input type="text" placeholder="key1=value1;key2=value2;key3=.." name="DATA" value="" style="width:400px;" maxlength="127"></td></tr>';
		//-FILE-
		echo '<tr><th>FILE </th><td> Upload: <input type="file" name="FILE"></td></tr>';
		//-COMMENT-
		echo '<tr><th>COMMENT </th><td> <input type="text" placeholder="" name="COMMENT" value="" style="width:400px;" maxlength="127"></td></tr>';
		//-TAG-
		echo '<script type="text/javascript">function testtag() { document.forms.webui.elements.TAG.value = "#testdata" }</script>';
		echo '<tr><th>TAG </th><td> <input type="text" placeholder="#~" name="TAG" value="" style="width:200px;" maxlength="127"> <input type="button" value="#testdata" onclick="testtag();"></td></tr>';
		//-Option-
		echo '<tr><th>Option Flags </th><td> ';
			echo '<input type="checkbox" name="LOCATION_UNAV" value="1">Location unavailable';
			echo '  <input type="checkbox" name="HIDDEN" value="1" '; if ($e_HID == 1) { echo 'checked="checked"'; } echo '>This data entry set as hidden';
		echo '</td></tr>';
		//-BUTTON-
		echo '</table><span class="config"><input type="submit" value="Upload Mydata!"><input type="reset" value="Reset"></span></form>';
	echo '</article>';
		}
?>











		<!-- Config -->
<?php
		if ($esc_OWNID == $esc_USERID) {
	echo '<article id="devconf">';
			echo '<h2>Device Config (Owner only)</h2>';
			
			// ACCESSKEY, Status & Fetch String
			echo '<h3>ACCESSKEY, Status & Fetch String </h3>';
			echo '<table class="config">';
			//-ACCKEY-
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID."#devconf".'" method="POST"><tr><th>ACCESSKEY </th><td> <input type="text" placeholder="accesskey" name="accesskey" value="'.$e_ACK.'" style="width:320px;" readonly="readonly"> <input type="submit" value="Regenerate ACCESSKEY"></td></tr><input type="hidden" name="regenerate" value="do!"><input type="hidden" name="devuserid" value="'.$esc_OWNID.'"><input type="hidden" name="devownname" value="'.$e_NAM.'"></form>';
			//-Status-
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID."#devconf".'" method="POST"><tr><th>Device Status </th><td> ';
			if ($devq_field[14] == 0) {
				echo '<b style="color:red;">Available</b> <input type="submit" value="Switch to Unavailable"><input type="hidden" name="isdisabled" value="1"></td></tr>';
			} else {
				echo '<b style="color:blue;">Unavailable</b> <input type="submit" value="Switch to Available"><input type="hidden" name="isdisabled" value="0"></td></tr>';
			}
			echo '<input type="hidden" name="devuserid" value="'.$esc_OWNID.'"></form>';
			//-FetchString-
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID."#devconf".'" method="POST"><tr><th>Fetch String </th><td> <input type="text" placeholder="NULL" name="fstr" value="'.$e_FSTR.'" style="width:320px;" maxlength="1024"> <input type="submit" value="Update"></td></tr><input type="hidden" name="devuserid" value="'.$esc_OWNID.'"></form>';
			echo '</table>';
			
			// General
			echo '<h3>General Config</h3><a name ="genconf"></a>';
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID."#genconf".'" method="POST"><table class="config">';
			//-DeviceName-
			echo '<tr><th>Device Name </th><td> <input type="text" placeholder="" name="conf_dnam" value="'.$e_NAM.'" style="width:300px;" maxlength="127"></td></tr>';
			//-Location-
			echo '<tr><th>GEO Location </th><td>(Longitude, Latitude) = ( <input type="text" placeholder="0 (default)" name="conf_lon" value="'.$g_LON.'" style="width:70px;"> , <input type="text" placeholder="0 (default)" name="conf_lat" value="'.$g_LAT.'" style="width:70px;"> )  <input type="checkbox" name="conf_fixed" value="1" '; if ($e_FIX == 1) { echo 'checked="checked"'; } echo '>Fixed</td></tr>';
			//-Place-
			echo '<tr><th>Place Name </th><td> <input type="text" placeholder="Landmark, Address, etc" name="conf_plc" value="'.$e_PLC.'" style="width:200px;" maxlength="127"></td></tr>';
			//-TimeZone-
			echo '<tr><th>TimeZone </th><td> UTC <select name="conf_tz">';
			foreach ($tzlist as $tzkey => $tzval) { if ($tzval == $e_TZN) { echo '<option value="'.$tzval.'" selected="selected">'.$tzkey.'</option>'; } else { echo '<option value="'.$tzval.'">'.$tzkey.'</option>'; } }
			echo '</select></td></tr>';
			//-Altitude-
			echo '<tr><th>Altitude </th><td> <input type="text" name="conf_alt" value="'.$e_ALT.'" style="width:50px;"> m (above sea level)</td></tr>';
			//-AttachedHeight-
			echo '<tr><th>Attached Height </th><td> <input type="text" name="conf_ath" value="'.$e_AHI.'" style="width:50px;"> mm (above ground level)</td></tr>';
			//-isUser&hidden-
			echo '<tr><th>Option Flags </th><td> ';
			echo '<input type="checkbox" name="conf_isuser" value="1" '; if ($e_USR == 1) { echo 'checked="checked"'; } echo '>is_User(turn on User-mode Uploader)';
			echo '  <input type="checkbox" name="conf_ishidden" value="1" '; if ($e_HID == 1) { echo 'checked="checked"'; } echo '>is_Hidden';
			echo '</td></tr>';
			//-BUTTON-
			echo '</table><input type="hidden" name="devuserid" value="'.$esc_OWNID.'"><span class="config"><input type="submit" value="Change General Config"><input type="reset" value="Revert to previous"></span></form>';
			
			// Device Image & Description
			echo '<h3>Device Image & Description</h3><a name ="imgdsc"></a>';
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID."#imgdsc".'" method="POST" enctype="multipart/form-data"><table class="config">';
			//-Image-
			echo '<tr><th>Device Image </th><td> Upload (image/jpeg only): <input type="file" accept=".jpg,image/jpeg" name="conf_devimg"></td></tr>';
			//-Description-
			echo '<tr><th>Description </th><td> <textarea name="conf_comm" rows="5" cols="60">'.$e_COM.'</textarea></td></tr>';
			//-BUTTON-
			echo '</table><input type="hidden" name="devuserid" value="'.$esc_OWNID.'"><input type="hidden" name="devownname" value="'.$e_NAM.'"><span class="config"><input type="submit" value="Change Image&Comment"><input type="reset" value="Revert to previous"></span></form>';
			
			// Delete
			echo '<h3>Delete this Device</h3><a name ="del"></a>';
			echo '<form action="'.$_SERVER['PHP_SELF']."?id=".$esc_DEVID.'" method="POST"><table class="config">';
			//-Password-
			echo '<tr><th>Password </th><td> <input type="password" placeholder="Your Password here" name="conf_delpass" value="" style="width:200px;"></td></tr>';
			//-Confirm-
			echo '<tr><th>Confirm </th><td> <input type="checkbox" name="conf_delconf" value="1">Bye</td></tr>';
			//-BUTTON-
			echo '</table><input type="hidden" name="devuserid" value="'.$esc_OWNID.'"><span class="config"><input type="submit" value="DELETE"></span></form>';
			
	echo '</article>';
		}
?>















<!-- X ---------------------------------------------------------------------- X -->
<?php mysqli_close($db); ?>
	<footer>© Sony Computer Science Laboratories, Inc.</footer></div>
</body>
</html>
<!-- EOF -->