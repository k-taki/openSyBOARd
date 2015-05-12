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
  //Serial.println("********");
  Serial.print(res[8],HEX);  Serial.print("-");
  Serial.print(res[9],HEX);
}
/*############### remoteATDB ###############*/
void ATDB() {
  uint8_t cmd[] = {0x7E,0x00,0x0F,0x17,0x01,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0xFF,0xFE,0x00,0x44,0x42,0x64};
  uint8_t res[20];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=20; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  //Serial.print("********");
  Serial.print("-"); Serial.print(res[18],DEC);
}
/*############### remoteATTP ###############*/
void ATTP() {
  uint8_t cmd[] = {0x7E,0x00,0x0F,0x17,0x01,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0xFF,0xFE,0x00,0x54,0x50,0x46};
  uint8_t res[21];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=21; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  //Serial.print("********");
  int val = res[18] * 0x0100 + res[19];
  Serial.print(val,DEC);
}
/*############### remoteAT%V ###############*/
void ATpcV() {
  uint8_t cmd[] = {0x7E,0x00,0x0F,0x17,0x01,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0xFF,0xFE,0x00,0x25,0x56,0x6F};
  uint8_t res[21];
  xbee.write(cmd,sizeof(cmd));
  delay(200);
  for (int i=0; i<=21; i++) {
    res[i] = xbee.read();
    //Serial.print(res[i],HEX);
    //Serial.print("-");
  }
  //Serial.print("********");
  uint8_t val = res[18] * 0x0100 + res[19];
  val = val / 0x0400; val = val * 0x04B0;
  Serial.print(val,DEC);
}





int cnt = 0;










void setup() {
  Serial.begin(9600);
  xbee.begin(9600);
  delay(3000);
  Serial.println("*HELO, This is XBee Packet Sniffer for Syneco.*");
  Serial.println("#################### INIT ####################");
  Serial.print("I am 0013A200+"); ATSL(); Serial.println();
  Serial.print("PAN ID: "); ATOP(); Serial.println();
  Serial.print("ATCB = "); ATCB(); Serial.println();
  Serial.println("#################### LOOP ####################");
  Serial.println("");
  delay(1000);
}


void loop() {
  Serial.print("#################### "); Serial.print(10 * cnt); Serial.println("sec ####################");
  Serial.print("My 16bit Addr: "); ATMY(); Serial.println();
  Serial.print("Coordinator RSSI: "); ATDB(); Serial.println("dBm");
  Serial.print("Coordinator Temparature: "); ATTP(); Serial.println("degC");
  Serial.print("Coordinator Volts: "); ATpcV(); Serial.println("mV");
  cnt++;
  delay(10000);
}





