<?php
	// error?
	error_reporting(E_ALL & ~E_NOTICE);
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


	// Get about selected device
	$esc_USERID = $_SESSION['USER_ID']; // *need escape var
	$esc_DEVID = $_GET['id']; // *need escape var
	$devicequery = mysqli_query($db, "SELECT * FROM device WHERE device_id = '$esc_DEVID' "); //get selected device's row
	$devq_field = mysqli_fetch_row($devicequery);
	if (!isset($devq_field[0])) { // if no device id on database
		$_SESSION['ERROR'] = "Device ID is not exist on this database.";
		header("Location: sorry.php");
		exit;
	}


	// Select article color
	$articletype = "device_normal";
	if ($devq_field[12] == 1) { $articletype = "device_user"; } //is_user=1
	if ($devq_field[13] == 1) { $articletype = "device_hidden"; } //is_hidden=1


	// Get who is owner
	$esc_OWNID = $devq_field[1];
	$userquery = mysqli_query($db, "SELECT username FROM user WHERE user_id = '$esc_OWNID' ");
	$uq_field = mysqli_fetch_row($userquery);


	// Redirect general user if they access hidden device (except owner)
	if ($devq_field[13] == 1 and $_SESSION['VIEW_HIDDEN'] == 0 and $esc_OWNID != $esc_USERID) {
		$_SESSION['ERROR'] = "The request is referring hidden device.";
		header("Location: sorry.php");
		exit;
	}


	// Get device's location (position)
	$posquery = mysqli_query($db, "SELECT X(location), Y(location) FROM device WHERE device_id = '$esc_DEVID' ");
	$posq_field = mysqli_fetch_row($posquery);


	// Get data rows by the device from data table
	$dataquery = mysqli_query($db, "SELECT * FROM data WHERE device_id = '$esc_DEVID' ORDER BY timestamp DESC");
	$strquery = mysqli_query($db, "SELECT date, time, data FROM data WHERE device_id = '$esc_DEVID' ORDER BY timestamp DESC");
	$datrow_cnt = mysqli_num_rows($dataquery);
	// Get data location (position)
	$dpquery = mysqli_query($db, "SELECT X(location), Y(location) FROM data WHERE device_id = '$esc_DEVID' ORDER BY timestamp DESC");


















?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>Device information of <?php echo $devq_field[2]; ?> / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<script type="text/javascript" src="./js/jquery-1.9.1.min.js"></script>
<!-- X ---------------------------------------------------------------------- X -->











	<?php echo '<article id="device" class="' . $articletype . '">'; ?>
		<?php echo '<img src="./device_images/' . $devq_field[11] . '" alt="Device image">'; ?>


		<!-- Device information table -->
		<table id="info">
			<tr><th>Device ID</th>
				<td><?php echo $devq_field[0]; ?></td></tr>
			<tr><th>Owner</th>
				<td><?php echo $uq_field[0]; ?></td></tr>
			<tr><th>Place</th>
				<td><?php echo $devq_field[9]; ?></td></tr>
			<tr><th>Location</th>
				<td>
				<?php if ($devq_field[6] == 0) : ?>
					<?php echo 'Indefinite'; ?>
				<?php else : ?>
					<?php echo '<a href="http://maps.google.com/maps?q='.$posq_field[1].','.$posq_field[0].'" target="_blank">'.$posq_field[0].', '.$posq_field[1].'</a>'; ?></td></tr>
				<?php endif; ?>
			<tr><th>Altitude</th>
				<td><?php echo $devq_field[7]; ?> m</td></tr>
			<tr><th>Attached Height</th>
				<td><?php echo $devq_field[8]; ?> mm</td></tr>
			<tr><th>Time Zone</th>
				<td><?php echo $devq_field[4]; ?> hour(s)</td></tr>
			<tr><th>Status</th>
				<?php $enb = 'Error'; if ($devq_field[15] == 0) { $enb = 'Available'; }; if ($devq_field[15] == 1) { $enb = 'Unavailable'; }; ?>
				<td><?php echo $enb; ?></td></tr>
		</table>


		<!-- Device name -->
		<h2><?php echo "Device: ".$devq_field[2]; ?></h2>
		<!-- Device description -->
		<p><?php echo $devq_field[10]; ?></p>


		<!-- Datalist -->
		<h2>Latest Data</h2>
		<!-- All of in Data rows -->
		<table id="data">
			<!-- Data field name -->
			<tr><th>LOCALTIME</th><th>GEO INFO</th><th>CONTENT</th><th>DATA STRING</th><th>TAGS</th></tr>
			<!-- Data body -->
<?php
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
						echo '<a href="http://maps.google.com/maps?q='.$dpq_field[1].','.$dpq_field[0].'" target="_blank">'.$datq_field[10].'</a>';
					}
					echo "</td>";
					// contentfile
					if ($datq_field[11] == "NO_CONTENT") {
						echo "<td> - </td>";
					} else {
						echo '<td><a href="../../contents/'.$datq_field[11].'" target="_blank">'.$datq_field[12].'</a></td>';
					}
					// datastring
					echo "<td>".$datq_field[13]."</td>";
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
				//$dstr_DT[$i]["date"] = substr($strq_field[0], -5);
				//$dstr_DT[$i]["time"] = substr($strq_field[1], 0, 5);
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
		<h2>Graph View</h2>
		<form action="./dev.php" method="GET">
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



		<!-- Config -->
		<h2>Config</h2>
		<section class="dat">
			------------------------- 準備中です -------------------------
		</section>


	</article>












<!-- X ---------------------------------------------------------------------- X -->
<?php mysqli_close($db); ?>
	<footer>© Sony Computer Science Laboratories, Inc.</footer></div>
</body>
</html>
<!-- EOF -->