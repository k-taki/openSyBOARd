//ONLY USE FOR ARDUINO MEGA 2560

#include <SPI.h>
#include <SD.h>
#include <SoftwareSerial.h>
#include <a3gs2.h>

#define DHTport 68


volatile const uint8_t *b64table = (uint8_t *)"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
const char *server = "ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com";
const int port = 80;
const char *ACCESSKEY = "9e55daa40615367152fe055e89ad554ab95b3f84";

  // only use FETCH
  const char *pathF = "/api/org/fetch.php";
  const char *header = "Content-Type: text/plain";
  const char *body = "[ACCESSKEY]9e55daa40615367152fe055e89ad554ab95b3f84";


char date[a3gsDATE_SIZE], time[a3gsTIME_SIZE];
char res[a3gsMAX_RESULT_LENGTH+1];
volatile char mode = '-';
int *len;
volatile unsigned long intervalnext = 2700000UL; // Sleep interval(ms), default value is 1 hour.

char xbfrom[9] = "00000000";

char temparature[6];
char humidity[5];
char brightness[5];

boolean debugon = false;
char debugstr[9] = "--------";
/*
  Debug Strings:
    0 = start bit
    1 = SD card
    2 = Camera
    3 = DHTport
    4 = take pict
    5 = fetch
    6 = connectTCP
    7 = waiting mode
    8 = NULL char
  EEPROM adressing:
    0 ~ envdata
   20 ~ date
   30 ~ xbee
*/







/*
--- Standard I/O pins allocation ---

D0    Serial RX
D1    Serial TX
D2    3G INT0
D3
D4
D5
D6    3G PWR
D7    3G REG
D8
D9
D10
D11   
D12   
D13   
A0    
A1    
A2
A3
A4
A5


--- MEGA's Extended pins allocation ---

Serial1(19,18)  3G Serial (pin 4,5 on the shield)
Serial2(17,16)  NO USE (For Camera)
Serial3(15,14)  XBee Serial (pin 0,1 on the shield)

D50   SD SPI        (pin 11 on the shield)
D51   SD SPI        (pin 12 on the shield)
D52   SD SPI        (pin 13 on the shield)
D53   SD ChipSelect (pin  4 on the shield)

A14   AM2302 (D68)
A15   CdS    (D69)


--- Flow chart ---

setup()
  |
  |
loop() --- itp() --- mainframe()




*/









void itp() {
  // mode = mainframe();
  envmajor();
  mainframe();
  Serial.print("Mode = ");
  Serial.println(mode);
  debugstr[7] = mode;
  if (debugon == true)
    report();
  //-----------------------------------------------------------------------
  // if mode='Z'
  //
    Serial.println("Waiting XBee...");
    Serial3.flush();
    int cnt = 0;
    while (true) {
      delay(970);
      // Waiting XBee Serial Port
      if (Serial3.available() > 0) { // if recieve XBee Signal:
        uint8_t res[16];
        for (int i=0; i<=16; i++) { res[i] = Serial3.read(); }
        Serial.print("XBee-Signal Recieved from ");
        Serial.print(res[8],HEX);  Serial.print("-");
        Serial.print(res[9],HEX);  Serial.print("-");
        Serial.print(res[10],HEX); Serial.print("-");
        Serial.print(res[11],HEX); Serial.println();
        
        File xbdat = SD.open("XBEEDATA.TXT", FILE_WRITE);
        xbdat.seek(0); xbdat.print(res[8],HEX);
        xbdat.seek(2); xbdat.print(res[9],HEX);
        xbdat.seek(4); xbdat.print(res[10],HEX);
        xbdat.seek(6); xbdat.print(res[11],HEX);
        xbdat.seek(0);
        xbfrom[0] = xbdat.read(); xbfrom[1] = xbdat.read(); xbfrom[2] = xbdat.read(); xbfrom[3] = xbdat.read(); xbfrom[4] = xbdat.read(); xbfrom[5] = xbdat.read(); xbfrom[6] = xbdat.read(); xbfrom[7] = xbdat.read(); xbfrom[8] = 0x00;
        if ((res[8]!=0xFF)&(res[8]!=0x00)) { break;}
      }//-if-
      
      // To send sensor data per 3 hour
      cnt++;
      if (cnt >= 60) { //cnt>=7200
        cnt = 0;
        a3gs.getTime(date, time);
        // time = 0,4,8,12,16,20
        Serial.print(time); Serial.println();
        if ( (time[3]=='0')&(time[4]=='0') ) {
          char xbfrom[9] = "00000000";
          break;
        }//-if-
      }//-if-
    }//-while(true)-
  //-----------------------------------------------------------------------
  //
}//-void itp()-
















