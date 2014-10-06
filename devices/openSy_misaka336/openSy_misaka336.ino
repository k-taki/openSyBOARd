// ONLY USE FOR ARDUINO UNO
// PLEASE CHECK COMPILE OPTION

#include <SPI.h>
#define DHTport 19 // Analog5
const char *ACCESSKEY = "c8113efb97a6c9d4dcc6f1f1621d2bf84b2e0023";
boolean blinker = true;
float a0,b1,b2,c12;
unsigned long Press,Temp; 






/*
--- Standard I/O pins allocation ---

D0    Serial RX (XBee)
D1    Serial TX (XBee)
D2
D3
D4
D5
D6
D7    Pressure Sensor Vcc (When needed ON)
D8    Status Red LED
D9    CdS cell Vcc (Always ON)
D10   SPI CS
D11   SPI MOSI
D12   SPI MISO
D13   SPI SCK
A0    Moisture sensor
A1    CdS cell voltage input
A2
A3
A4
A5    DHT11 input

*/





void DHTInit(void) {
	pinMode(DHTport,INPUT);
	delay(250);
}
byte DHTTSSeq(void) {
	byte dht11_in;
	// start condition
	// 1. pull-down i/o pin from 18ms
	digitalWrite(DHTport, LOW);
	pinMode(DHTport,OUTPUT);
	delay(30);
	pinMode(DHTport,INPUT);
	delayMicroseconds(20+40);	// High 20us + Slave ACK 80us/2

	dht11_in = digitalRead(DHTport);	// normal = LOW
	if(dht11_in){
		Serial.println("dht start condition 1 not met");
		return(1);
	}
	delayMicroseconds(80);
	dht11_in = digitalRead(DHTport);	// normal = HIGH
	if(!dht11_in){
		Serial.println("dht start condition 2 not met");
		return(1);
	}
	while( digitalRead(DHTport) );		// wait LOW
	return(0);
}
byte read_dht11_dat(){
	byte i = 0;
	byte result=0;
	for(i=0; i< 8; i++){
		while( !digitalRead(DHTport) ); // wait High
		delayMicroseconds(49);			// 28us or 70us 
		if( digitalRead(DHTport) ){
			result |=(1<<(7-i));
			while( digitalRead(DHTport) ); // wait '1' finish
		}
	}
	return result;
}
// DHT ACK
void DHT_ACK(void) {
	while( digitalRead(DHTport) );
	delayMicroseconds(50);
}
// DHT dataget
void DHT_GET(void) {
	byte dht11_dat[5];
	byte dht11_check_sum;
	byte i;
	float hum,temp;
	
	DHTInit();
	DHTTSSeq();
	for (i=0; i<5; i++) dht11_dat[i] = read_dht11_dat();
	DHT_ACK();
	dht11_check_sum = dht11_dat[0]+dht11_dat[1]+dht11_dat[2]+dht11_dat[3];
	if(dht11_dat[4]!= dht11_check_sum){
		Serial.println("DHT11 checksum error");
	}




	temp =  (float)dht11_dat[2];
	Serial.print("TEMP=");
	Serial.print( temp , 1 );
	Serial.print(";");
	
	hum =  (float)dht11_dat[0];
	Serial.print("HUMD=");
	Serial.print( hum , 1 );
	Serial.print(";");
}












