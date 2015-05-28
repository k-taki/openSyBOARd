<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="utf-8">
<style type="text/css">
body {
	margin: 0px;
	padding: 0px;
}
#graph {
	width : 600px;
	height: 400px;
	margin: 20px auto;
}
.graph-title {
	font-size:16px;
	font-weight:bold;
	text-align:center;
	margin:50px 0 0;
}
</style>
<script type="text/javascript" src="./js/jquery-1.9.1.min.js"></script>
</head>
<body>
<div id="graph"></div>
<script type="text/javascript" src="./js/flotr2.min.js"></script>
<script type="text/javascript">


$(function advanced_titles(container){

	var
	d1 = [],
	d2 = [],
	ticks_X = [],
	rainfall_amount = [30, 0, 0, 1, 0, 80, 10],
	temperature = [34.5, 34.8, 33.2, 34.1, 30.8, 39.4, 36.0],
	week = ["8/30", "8/31", "9/1", "9/2", "9/3", "9/4", "9/5"],
	i, graph, options;

	for (i = 0; i < 7; i++) {
		ticks_X.push([i, week[i]]);
		d1.push([i, rainfall_amount[i]]);
		d2.push([i, temperature[i]]);
	}

    graph =[
		{data: d1, label: "降水量", bars: { show: true, barWidth: 0.8,lineWidth: 0 }},
		{data: d2, label: "気温", yaxis: 2}
	];
	
	options = {
		title: "降水量と気温のグラフ",
		HtmlText: false,
		xaxis: {
			ticks: ticks_X,
			title: "日時"
		},
		yaxis: {
			ticks: [0, 25, 50, 75, [100, "100mm"]],
			min: 0,
			max: 100,
			title: "降水量"
		},
		y2axis: {
			ticks: [0, 10, 20, 30, 40, [50, "50°C"]],
			min: 0,
			max: 50,
			title: "気温"
		},
		legend: {
			position: "nw"
		}			
	};

	Flotr.draw($('#graph').get(0), graph, options);
	
});

</script>
</body>
</html>