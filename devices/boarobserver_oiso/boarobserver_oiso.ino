//ONLY USE FOR ARDUINO MEGA 2560

//#include <avr/sleep.h>
//#include <MsTimer2.h>
//#include <Prescaler.h>
#include <Adafruit_VC0706.h>
#include <SD.h>
#include <SoftwareSerial.h>
#include <a3gs2.h>

#define DHTport 68


Adafruit_VC0706 cam = Adafruit_VC0706(&Serial2);
volatile const uint8_t *b64table = (uint8_t *)"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
const char *server = "ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com";
const char *pathF = "/api/original/fetch.php";
const char *pathR = "/api/original/report.php";
const char *header = "Content-Type: text/plain";
const int port = 80;
const char *ACCESSKEY = "9e55daa40615367152fe055e89ad554ab95b3f84";
const char *body = "[ACCESSKEY]9e55daa40615367152fe055e89ad554ab95b3f84";

char date[a3gsDATE_SIZE], time[a3gsTIME_SIZE];
char res[a3gsMAX_RESULT_LENGTH+1];
volatile char mode = 'A';
int *len;
boolean blinker = false;
volatile unsigned long intervalnext = 2700000; // Sleep interval(ms), default value is 1 hour.

char temparature[6];
char humidity[5];
char brightness[5];