// Memory map
void CoefficientRead() {
     unsigned int h,l;
     digitalWrite(10,LOW);               // SS(CS)ラインをLOWにする
     // ａ０の係数を得る
     SPI.transfer(0x88);                 // a0(MSB:HIGH byte)係数
     h = SPI.transfer(0x00);
     SPI.transfer(0x8a);                 // a0(LSB:LOW byte) 係数
     l = SPI.transfer(0x00);
     a0 = (h << 5) + (l >> 3) + (l & 0x07) / 8.0 ;

     // ｂ１の係数を得る
     SPI.transfer(0x8c);                 // b1(MSB:HIGH byte)係数
     h = SPI.transfer(0x00);
     SPI.transfer(0x8e);                 // b1(LSB:LOW byte) 係数
     l = SPI.transfer(0x00);
     b1 = ( ( ( (h & 0x1F) * 0x100 ) + l ) / 8192.0 ) - 3 ;

     // ｂ２の係数を得る
     SPI.transfer(0x90);                 // b2(MSB:HIGH byte)係数
     h = SPI.transfer(0x00);
     SPI.transfer(0x92);                 // b2(LSB:LOW byte) 係数
     l = SPI.transfer(0x00);
     b2 = ( ( ( ( h - 0x80) << 8 ) + l ) / 16384.0 ) - 2 ;

     // Ｃ１２の係数を得る
     SPI.transfer(0x94);                 // c12(MSB:HIGH byte)係数
     h = SPI.transfer(0x00);
     SPI.transfer(0x96);                 // c12(LSB:LOW byte) 係数
     l = SPI.transfer(0x00);
     c12 = ( ( ( h * 0x100 ) + l ) / 16777216.0 )  ;

     SPI.transfer(0x00);
     digitalWrite(10,HIGH);              // SS(CS)ラインをHIGHにする
}
// Read Pressure value
void PressureRead() {
     unsigned int h,l;
     // 圧力および温度の変換を開始させる
     digitalWrite(10,LOW);               // SS(CS)ラインをLOWにする
     SPI.transfer(0x24);                 // 0x24コマンドの発行(圧力と温度の変換)
     SPI.transfer(0x00);
     digitalWrite(10,HIGH);              // SS(CS)ラインをHIGHにする
     delay(3);                           // 変換完了まで３ｍｓ待つ
     digitalWrite(10,LOW);               // SS(CS)ラインをLOWにする
     // 圧力のＡ／Ｄ変換値を得る
     SPI.transfer(0x80);                 // 圧力(MSB:HIGH byte)
     h = SPI.transfer(0x00);
     SPI.transfer(0x82);                 // 圧力(LSB:LOW byte)
     l = SPI.transfer(0x00);
     Press = ( ( h * 256 ) + l ) / 64 ;

     // 温度のＡ／Ｄ変換値を得る
     digitalWrite(10,LOW);               // SS(CS)ラインをLOWにする
     SPI.transfer(0x84);                 // 温度(MSB:HIGH byte)
     h = SPI.transfer(0x00);
     SPI.transfer(0x86);                 // 温度(LSB:LOW byte)
     l = SPI.transfer(0x00);
     Temp = ( ( h * 256 ) + l ) / 64 ;

     SPI.transfer(0x00);
     digitalWrite(10,HIGH);              // SS(CS)ラインをHIGHにする
}
// Calcurate pressure value
float PressureCalc() {
     float ret,f;
     f = a0 + ( b1 + c12 * Temp ) * Press + b2 * Temp ;
     ret = f * ( 650.0 / 1023.0 ) + 500.0 ;
     return ret;
}

















void ACCMES(void) {
        Serial.print("[ACCESSKEY]");
        Serial.print(ACCESSKEY);
}

void CdS(void) {
        Serial.print("BRGT=");
        Serial.print(analogRead(1));
        Serial.print(";");
}

void Moi(void) {
        Serial.print("MOIS=");
        Serial.print(analogRead(0));
        Serial.print(";");
}

void PreS(void) {
        digitalWrite(7,HIGH); // Sensor ON
        delay(50);
        SPI.begin();
        SPI.setBitOrder(MSBFIRST);
        SPI.setClockDivider(SPI_CLOCK_DIV2);
        SPI.setDataMode(SPI_MODE0);
        delay(50);
        CoefficientRead();
        
        int i;
        float ans = 0.0;
        unsigned long p = 0;
        unsigned long t = 0;
        for (i=0 ; i < 30 ; i++) {
          PressureRead();
          p = p + Press ;
          t = t + Temp ;
          delay(1);
        }
        Press = p / 30 ;
        Temp  = t / 30 ;
        ans = PressureCalc();
        Serial.print("PRES=");
        Serial.print(ans);
        Serial.print(";");
        SPI.end();
        delay(10);
        digitalWrite(7,LOW); // Sensor OFF
}





//---------------------------------------------------------------
















void setup() {
	Serial.begin(38400);
        pinMode(8,OUTPUT);    // LED
        digitalWrite(8,LOW);
        pinMode(9,OUTPUT);    // CdS Cell Vcc
        digitalWrite(9,HIGH);
        pinMode(7,OUTPUT);    // Pressure Sensor Vcc
        digitalWrite(7,LOW);        
}



void loop() {
    if (analogRead(1) <= 300) { // Brightness blink
        digitalWrite(8,blinker);
        blinker = !blinker;
    } else {
        digitalWrite(8,LOW);
    }
    delay(1000);
}



void serialEvent() {
    char mess = Serial.read();
    // ALL DATA GET
    if (mess == 'c') {
        digitalWrite(8,LOW);
        // KEY
        ACCMES();
        digitalWrite(8,HIGH); delay(100);
        // DATA
        Serial.print("[DATA]");
        DHT_GET();
        digitalWrite(8,LOW); delay(100);
        CdS();
        digitalWrite(8,HIGH); delay(100);
        Moi();
        digitalWrite(8,LOW); delay(100);
        PreS();
        digitalWrite(8,HIGH); delay(100); digitalWrite(8,LOW);
    }
    // ONLY ACCESSKEY
    if (mess == 'k') {
        ACCMES();
    }
}
