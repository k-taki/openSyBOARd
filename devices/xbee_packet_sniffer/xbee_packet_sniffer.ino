#include <SoftwareSerial.h>


// XBee SoftwareSerial
SoftwareSerial xbee(8, 9);


/*############### ATOP ###############*/
void ATOP() {
  uint8_t cmd[] = {0x7E,0x00,0x04,0x08,0x01,0x4F,0x50,0x57};
  uint8_t res[16];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=16; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  Serial.print(res[8],HEX);  Serial.print("-");
  Serial.print(res[9],HEX);  Serial.print("-");
  Serial.print(res[10],HEX); Serial.print("-");
  Serial.print(res[11],HEX); Serial.print("-");
  Serial.print(res[12],HEX); Serial.print("-");
  Serial.print(res[13],HEX); Serial.print("-");
  Serial.print(res[14],HEX); Serial.print("-");
  Serial.print(res[15],HEX);
}
/*############### ATSL ###############*/
void ATSL() {
  uint8_t cmd[] = {0x7E,0x00,0x04,0x08,0x01,0x53,0x4C,0x57};
  uint8_t res[16];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=16; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  Serial.print(res[8],HEX);  Serial.print("-");
  Serial.print(res[9],HEX);  Serial.print("-");
  Serial.print(res[10],HEX); Serial.print("-");
  Serial.print(res[11],HEX);
}
/*############### ATCB ###############*/
void ATCB() {
  uint8_t cmd[] = {0x7E,0x00,0x05,0x08,0x01,0x43,0x42,0x01,0x70};
  uint8_t res[16];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=16; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  Serial.print(res[7],HEX);
}
/*############### ATMY ###############*/
void ATMY() {
  uint8_t cmd[] = {0x7E,0x00,0x04,0x08,0x01,0x4D,0x59,0x50};
  uint8_t res[16];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=16; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  //Serial.print("********");
  Serial.print(res[8],HEX);  Serial.print("-");
  Serial.print(res[9],HEX);
}














void setup() {
  Serial.begin(9600);
  xbee.begin(9600);
  delay(1000);
  Serial.println("*HELO, This is XBee Packet Sniffer for Syneco.*");
  delay(2000);
  Serial.print("I am 0013A200+"); ATSL(); Serial.println();
  Serial.print("PAN ID: "); ATOP(); Serial.println();
  Serial.print("ATCB = "); ATCB(); Serial.println();
  Serial.print("My 16bit Addr: "); ATMY(); Serial.println();

  
}


void loop() {

}





