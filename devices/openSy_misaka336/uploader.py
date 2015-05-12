#! /usr/bin/env python

import serial
import time
import shutil
import urllib3
from urllib3 import PoolManager
import os
import sys


def dataup():
	tp = 'c'
	ars.write(tp)
	print time.strftime("%H:%M:%S ") + "Data recieving from device..."
	postdata = ars.read(512)
	cnt = 0
	while postdata == '' : 
		print "ERROR : Retrying..."
		ars.write(tp)
		postdata = ars.read(512)
		cnt = cnt + 1
		if cnt >= 6 :
			break
		time.sleep(5)
	
	print "String = " + postdata
	ars.flush()
	print time.strftime("%H:%M:%S ") + "Uploading..."
	url = "http://54.249.238.92/api/org/upload.php"
	pool = urllib3.PoolManager()
	print pool.urlopen('POST',url,headers={'Content-Type':'text/plain'},body=postdata)
	print time.strftime("%H:%M:%S ") + "Ready."
	time.sleep(1)
	
	
def dget():
	tp = 'c'
	ars.write(tp)
	print time.strftime("%H:%M:%S ") + "Data recieving from device..."
	postdata = ars.read(1024)
	print "String = " + postdata
	ars.flush()
	print time.strftime("%H:%M:%S ") + "Ready."
	time.sleep(1)
	


#-----------------------------------------------------------------------------------

# Arduino's port opening & Check
ars = serial.Serial('/dev/ttyUSB0', baudrate=38400, timeout=1, writeTimeout=1)
#print time.strftime("%H:%M:%S ") + "Test connection."
#dget()


'''
# Snapshot!
while True :
	# Normal dataupload
	if time.strftime("%M%S") == "0000" :
		dataup()
		
	# Read motion detect
	if ars.read(1) == 'w' :
		print time.strftime("%H:%M:%S ") + "Motion detected!"
		dataup()
		
	time.sleep(1)
'''     
# USE CRONTAB (Run per a hour)
dataup()


# Port closing and exit program
ars.close()
sys.exit()

# EOF #