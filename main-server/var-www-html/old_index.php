<!DOCTYPE html>
<html>
<!-- header -->
<head>
<meta charset="UTF-8">
<meta name="author" content="">
<meta name="keywords" content="">
<meta name="description" content="">
<meta http-equiv="Refresh" content=300>
<link rel="stylesheet" href="./css/style.css">
<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Oleo+Script+Swash+Caps">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="./js/jquery.carouFredSel-6.2.1.js"></script>
<script src="./js/jquery.easing.js"></script>
<script src="./js/clock.js"></script>
<title>LiveCamduino!</title>
</head><!-- header/end -->




<!-- contents main frame -->
<body><section id="container">







<!-- side -->
	<aside id="sidebar">
		<?php
			$lf = "./photo/latest.jpg";
			$ut = filemtime($lf)-40;
			echo "Last updated time: <br>".date("Y-m-d H:i:s",$ut)." JST";
		?>
		<br><br>
		<a href = "./photo/l-1.jpg"><img src="./photo/l-1.jpg"></a>
		<?php
			$lf = "./photo/l-1.jpg";
			$ut = filemtime($lf)-40;
			echo date("H:i:s",$ut);
		?><br>
		<a href = "./photo/l-2.jpg"><img src="./photo/l-2.jpg"></a>
		<?php
			$lf = "./photo/l-2.jpg";
			$ut = filemtime($lf)-40;
			echo date("H:i:s",$ut);
		?><br>
		<a href = "./photo/l-3.jpg"><img src="./photo/l-3.jpg"></a>
		<?php
			$lf = "./photo/l-3.jpg";
			$ut = filemtime($lf)-40;
			echo date("H:i:s",$ut);
		?><br>
		<a href = "./photo/l-4.jpg"><img src="./photo/l-4.jpg"></a>
		<?php
			$lf = "./photo/l-4.jpg";
			$ut = filemtime($lf)-40;
			echo date("H:i:s",$ut);
		?><br>
		<a href = "./photo/l-5.jpg"><img src="./photo/l-5.jpg"></a>
		<?php
			$lf = "./photo/l-5.jpg";
			$ut = filemtime($lf)-40;
			echo date("H:i:s",$ut);
		?><br>
		<br>
		<a href = "./photo/" class = "link">History</a><br>
		<a href = "./setup/" class = "link">Setup</a>
	</aside><!-- *side/end -->




<!-- clock -->
	<section id="watch">
		<section id="c_daytime"></section>
		<section id="c_clock"></section>
		<section id="c_type"></section>
	</section><!-- *clock/end -->



<!-- ToDo -->
	<div class="todoframe">
		<div class="todo"><img src="./photo/latest.jpg"></div>
		<!-- <div class="todo"></div>
		<div class="todo"></div>
		<div class="todo"></div> -->
	</div><!-- *ToDo/end -->







</section><!-- *container --></body>
</html>
<!-- EOF -->