boolean debugon = false;
char debugstr[8];
/*
  0 = start bit
  1 = SD card
  2 = Camera
  3 = DHTport
  4 = take pict
  5 = fetch
  6 = connectTCP
  7 = waiting mode
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
Serial2(17,16)  Camera Serial
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
  //envmajor();
  mainframe();
  cam.resumeVideo();
  cam.setMotionDetect(true);
  Serial.print("Mode = ");
  Serial.println(mode);
  debugstr[7] = mode;
  // loop costs 4 minutes!
  if (debugon == true)
    report();
  if (mode == 'Z') {
    Serial.println("Waiting motion...");
    while (true) {
      delay(1);
      if (cam.motionDetected()) {
        a3gs.setLED(true); delay(100); a3gs.setLED(false); delay(100); a3gs.setLED(true); delay(100); a3gs.setLED(false); delay(100);
        a3gs.setLED(true); delay(100); a3gs.setLED(false); delay(100); a3gs.setLED(true); delay(100); a3gs.setLED(false);
        Serial.println("Motion detected!");
        break;
      }
    }
  } else {
    // No Sleep-interrupt routine implemented on openSy-ISE model.
    //
    if (mode == 'H')
      intervalnext = (1440-4)*60000; // 24hours for test
    if (mode == 'G')
      intervalnext = (720-4)*60000; // 12hours for test
    if (mode == 'F')
      intervalnext = (360-4)*60000; // 6hours for test
    if (mode == 'E')
      intervalnext = (180-4)*60000; // 3hours for test
    if (mode == 'D')
      intervalnext = (120-4)*60000; // 2hours for test
    if (mode == 'C')
      intervalnext = (60-4)*60000; // an hour for test
    if (mode == 'B')
      intervalnext = (30-4)*60000; // 30minutes for test
    if (mode == 'A')
      intervalnext = 1; // zero interval for test

    //delay(6300000); // per 1.75hours
    //delay(intervalnext);
    Serial.print("Sleep for "); Serial.print(intervalnext); Serial.println(" seconds...");
    delay(intervalnext);
  }
}














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
  
  
  
  File envdata = SD.open("envdata.txt", FILE_WRITE);
  
  int brgt = analogRead(69);
  // temp  hum  br
  // 0123450123401234
 
 
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
}

















void mainframe() {

  cam.reset();
  cam.setImageSize(VC0706_320x240);
  Serial.print("[Info]   Snap in 3 secs... ");
  delay(1000);
  Serial.print("2... ");
  delay(1000);
  Serial.println("1... ");
  delay(1000);

  if (! cam.takePicture()) {
    Serial.println("[Failed] Taken picture");
    debugstr[4] = 'N';
  } else 
    Serial.println("[OK]     Taken picture");
    cam.setMotionDetect(false);
    debugstr[4] = 'Y';
  
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
    char picfilename[21];
    strcpy(picfilename, "/012345/012345.JPG");
    picfilename[1]  = date[2];
    picfilename[2]  = date[3];
    picfilename[3]  = date[5];
    picfilename[4]  = date[6];
    picfilename[5]  = date[8];
    picfilename[6]  = date[9];
    picfilename[8]  = time[0];
    picfilename[9]  = time[1];
    picfilename[10] = time[3];
    picfilename[11] = time[4];
    picfilename[12] = time[6];
    picfilename[13] = time[7];

    File imgFile = SD.open(picfilename, FILE_WRITE);

  // Get the size of the image (frame) taken
  uint16_t jpglen = cam.frameLength();
  Serial.print("[Info]   Image: "); Serial.print(jpglen, DEC); Serial.println(" bytes");

  while (jpglen > 0) {
    // read 64 bytes at a time;
    uint8_t *buffer;
    uint8_t bytesToRead = min(64, jpglen);
    buffer = cam.readPicture(bytesToRead);
    imgFile.write(buffer, bytesToRead);
    jpglen -= bytesToRead;
  }
  imgFile.close();
  Serial.print("[Info]   Successfully saved as ");
  Serial.print(picfilename);
  Serial.println("");










    len = (int*)sizeof(res);
    if (a3gs.httpPOST(server, port, pathF, header, body, res, len, false) == 0) {
      Serial.print("[OK]     httpPOST() from ");
      Serial.println(server);
      mode = (volatile char)res[0];
      Serial.print("[Info]   Server config file set as [");
      Serial.print(mode);
      Serial.println("]");
      debugstr[5] = 'Y';
    }
    else {
      Serial.print("[Failed] httpPOST() from ");
      Serial.println(server);
      debugstr[5] = 'N';
    }




  File myFile = SD.open(picfilename, FILE_READ);
  unsigned int filesize = myFile.size();
  unsigned int minfile = 0;
  while (minfile < (filesize-3))
    minfile = minfile + 3;
  if (myFile) {
    Serial.println("[OK]     Jpeg picture opening");
  } else {
    Serial.println("[Failed] Jpeg picture opening");
  }



    debugstr[6] = 'N';
    if (a3gs.connectTCP(server, port) != 0) {
      Serial.print("[Failed] connectTCP()");
    } else {
      // Send POST request
      a3gs.write("POST /api/original/upload.php HTTP/1.1$n");
      a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
      a3gs.write("Content-Type: text/plain$n");
      a3gs.write("Content-Length: "); a3gs.write("20000"); a3gs.write("$n$n");
      
      a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
      Serial.print("[ACCESSKEY]"); Serial.print(ACCESSKEY);
      a3gs.write("[PLACE]Koma Garvage hill"); Serial.print("[PLACE]Koma Garvage hill");
      a3gs.write("[TAG]"); Serial.print("[TAG]");
      if (mode == 'Z') {
        a3gs.write("#MotionDetect"); Serial.print("#MotionDetect");
      } else {
        a3gs.write("#ConstInterval"); Serial.print("#ConstInterval");
      }
      /*
      a3gs.write("[DATA]"); Serial.print("[DATA]");
      a3gs.write("TEMP=");  a3gs.write(temparature); a3gs.write(";");
      Serial.print("TEMP=");  Serial.print(temparature); Serial.print(";");
      a3gs.write("HUMD=");  a3gs.write(humidity); a3gs.write(";");
      Serial.print("HUMD=");  Serial.print(humidity); Serial.print(";");
      a3gs.write("BRGT=");  a3gs.write(brightness); a3gs.write(";");
      Serial.print("BRGT=");  Serial.print(brightness); Serial.println(";");
      */
      a3gs.write("[MIMETYPE]image/jpeg[CONTENT]");








  // SOMEHOW Not running in case use repeat style format (e.g. for, while)
  // Fragment:
  //
  unsigned int fpos = 0;
  unsigned int curr = 0;
  uint8_t buf[3] = {0x00,0x00,0x00};
  uint8_t bin[4] = {0x00,0x00,0x00,0x00};
  Serial.println("");
  Serial.print("CFP = ");
  while (fpos < minfile) {
    myFile.seek(fpos); buf[0] = myFile.peek();
    fpos++;
    myFile.seek(fpos); buf[1] = myFile.peek();
    fpos++;
    myFile.seek(fpos); buf[2] = myFile.peek();
    fpos++;

    bitWrite(bin[0],5,bitRead(buf[0],7));
    bitWrite(bin[0],4,bitRead(buf[0],6));
    bitWrite(bin[0],3,bitRead(buf[0],5));
    bitWrite(bin[0],2,bitRead(buf[0],4));
    bitWrite(bin[0],1,bitRead(buf[0],3));
    bitWrite(bin[0],0,bitRead(buf[0],2));
    bitWrite(bin[1],5,bitRead(buf[0],1));
    bitWrite(bin[1],4,bitRead(buf[0],0));
    bitWrite(bin[1],3,bitRead(buf[1],7));
    bitWrite(bin[1],2,bitRead(buf[1],6));
    bitWrite(bin[1],1,bitRead(buf[1],5));
    bitWrite(bin[1],0,bitRead(buf[1],4));
    bitWrite(bin[2],5,bitRead(buf[1],3));
    bitWrite(bin[2],4,bitRead(buf[1],2));
    bitWrite(bin[2],3,bitRead(buf[1],1));
    bitWrite(bin[2],2,bitRead(buf[1],0));
    bitWrite(bin[2],1,bitRead(buf[2],7));
    bitWrite(bin[2],0,bitRead(buf[2],6));
    bitWrite(bin[3],5,bitRead(buf[2],5));
    bitWrite(bin[3],4,bitRead(buf[2],4));
    bitWrite(bin[3],3,bitRead(buf[2],3));
    bitWrite(bin[3],2,bitRead(buf[2],2));
    bitWrite(bin[3],1,bitRead(buf[2],1));
    bitWrite(bin[3],0,bitRead(buf[2],0));
    a3gs.write(b64table[bin[0]]); a3gs.write(b64table[bin[1]]); a3gs.write(b64table[bin[2]]); a3gs.write(b64table[bin[3]]);
    //Serial.write(b64table[bin[0]]); Serial.write(b64table[bin[1]]); Serial.write(b64table[bin[2]]); Serial.write(b64table[bin[3]]);
    //Serial.print(",");
    curr++;
    if (curr >= 10) {
      curr = 0;
      //Serial.print(fpos);
      //Serial.print(", ");
      Serial.print(".");
      a3gs.setLED(blinker);
      blinker = !blinker;
    }
  }//while (fpos < minfile)

  if (filesize - fpos == 2) { //when % = 2
    myFile.seek(fpos); buf[0] = myFile.peek();
    fpos++;
    myFile.seek(fpos); buf[1] = myFile.peek();
    fpos++;
    bitWrite(bin[0],5,bitRead(buf[0],7));
    bitWrite(bin[0],4,bitRead(buf[0],6));
    bitWrite(bin[0],3,bitRead(buf[0],5));
    bitWrite(bin[0],2,bitRead(buf[0],4));
    bitWrite(bin[0],1,bitRead(buf[0],3));
    bitWrite(bin[0],0,bitRead(buf[0],2));
    bitWrite(bin[1],5,bitRead(buf[0],1));
    bitWrite(bin[1],4,bitRead(buf[0],0));
    bitWrite(bin[1],3,bitRead(buf[1],7));
    bitWrite(bin[1],2,bitRead(buf[1],6));
    bitWrite(bin[1],1,bitRead(buf[1],5));
    bitWrite(bin[1],0,bitRead(buf[1],4));
    bitWrite(bin[2],5,bitRead(buf[1],3));
    bitWrite(bin[2],4,bitRead(buf[1],2));
    bitWrite(bin[2],3,bitRead(buf[1],1));
    bitWrite(bin[2],2,bitRead(buf[1],0));
    bitWrite(bin[2],1,0);
    bitWrite(bin[2],0,0);
    a3gs.write(b64table[bin[0]]); a3gs.write(b64table[bin[1]]); a3gs.write(b64table[bin[2]]); a3gs.write('=');
    //Serial.write(b64table[bin[0]]); Serial.write(b64table[bin[1]]); Serial.write(b64table[bin[2]]); Serial.write('=');
    Serial.print("LAST2!");
  }

  if (filesize - fpos == 1) { //when % = 1
    myFile.seek(fpos); buf[0] = myFile.peek();
    fpos++;
    bitWrite(bin[0],5,bitRead(buf[0],7));
    bitWrite(bin[0],4,bitRead(buf[0],6));
    bitWrite(bin[0],3,bitRead(buf[0],5));
    bitWrite(bin[0],2,bitRead(buf[0],4));
    bitWrite(bin[0],1,bitRead(buf[0],3));
    bitWrite(bin[0],0,bitRead(buf[0],2));
    bitWrite(bin[1],5,bitRead(buf[0],1));
    bitWrite(bin[1],4,bitRead(buf[0],0));
    bitWrite(bin[1],3,0);
    bitWrite(bin[1],2,0);
    bitWrite(bin[1],1,0);
    bitWrite(bin[1],0,0);
    a3gs.write(b64table[bin[0]]); a3gs.write(b64table[bin[1]]); a3gs.write('='); a3gs.write('=');
    //Serial.write(b64table[bin[0]]); Serial.write(b64table[bin[1]]); Serial.write('='); Serial.write('=');
    Serial.print("LAST1!");
  }





      myFile.close();
      a3gs.write("$n$n");
      a3gs.setLED(false);
      Serial.println(""); Serial.println("");
      // Recieve responces
      char res[a3gsMAX_RESULT_LENGTH+1];
      while(( a3gs.read(res,a3gsMAX_RESULT_LENGTH+1)) > 0) {
        Serial.print(res);
      }
      Serial.println("[Info]   Disconnecting...");
      a3gs.disconnectTCP();
      debugstr[6] = 'Y';
    }



  //Serial.println("[Info]   a3gs.end...");
  //a3gs.end();
  //delay(1000);
  //Serial.println("[Info]   a3gs.shutdown...");
  //a3gs.shutdown();
  //delay(4000);
  Serial.println("");




  //return (char)res;
}