void DHTInit(void) {
  pinMode(DHTport,INPUT);
  delay(250);
}
byte DHTTSSeq(void) {
  byte dht11_in;
  // start condition
  // pull-down i/o pin from 18ms
  digitalWrite(DHTport, LOW);
  pinMode(DHTport,OUTPUT);
  delay(1);
  pinMode(DHTport,INPUT);
  delayMicroseconds(20+40); // High 20us + Slave ACK 80us/2

  dht11_in = digitalRead(DHTport); // Normal State = LOW
  if(dht11_in){
    Serial.println("[Failed] DHT Err1");
    debugstr[3] = '1';
    return(1);
  }
  delayMicroseconds(80);
  dht11_in = digitalRead(DHTport); // Normal State = HIGH
  if(!dht11_in){
    Serial.println("[Failed] DHT Err2");
    debugstr[3] = '2';
    return(1);
  }
  while( digitalRead(DHTport) ); // port LOW?
  debugstr[3] = 'Y';
  return(0);
}
byte read_dht11_dat(){
  byte i = 0;
  byte result=0;
  for(i=0; i< 8; i++){
    while( !digitalRead(DHTport) ); // port High?
    delayMicroseconds(49); // 28us or 70us 
    if( digitalRead(DHTport) ){
    result |=(1<<(7-i));
    while( digitalRead(DHTport) );
    }
  }
  return result;
}
void DHT_ACK(void) {
  while( digitalRead(DHTport) );
  delayMicroseconds(50);
}








void envmajor() {
  byte dht11_dat[5];
  byte i;
  byte dht11_check_sum;
  float hum,temp;

  DHTInit();
  DHTTSSeq();
  for (i=0; i<5; i++) dht11_dat[i] = read_dht11_dat();
  DHT_ACK();
  dht11_check_sum = dht11_dat[0]+dht11_dat[1]+dht11_dat[2]+dht11_dat[3];
  if(dht11_dat[4]!= dht11_check_sum){
    Serial.println("DHT11 checksum error");
    debugstr[3] = 'E';
  }
  temp = ((float)(dht11_dat[2]&0x7F)*256.+(float)dht11_dat[3])/10;
  if( dht11_dat[2] & 0x80 ) temp *= -1;
  hum = ((float)dht11_dat[0]*256.+(float)dht11_dat[1])/10;
  
  
  
  File envdata = SD.open("ENVRDATA.TXT", FILE_WRITE);
  
  int brgt = analogRead(69);
  // temp  hum  br
  // 0123450123401234 = (0-15)
 
 
  // temp
  envdata.seek(0);
  envdata.print("+00.0");
  if (temp < 0) envdata.seek(1);
  if (temp >= 0 && brgt < 10) envdata.seek(2);
  if (brgt >= 10) envdata.seek(1);
  envdata.print(temp,1);
  envdata.seek(0);
  temparature[0] = envdata.read(); temparature[1] = envdata.read(); temparature[2] = envdata.read(); temparature[3] = envdata.read(); temparature[4] = envdata.read(); temparature[5] = 0x00;

  // hum
  envdata.seek(6);
  envdata.print("00.0");
  if (hum < 10) envdata.seek(7);
  if (brgt >= 10) envdata.seek(6);
  envdata.print(hum,1);
  envdata.seek(6);
  humidity[0] = envdata.read(); humidity[1] = envdata.read(); humidity[2] = envdata.read(); humidity[3] = envdata.read(); humidity[4] = 0x00;

  // bright
  envdata.seek(11);
  envdata.print("0000");
  if (brgt <= 9) envdata.seek(14);
  if (brgt >= 10 && brgt <= 99) envdata.seek(13);
  if (brgt >= 100 && brgt <= 999) envdata.seek(12);
  if (brgt >= 1000) envdata.seek(11);
  envdata.print(brgt);
  envdata.seek(11);
  brightness[0] = envdata.read(); brightness[1] = envdata.read(); brightness[2] = envdata.read(); brightness[3] = envdata.read(); brightness[4] = 0x00;

  
  envdata.close();
}//-void envmajor()-

















