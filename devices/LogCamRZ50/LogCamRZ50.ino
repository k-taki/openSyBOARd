// ONLY USE FOR MEGA 2560

#include <SoftwareSerial.h>\
#include <SPI.h>
#include <SD.h>
#include <LiquidCrystal.h>
#include <a3gs2.h>
#include <Adafruit_VC0706.h>

#define DHTport 54

/*
D0    Serial RX
D1    Serial TX
D2    3G INT0
D3    SWITCH Interrupt 1
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


--- MEGA's Extended pins ---

Serial1(19,18)  3G Serial (pin 4,5 on the shield)
Serial2(17,16)  Camera Serial
Serial3(15,14)  XBee Serial (pin 0,1 on the shield)

D22   LCD RS
D23   LCD Enable
D24   LCD D4
D25   LCD D5
D26   LCD D6
D27   LCD D7

D50   SD SPI        (pin 11 on the shield)
D51   SD SPI        (pin 12 on the shield)
D52   SD SPI        (pin 13 on the shield)
D53   SD ChipSelect (pin  4 on the shield)

A0    AM2302 (D54)
A1    CdS    (D55)

*/





// initialize fail frag
// 0(OK) - 1(SD card) - 2(Can't get epoch) - 3(3G unavailable)
int fail = 0;

// GPS warmup level
// 3(Hot) - 2(Warm) - 1(Cold) - 0(Cold) - -1(Init)
int gpsLEV = -1;

// 3G available frag
boolean a3gsAVL = false;

// Camera available frag
volatile boolean camAVL = false;

// GPS logpoint index
unsigned long loopnum = 0;

// Logfile name
char csvfilename[13];

// Environment data
char temparature[6];
char humidity[5];
char brightness[5];

// var for b64encode
unsigned int fpos = 0;
unsigned int curr = 0;
unsigned int curb = 0;
uint8_t buf[3] = {0x00,0x00,0x00};
uint8_t bin[4] = {0x00,0x00,0x00,0x00};


uint32_t epoch = 0;
char date[a3gsDATE_SIZE], time[a3gsTIME_SIZE];
char res[a3gsMAX_RESULT_LENGTH+1];
File logFile;
LiquidCrystal lcd(22, 23, 24, 25, 26, 27);
int rssi = -127;
Adafruit_VC0706 cam = Adafruit_VC0706(&Serial2);
volatile const uint8_t *b64table = (uint8_t *)"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
const char *server = "ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com";
const char *path = "/api/org/upload.php";
const int port = 80;
const char *ACCESSKEY = "302694d24e41fef85ce10a3677d1e0018b4c3074";
char lat[15], lon[15];
int len;
boolean blinker = false;
float estfsize;



















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
    lcd.setCursor(0,0); lcd.print("! DHT Error (1) ");
    return(1);
  }
  delayMicroseconds(80);
  dht11_in = digitalRead(DHTport); // Normal State = HIGH
  if(!dht11_in){
    lcd.setCursor(0,0); lcd.print("! DHT Error (2) ");
    return(1);
  }
  while( digitalRead(DHTport) ); // port LOW?
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
    lcd.setCursor(0,0); lcd.print("! DHT Error (3) "); // checksum error
  }
  temp = ((float)(dht11_dat[2]&0x7F)*256.+(float)dht11_dat[3])/10;
  if( dht11_dat[2] & 0x80 ) temp *= -1;
  hum = ((float)dht11_dat[0]*256.+(float)dht11_dat[1])/10;
  
  
  
  File envdata = SD.open("envdata.txt", FILE_WRITE);
  
  int brgt = analogRead(55);
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

  lcd.setCursor(0,1); lcd.print(temparature); lcd.print("degC, ");
  lcd.setCursor(11,1); lcd.print(humidity); lcd.print("%"); 
  
  envdata.close();
}


