void report() {
  int resultPOST = a3gs.connectTCP(server, port);
  delay(10);
  a3gs.write("POST /api/original/report.php HTTP/1.1$n");
  a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
  a3gs.write("Content-Type: text/plain$n");
  a3gs.write("Content-Length: "); a3gs.write("512"); a3gs.write("$n$n");
  a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
  a3gs.write("[LOG]"); a3gs.write(debugstr);
  Serial.print("Reporting result = "); Serial.println(resultPOST);
  a3gs.disconnectTCP();
  debugon = false;
}

















void setup() {
  pinMode(53, OUTPUT);
  Serial.begin(38400);
  delay(3000);  // Wait for Start Serial Monitor
  Serial.println("Ready.");
  //set_sleep_mode(SLEEP_MODE_PWR_SAVE);
  debugstr[0] = '?';
  debugstr[1] = 'Y';
  
  if (!SD.begin(53)) {
    Serial.println("[Failed] SD card");
    debugstr[1] = 'N';
    // don't do anything more:
  }
  
  if (cam.begin()) {
    Serial.println("[OK]     Camera");
    cam.setMotionDetect(true);
    debugstr[2] = 'Y';
  } else {
    Serial.println("[Failed] Camera");
    debugstr[2] = 'N';
    return;
  }
  cam.setImageSize(VC0706_320x240);






  //3G Shield init
  Serial.println("[Info]   3G Init... ");
  if (a3gs.start() == 0 && a3gs.begin(0,115200) == 0) {
    Serial.println("[OK]     3G initialize");
    int rssi;
    a3gs.getRSSI(rssi);
    Serial.print("[Info]   RSSI = ");
    Serial.print(rssi);
    Serial.println("dbm");
  } else {
    Serial.println("[Failed] 3G initialize");
  }
  Serial.println("---------------------------------------------------");
  Serial.println("");
}









void loop() {
  //Prescaler.set(clock_div_64);
  //scaledDelay(10000);
  //Prescaler.set(clock_div_1);
  itp();
}


























//EOF
