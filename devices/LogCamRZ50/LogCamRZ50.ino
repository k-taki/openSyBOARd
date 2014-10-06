// ONLY USE FOR MEGA 2560

#include <SoftwareSerial.h>\
#include <SPI.h>
#include <SD.h>
#include <LiquidCrystal.h>
#include <a3gs2.h>
#include <Adafruit_VC0706.h>

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

*/





// initialize fail frag
boolean fail = false;

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

uint32_t epoch = 0;
char date[a3gsDATE_SIZE], time[a3gsTIME_SIZE];
char res[a3gsMAX_RESULT_LENGTH+1];
File logFile;
LiquidCrystal lcd(22, 23, 24, 25, 26, 27);
int rssi = -127;
Adafruit_VC0706 cam = Adafruit_VC0706(&Serial2);
volatile const uint8_t *b64table = (uint8_t *)"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
const char *server = "ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com";
const char *path = "/api/original/upload.php";
const int port = 80;
const char *ACCESSKEY = "302694d24e41fef85ce10a3677d1e0018b4c3074";
char lat[15], lon[15];
int len;
boolean blinker = false;
float estfsize;












void location() {
  a3gs.setLED(true);
  // GPS Check
  lcd.setCursor(0,1); lcd.print("Searching...    ");
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
  lcd.clear();
  if (gpsLEV == 3) {
    lcd.print("GPS GOOD.");
    if (a3gsAVL == true) {
      a3gs.getTime(date, time);
      logFile.print(loopnum); logFile.print(","); logFile.print(time);
      lcd.setCursor(0,1); lcd.print("LOG: ");
      lcd.setCursor(5,1); lcd.print(time);
    } else {
      logFile.print(loopnum); logFile.print(","); logFile.print("unknown");
      lcd.setCursor(0,1); lcd.print("3G Network down.");
    }
    logFile.print(","); logFile.print(lon); logFile.print(","); logFile.print(lat); logFile.print("\n");
    loopnum++;
  } else {
    if (gpsLEV == 2) {
      lcd.print("GPS LOST LV1.");
    }
    if (gpsLEV == 1) {
      lcd.print("GPS LOST LV2.");
    }
    if (gpsLEV == 0) {
      lcd.print("GPS LOST LV3.");
    }
    if (gpsLEV == -1) {
      lcd.print("GPS warming up,");
    }
    if (a3gsAVL == true) {
      a3gs.getTime(date, time);
      lcd.setCursor(0,1); lcd.print("UPDATE: ");
      lcd.setCursor(8,1); lcd.print(time);
    } else {
      lcd.setCursor(0,1); lcd.print("3G Network down.");
    }
  }
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
      lcd.setCursor(0,1); lcd.print("HTTP POST [FAIL]");
      delay(2000);
    } else {
      // Send POST request
      lcd.setCursor(0,1); lcd.print("HTTP POST [OK]  ");
      delay(2000);
      // ->HEAD<-
      a3gs.write("POST /api/original/upload.php HTTP/1.1$n");
      a3gs.write("HOST: "); a3gs.write(server); a3gs.write("$n");
      a3gs.write("Content-Type: text/plain$n");
      a3gs.write("Content-Length: "); a3gs.write("20000"); a3gs.write("$n$n");
      // ->BODY<-
      a3gs.write("[ACCESSKEY]"); a3gs.write(ACCESSKEY);
      a3gs.write("[DATE]"); a3gs.write(date);
      a3gs.write("[TIME]"); a3gs.write(time);
      a3gs.write("[TIMEZONE]9[TAG]#testdata");
      a3gs.write("[LOCATION]"); a3gs.write(lon); a3gs.write(" "); a3gs.write(lat);
      a3gs.write("[MIMETYPE]image/jpeg[CONTENT]");



//------------------------------------------------------------------------
  // SOMEHOW Not running in case use repeat style format (e.g. for, while)
  // Fragment:
  //
  unsigned int fpos = 0;
  unsigned int curr = 0;
  unsigned int curb = 0;
  uint8_t buf[3] = {0x00,0x00,0x00};
  uint8_t bin[4] = {0x00,0x00,0x00,0x00};
  lcd.setCursor(0,0);
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
    curr++;
    blinker = !blinker; a3gs.setLED(blinker);
    if (curr >= 100) {
      curb = curb + 300;
      curr = 0;
      float percentage = (float)curb / estfsize;
      lcd.clear();
      lcd.setCursor(0,0); lcd.print("Uploading...");
      lcd.setCursor(0,1);
      lcd.print(percentage*100);
      lcd.print("% / ");
      lcd.print(curb);
      lcd.print("B");
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
  }





      myFile.close();
      a3gs.write("$n$n");
      a3gs.setLED(false);
      // Recieve responces
      //char res[a3gsMAX_RESULT_LENGTH+1];
      //while(( a3gs.read(res,a3gsMAX_RESULT_LENGTH+1)) > 0) {
      //  Serial.print(res);
      //}
      lcd.clear(); lcd.print("Disconnecting...");
      a3gs.disconnectTCP();
      delay(2);
//------------------------------------------------------------------------

  
  }
  return;
}
}











void setup() {
  pinMode(53, OUTPUT);
  pinMode(3, INPUT);
  lcd.begin(16, 2);
  lcd.print("Ready.");
  //Serial.begin(9600);
  delay(3000);    // Wait for start serial monitor

 _retry:
  lcd.clear();
  // SD init
  if (!SD.begin(53)) {
    lcd.print("SD card failed!");
    fail = true;
    delay(3000);
    lcd.clear(); lcd.print("Retry...");
    delay(5000);
    goto _retry;
    return;
  }
  lcd.print("SD card OK.");
  delay(1000);

  // Camera init
  lcd.clear();
  if (cam.begin()) {
    lcd.print("Camera OK.");
    camAVL = true;
    cam.getVersion();
    delay(10);
    cam.setImageSize(VC0706_320x240);
  } else {
    lcd.print("No camera found!");
    camAVL = false;
  }
  delay(1000);
  //if (SD.exists("gpslog.csv") == true) {
  //  SD.remove("gpslog.csv");
  //}

  lcd.clear(); lcd.print("3G initialize...");
  if (a3gs.start() == 0 && a3gs.begin(0,115200) == 0) {
    lcd.clear(); lcd.print("3G Available.");
    lcd.setCursor(0,1);
    if (a3gs.getTime2(epoch) == 0) {
      a3gs.getRSSI(rssi) == 0;
      lcd.print("RSSI = ");
      lcd.print(rssi);
      lcd.print(" dBm.");
    } else {
      lcd.println("Epoch unknown.");
      fail = true;
    }
  } else {
    lcd.setCursor(0,1);
    lcd.print("Failed.");
    fail = true;
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
    if (! SD.exists(csvfilename)) {
      break;
    }
  }
  lcd.clear();
  lcd.print("Logfile created:");
  lcd.setCursor(0,1);
  lcd.print(csvfilename);
  delay(3000);
  lcd.clear();
  lcd.print("GPS initialize,");
}

















void loop() {
  if (fail == false) {
    logFile = SD.open(csvfilename, FILE_WRITE);
    location();
    logFile.close();
  } else {
    lcd.clear();
    lcd.print("INIT ERROR!!");
  }

  // Self interrupt to take photo
  for (int i = 0; i < 20; i++) {
    if (digitalRead(3) == HIGH)
      pict();
    delay(500);
  }
}




// EOF