void mainframe() {
  
  debugstr[4] = 'U'; // Picture is unavailable

    // mkdir and create pictfile
    a3gs.getTime(date, time);
    char pictfolder[8];
    strcpy(pictfolder, "/012345");
    pictfolder[1] = date[2];
    pictfolder[2] = date[3];
    pictfolder[3] = date[5];
    pictfolder[4] = date[6];
    pictfolder[5] = date[8];
    pictfolder[6] = date[9];
    if (!SD.exists(pictfolder)) {
      SD.mkdir(pictfolder);
      debugon = true;
    }







    // Fetch config
    len = (int*)sizeof(res);
    if (a3gs.httpPOST(server, port, pathF, header, body, res, len, false) == 0) {
      Serial.print("[OK]     httpPOST() from ");
      Serial.println(server);
      mode = (volatile char)res[0];
      Serial.print("[Info]   Server config file set as [");
      Serial.print(mode);
      Serial.println("]");
      debugstr[5] = 'Y';
    } else {
      Serial.print("[Failed] httpPOST() from ");
      Serial.println(server);
      debugstr[5] = 'N';
    }






    Serial.print("XBee RSSI: -"); ATDB(); Serial.println("dBm");



    // Send
    debugstr[6] = 'N';
    if (a3gs.connectTCP(server, port) != 0) {
      Serial.print("[Failed] connectTCP()");
    } else {
      // Send POST request
      a3gs.setLED(true);
      a3gs.write("POST /api/original/upload.php HTTP/1.1$n");
      a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
      a3gs.write("Content-Type: text/plain$n");
      a3gs.write("Content-Length: "); a3gs.write("20000"); a3gs.write("$n$n");
      
      a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
      Serial.print("[ACCESSKEY]"); Serial.print(ACCESSKEY);
      //a3gs.write("[PLACE]Mt.Koma"); Serial.print("[PLACE]Mt.Koma");
      a3gs.write("[TAG]"); Serial.print("[TAG]");
      if (mode == 'Z') {
        a3gs.write("#MotionDetect"); Serial.print("#MotionDetect");
      } else {
        a3gs.write("#ConstInterval"); Serial.print("#ConstInterval");
      }
      a3gs.write("[DATA]"); Serial.print("[DATA]");
      a3gs.write("XBEE_ON("); a3gs.write(xbfrom); a3gs.write(")");
      Serial.print("XBEE_ON("); Serial.print(xbfrom); Serial.print(")");
      a3gs.write("TEMP(");  a3gs.write(temparature); a3gs.write(")");
      Serial.print("TEMP(");  Serial.print(temparature); Serial.print(")");
      a3gs.write("HUMD(");  a3gs.write(humidity); a3gs.write(")");
      Serial.print("HUMD(");  Serial.print(humidity); Serial.print(")");
      a3gs.write("BRGT(");  a3gs.write(brightness); a3gs.write(")");
      Serial.print("BRGT(");  Serial.print(brightness); Serial.println(")");
      
      a3gs.write("$n$n"); a3gs.setLED(false);
      Serial.println(""); Serial.println("");
      // Recieve responces
      char res[a3gsMAX_RESULT_LENGTH+1];
      while( (a3gs.read(res,a3gsMAX_RESULT_LENGTH+1)) > 0 ) {
        Serial.print(res);
      }
      Serial.println("[Info]   Disconnecting...");
      a3gs.disconnectTCP();
      debugstr[6] = 'Y';
    }//-else-
  Serial.println("");
  //return (char)res;
}//-void mainframe()-

























void report() {
  int resultPOST = a3gs.connectTCP(server, port);
  delay(10);
  a3gs.write("POST /api/org/report.php HTTP/1.1$n");
  a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
  a3gs.write("Content-Type: text/plain$n");
  a3gs.write("Content-Length: "); a3gs.write("512"); a3gs.write("$n$n");
  a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
  a3gs.write("[LOG]"); a3gs.write(debugstr);
  Serial.print("Reporting result = "); Serial.println(resultPOST);
  a3gs.disconnectTCP();
  debugon = false;
}//void report










/*############### ATSL ###############*/
void ATSL() {
  uint8_t cmd[] = {0x7E,0x00,0x04,0x08,0x01,0x53,0x4C,0x57};
  uint8_t res[16];
  Serial3.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=16; i++) {
    res[i] = Serial3.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  Serial.print(res[8],HEX);  Serial.print("-");
  Serial.print(res[9],HEX);  Serial.print("-");
  Serial.print(res[10],HEX); Serial.print("-");
  Serial.print(res[11],HEX);
}
/*############### ATDB ###############*/
void ATDB() {
  uint8_t cmd[] = {0x7E,0x00,0x04,0x08,0x01,0x44,0x42,0x70};
  uint8_t res[9];
  Serial3.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=9; i++) {
    res[i] = Serial3.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  //Serial.print("********");
  Serial.print(res[8],DEC);
}


















void setup() {
  pinMode(53, OUTPUT);
  Serial.begin(9600);
  delay(3000);  // Wait for Start Serial Monitor
  Serial.println("Ready.");
  debugstr[0] = '?';
  debugstr[1] = 'Y';
  
  if (!SD.begin(53)) {
    Serial.println("[Failed] SD card");
    debugstr[1] = 'N';
    
  }
  debugstr[2] = 'U'; //unavailable



  //XBee init
  Serial3.begin(9600);
  delay(1000);
  Serial.print("[Info]   XBee addr = 0013A200+"); ATSL(); Serial.println();





  //3G Shield init
  Serial.println("[Info]   3G Init... ");
  if (a3gs.start() == 0 && a3gs.begin(0,115200) == 0) {
    Serial.println("[OK]     3G initialize");
    int rssi = -127;
    a3gs.getRSSI(rssi);
    Serial.print("[Info]   RSSI = ");
    Serial.print(rssi);
    Serial.println("dbm");
  } else {
    Serial.println("[Failed] 3G initialize");
  }
  Serial.println("---------------------------------------------------");
  Serial.println("");
}//void setup()









void loop() {
  itp();
}


























//EOF