void location() {
  a3gs.setLED(true);
  // GPS Check
  lcd.setCursor(0,0); lcd.print("GPS Searching.. ");
  if (a3gs.getLocation(a3gsMPBASED, lat, lon) == 0) {
  // GPS AVAILABLE
    gpsLEV = 3;
  } else {
  // GPS UNAVAILABLE
    if (gpsLEV > 0)
      gpsLEV--;
  } 

  // 3G Check
  if (a3gs.getTime2(epoch) == 0)
  // 3G AVAILABLE
    a3gsAVL = true;
  else
  // 3G UNAVAILABLE
    a3gsAVL = false;
  a3gs.setLED(false);

  // Write DATA
  logFile = SD.open(csvfilename, FILE_WRITE);
  lcd.clear();
  if (gpsLEV == 3) {
    lcd.print("GPS GOOD");
    if (a3gsAVL == true) {
      a3gs.getTime(date, time);
      logFile.print(loopnum); logFile.print(","); logFile.print(time);
      lcd.setCursor(0,1); lcd.print("LOGGED> ");
      lcd.setCursor(5,1); lcd.print(time);
    } else {
      logFile.print(loopnum); logFile.print(","); logFile.print("unknown");
      lcd.setCursor(0,1); lcd.print("3G Network down");
    }
    logFile.print(","); logFile.print(lon); logFile.print(","); logFile.print(lat); logFile.print(","); logFile.print(temparature); logFile.print(","); logFile.print(humidity); logFile.print(","); logFile.print(brightness); logFile.print("\n");
    loopnum++;
  } else {
    if (gpsLEV == 2) {
      lcd.print("GPS LOST LV1");
    }
    if (gpsLEV == 1) {
      lcd.print("GPS LOST LV2");
    }
    if (gpsLEV == 0) {
      lcd.print("GPS LOST LV3");
    }
    if (gpsLEV == -1) {
      lcd.print("GPS warming up");
    }
    if (a3gsAVL == true) {
      a3gs.getTime(date, time);
      lcd.setCursor(0,1); lcd.print("UPDATE> ");
      lcd.setCursor(8,1); lcd.print(time);
    } else {
      lcd.setCursor(0,1); lcd.print("3G Network down");
    }
  }
  logFile.close();
}






















void pict() {
  cam.reset();
  delay(1000);
  lcd.setCursor(0,0);
  if (!cam.takePicture()) {
    lcd.print("Failed to snap! ");
  } else {
    lcd.print("Picture taken!  ");

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

    // Read all the data up to # bytes!
    lcd.setCursor(15,0); lcd.write(0x7F);
    lcd.setCursor(0,0);
    uint16_t jpglen = cam.frameLength();
    byte wCount = 0; // For counting # of writes
    while (jpglen > 0) {
      // read 32 bytes at a time;
      uint8_t *buffer;
      uint8_t bytesToRead = min(32, jpglen);
      buffer = cam.readPicture(bytesToRead);
      imgFile.write(buffer, bytesToRead);
      if(++wCount >= 26) {
        lcd.write(0xFF);
        wCount = 0;
      }
      jpglen -= bytesToRead;
    }
    imgFile.close();
    lcd.setCursor(0,0); lcd.print("Picture saved.  ");
    
    
    // 3G SEND
    File myFile = SD.open(picfilename, FILE_READ);
    unsigned int filesize = myFile.size();
    estfsize = (float)filesize;
    unsigned int minfile = 0;
    while (minfile < (filesize-3))
      minfile = minfile + 3;
    //
    // HTTP POST
    if (a3gs.connectTCP(server, port) != 0) {
      lcd.setCursor(0,1); lcd.print("connectTCP[FAIL]");
      delay(2000);
    } else {
      // Send POST request
      lcd.setCursor(0,1); lcd.print("connectTCP  [OK]");
      delay(2000);
      // ->HEAD<-
      a3gs.write("POST /api/org/upload.php HTTP/1.1$n");
      a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
      a3gs.write("Content-Type: text/plain$n");
      a3gs.write("Content-Length: "); a3gs.write("20000"); a3gs.write("$n$n");
      // ->BODY<-
      a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
      a3gs.write("[DATE]"); a3gs.write(date);
      a3gs.write("[TIME]"); a3gs.write(time);
      a3gs.write("[TIMEZONE]9");
      a3gs.write("[TAG]#testdata");
      a3gs.write("[LOCATION]"); a3gs.write(lon); a3gs.write(" "); a3gs.write(lat);
      a3gs.write("[PLACE]Sony CSL");
      a3gs.write("[DATA]"); Serial.print("[DATA]");
      a3gs.write("TEMP=");  a3gs.write(temparature); a3gs.write(";");
      a3gs.write("HUMD=");  a3gs.write(humidity); a3gs.write(";");
      a3gs.write("BRGT=");  a3gs.write(brightness); a3gs.write(";");
      a3gs.write("[MIMETYPE]image/jpeg");
      if (!camAVL)
        goto _nocam;
      a3gs.write("[CONTENT]");


//------------------------------------------------------------------------
  // SOMEHOW Not running in case use repeat style format (e.g. for, while)
  // Fragment:
  //
  lcd.setCursor(0,0);
  while (fpos < minfile) {
    myFile.seek(fpos); buf[0] = myFile.peek(); fpos++;
    myFile.seek(fpos); buf[1] = myFile.peek(); fpos++;
    myFile.seek(fpos); buf[2] = myFile.peek(); fpos++;
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
    curr++;
    blinker = !blinker; a3gs.setLED(blinker);
    if (curr >= 100) {
      curb = curb + 300;
      curr = 0;
      float percentage = (float)curb / estfsize;
      lcd.clear(); lcd.print("Uploading..");
      lcd.setCursor(0,1); lcd.print(percentage*100); lcd.print("% / "); lcd.print(curb); lcd.print("B");
    }
  }//while (fpos < minfile)

  if (filesize - fpos == 2) { //when % = 2
    myFile.seek(fpos); buf[0] = myFile.peek(); fpos++;
    myFile.seek(fpos); buf[1] = myFile.peek(); fpos++;
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
  }

  if (filesize - fpos == 1) { //when % = 1
    myFile.seek(fpos); buf[0] = myFile.peek(); fpos++;
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
  }




    _nocam:
      myFile.close();
      a3gs.write("$n$n");
      a3gs.setLED(false);
      // Recieve responces
      //char res[a3gsMAX_RESULT_LENGTH+1];
      //while(( a3gs.read(res,a3gsMAX_RESULT_LENGTH+1)) > 0) {
      //  Serial.print(res);
      //}
      lcd.clear(); lcd.print("Disconnecting..");
      a3gs.disconnectTCP();
      delay(10);
//------------------------------------------------------------------------

  
    }
    return;
  }
}





















