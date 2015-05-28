#include <Adafruit_VC0706.h>
#include <SPI.h>
#include <SD.h>
#include <SoftwareSerial.h>         
#define chipSelect 4
SoftwareSerial cc = SoftwareSerial(2, 3);
Adafruit_VC0706 cam = Adafruit_VC0706(&cc);






void setup() {
  pinMode(4, OUTPUT); // SDCS
  pinMode(10, OUTPUT); // LED
  digitalWrite(10, LOW);


  Serial.begin(38400);
  Serial.println("VC0706 Camera test");
  
  // see if the card is present and can be initialized:
  if (!SD.begin(chipSelect)) {
    Serial.println("Card failed, or not present");
    // don't do anything more:
    return;
  }  
  
  // Try to locate the camera
  if (cam.begin()) {
    Serial.println("Camera Found:");
  } else {
    Serial.println("No camera found?");
    return;
  }
  // Print out the camera version information (optional)
  char *reply = cam.getVersion();
  if (reply == 0) {
    Serial.print("Failed to get version");
  } else {
    Serial.println("-----------------");
    Serial.print(reply);
    Serial.println("-----------------");
  }

  // Set the picture size - you can choose one of 640x480, 320x240 or 160x120 
  // Remember that bigger pictures take longer to transmit!
  
  //cam.setImageSize(VC0706_640x480);        // biggest
  cam.setImageSize(VC0706_320x240);        // medium
  //cam.setImageSize(VC0706_160x120);          // small

  // You can read the size back from the camera (optional, but maybe useful?)
  uint8_t imgsize = cam.getImageSize();
  Serial.print("Image size: ");
  if (imgsize == VC0706_640x480) Serial.println("640x480");
  if (imgsize == VC0706_320x240) Serial.println("320x240");
  if (imgsize == VC0706_160x120) Serial.println("160x120");


  //  Motion detection system can alert you when the camera 'sees' motion!
  cam.setMotionDetect(true);           // turn it on
  //cam.setMotionDetect(false);        // turn it off   (default)

  // You can also verify whether motion detection is active!
  Serial.print("Motion detection is ");
  if (cam.getMotionDetect()) 
    Serial.println("ON");
  else 
    Serial.println("OFF");
}




void loop() {
 //
 if (Serial.read() == 't') {
   cam.setMotionDetect(false);
   delay(1000);
   
  if (! cam.takePicture()) 
    Serial.println("0000000000000000");
  

  uint16_t jpglen = cam.frameLength();
  while (jpglen > 0) {
    uint8_t *buffer;
    uint8_t bytesToRead = min(64, jpglen);
    buffer = cam.readPicture(bytesToRead);
    Serial.write(buffer, bytesToRead);
    jpglen -= bytesToRead;
  }
  cam.resumeVideo();
  cam.setMotionDetect(true);
 }
 //
 if (cam.motionDetected()) {
   Serial.print("m");
 }
 //
  if (analogRead(0) >= 675) {
   delay(50); // chattering
    if (analogRead(0) >= 675) {
     Serial.print("p");
     digitalWrite(10, HIGH); delay(200);
     digitalWrite(10, LOW); delay(200);
     digitalWrite(10, HIGH); delay(200);
     digitalWrite(10, LOW);
   }
 }
 delay(1);
}







