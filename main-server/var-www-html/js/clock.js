setInterval ( 'clocknow()',1000 );

function clocknow(){
	weeks = new Array("Sun","Mon","Thu","Wed","Thr","Fri","Sat") ;
    now = new Date() ;
	y = now.getFullYear() ;
	mo = now.getMonth() + 1 ;
	d = now.getDate() ;
	w = weeks[now.getDay()] ;
	h = now.getHours();
	mi = now.getMinutes();
	s = now.getSeconds();

	sctt = "Current: Recess." ;
	if ( 6 <= h && h <= 8 ) { sctt = "Good morning!" ; }
	if ( h == 8 && 45 <= mi && mi <= 59 ) { sctt = "The first period starting soon..." ; }
	if ( h == 9 ) { sctt = "First period. (9:00 - 10:30)" ; }
	if ( h == 10 && 0 <= mi && mi <= 29 ) { sctt = "First period. (9:00 - 10:30)" ; }
	if ( h == 10 && 40 <= mi && mi <= 59 ) { sctt = "Second period. (10:40 - 12:10)" ; }
	if ( h == 11 ) { sctt = "Second period. (10:40 - 12:10)" ; }
	if ( h == 12 && 0 <= mi && mi <= 9 ) { sctt = "Second period. (10:40 - 12:10)" ; }
	if ( h == 12 && 10 <= mi && mi <= 59 ) { sctt = "Lunch time. (12:10 - 13:10)" ; }
	if ( h == 13 && 0 <= mi && mi <= 9 ) { sctt = "Lunch time. (12:10 - 13:10)" ; }
	if ( h == 13 && 10 <= mi && mi <= 59 ) { sctt = "Third period. (13:10 - 14:40)" ; }
	if ( h == 14 && 0 <= mi && mi <= 39 ) { sctt = "Third period. (13:10 - 14:40)" ; }
	if ( h == 14 && 50 <= mi && mi <= 59 ) { sctt = "Fourth period. (14:50 - 16:20)" ; }
	if ( h == 15 ) { sctt = "Fourth period. (14:50 - 16:20)" ; }
	if ( h == 16 && 0 <= mi && mi <= 19 ) { sctt = "Fourth period. (14:50 - 16:20)" ; }
	if ( h == 16 && 30 <= mi && mi <= 59 ) { sctt = "Fifth period. (16:30 - 18:00)" ; }
	if ( h == 17 ) { sctt = "Fifth period. (16:30 - 18:00)" ; }
	if ( 0 <= h && h <= 5 ) { sctt = "Sleepy time... zzz..." ; }
	if ( w == "Sun" ) { sctt = "Holiday!" ; }

	if ( mo < 10 ) { mo = "0" + mo ; }
	if ( d < 10 ) { d = "0" + d ; }
	if ( mi < 10 ) { mi = "0" + mi ; }
	if ( s < 10 ) { s = "0" + s ; }

	document.getElementById("c_daytime").innerHTML =  y + "/" + mo + "/" + d + " (" + w + ")";
	document.getElementById("c_clock").innerHTML = h + ":" + mi + ":" + s;
	document.getElementById("c_type").innerHTML = sctt


}