void setup() {
  pinMode(53, OUTPUT);
  pinMode(3, INPUT);
  lcd.begin(16, 2);
  lcd.print("HELLO THIS IS");
  lcd.setCursor(0,1); lcd.print("ARD-LogCam v1.2");
  //Serial.begin(9600);
  delay(3000);    // Wait for start serial monitor

 _retry:
  lcd.clear();
  // SD init
  if (!SD.begin(53)) {
    lcd.print("Please re-insert");
    lcd.setCursor(0,1); lcd.print("your microSD!");
    fail = 1;
    delay(3000);
    lcd.clear(); lcd.print("Retrying..");
    delay(5000);
    goto _retry;
    return;
  }
  lcd.print("SD card [OK]");
  fail = 0;
  delay(1000);

  // Camera init
  lcd.clear();
  if (cam.begin()) {
    lcd.print("Camera  [OK]");
    camAVL = true;
    cam.getVersion();
    delay(10);
    cam.setImageSize(VC0706_320x240);
  } else {
    lcd.print("No camera found");
    lcd.setCursor(0,1); lcd.print("Mode: Log-only");
    camAVL = false;
    delay(2000);
  }
  delay(1000);
  //if (SD.exists("gpslog.csv") == true) {
  //  SD.remove("gpslog.csv");
  //}

  lcd.clear(); lcd.print("3G initializing,");
  lcd.setCursor(0,1); lcd.print("Please wait.");
  if (a3gs.start() == 0 && a3gs.begin(0,115200) == 0) {
    lcd.clear(); lcd.print("3G Available");
    lcd.setCursor(0,1);
    if (a3gs.getTime2(epoch) == 0) {
      a3gs.getRSSI(rssi) == 0;
      lcd.print("RSSI = "); lcd.print(rssi); lcd.print(" dBm");
    } else {
      lcd.println("Can't get TIME");
      fail = 2;
    }
  } else {
    lcd.clear(); lcd.print("3G Unavailable");
    fail = 3;
  }
  delay(3000);

  // make filename
  a3gs.getTime(date, time);
  strcpy(csvfilename, "01234567.CSV");
  csvfilename[0] = date[2];
  csvfilename[1] = date[3];
  csvfilename[2] = date[5];
  csvfilename[3] = date[6];
  csvfilename[4] = date[8];
  csvfilename[5] = date[9];
  csvfilename[6] = '_';
  for (int i = 0; i < 100; i++) {
    csvfilename[7] = 'A' + i;
    // create if does not exist, do not open existing, write, sync after write
    if (! SD.exists(csvfilename))
      break;
  }
  lcd.clear(); lcd.print("Logfile created:");
  lcd.setCursor(0,1); lcd.print(csvfilename);
  delay(3000);
  lcd.clear(); lcd.print("GPS initializing");
}





















void loop() {
  if (fail == 0) {
    envmajor();
    // Self interrupt to take photo 1
    for (int i = 0; i < 10; i++) {
      if (digitalRead(3) == HIGH)
        pict();
      delay(500);
    }
    location();
    // Self interrupt to take photo 2
    for (int i = 0; i < 10; i++) {
      if (digitalRead(3) == HIGH)
        pict();
      delay(500);
    }
    
    
    
  } else {
    lcd.clear(); lcd.print("INIT ERROR");
    lcd.setCursor(0,1); lcd.print("CODE = "); lcd.print(fail);
  }
}




// EOF
