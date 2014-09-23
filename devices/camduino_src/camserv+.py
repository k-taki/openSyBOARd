#Coding: utf-8
import serial
import time
import shutil
import smtplib 
from email.MIMEMultipart import MIMEMultipart 
from email.MIMEBase import MIMEBase 
from email.MIMEText import MIMEText 
from email import Encoders 
import os
import sys

gmail_user = "camduino@gmail.com"
gmail_pwd = "synecoculture"





def snapshot():
	currentpath = time.strftime("/var/www/photo/%Y-%m-%d")
	if os.path.isdir(currentpath) == False :
		os.mkdir(currentpath)
	currentpath = time.strftime("/var/www/photo/%Y-%m-%d/%H00-%H59")
	if os.path.isdir(currentpath) == False :
		os.mkdir(currentpath)
	jpgfilename = time.strftime("/var/www/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
	f = open(jpgfilename,'w',51200)
	tp = 't'
	ars.write(tp)
	#time.sleep(3)
	print time.strftime("%H:%M:%S ") + "Data recieving..."
	for l in range(50):
		packet = ars.read(1024)
		f.write(packet)
		#print(packet)
	f.close()
	ars.flush()
	lpictimest = os.stat("/var/www/photo/l-4.jpg").st_mtime
	shutil.copyfile("/var/www/photo/l-4.jpg", "/var/www/photo/l-5.jpg")
	os.utime("/var/www/photo/l-5.jpg", (lpictimest, lpictimest))
	lpictimest = os.stat("/var/www/photo/l-3.jpg").st_mtime
	shutil.copyfile("/var/www/photo/l-3.jpg", "/var/www/photo/l-4.jpg")
	os.utime("/var/www/photo/l-4.jpg", (lpictimest, lpictimest))
	lpictimest = os.stat("/var/www/photo/l-2.jpg").st_mtime
	shutil.copyfile("/var/www/photo/l-2.jpg", "/var/www/photo/l-3.jpg")
	os.utime("/var/www/photo/l-3.jpg", (lpictimest, lpictimest))
	lpictimest = os.stat("/var/www/photo/l-1.jpg").st_mtime
	shutil.copyfile("/var/www/photo/l-1.jpg", "/var/www/photo/l-2.jpg")
	os.utime("/var/www/photo/l-2.jpg", (lpictimest, lpictimest))
	lpictimest = os.stat("/var/www/photo/latest.jpg").st_mtime
	shutil.copyfile("/var/www/photo/latest.jpg", "/var/www/photo/l-1.jpg")
	os.utime("/var/www/photo/l-1.jpg", (lpictimest, lpictimest))
	shutil.copyfile(jpgfilename, "/var/www/photo/latest.jpg")
	print time.strftime("%H:%M:%S ") + "Snapshot has successfully taken!"
	print time.strftime("%H:%M:%S ") + "Ready."
	time.sleep(1)


def mail(to, subject, text, attach): 
	msg = MIMEMultipart() 

	msg['From'] = gmail_user 
	msg['To'] = to 
	msg['Subject'] = subject 

	msg.attach(MIMEText(text)) 

	part = MIMEBase('application', 'octet-stream') 
	part.set_payload(open(attach, 'rb').read()) 
	Encoders.encode_base64(part) 
	part.add_header('Content-Disposition', 'attachment; filename="%s"' % os.path.basename(attach))
	msg.attach(part)
         
	mailServer = smtplib.SMTP("smtp.gmail.com", 587) 
	mailServer.ehlo() 
	mailServer.starttls() 
	mailServer.ehlo() 
	mailServer.login(gmail_user, gmail_pwd) 
	mailServer.sendmail(gmail_user, to, msg.as_string()) 
	# Should be mailServer.quit(), but that crashes... 
	mailServer.close()
	print time.strftime("%H:%M:%S ") + "Mail sent to " + to
	time.sleep(1)

#-----------------------------------------------------------------------------------








# Arduino's port opening & Check
ars = serial.Serial('/dev/ttyUSB0', baudrate=38400, timeout=1, writeTimeout=1)
time.sleep(5)
print time.strftime("%H:%M:%S ") + ars.read(160)

# Snapshot!
while True :
	cur = time.strftime("%I%M")
	
	if cur == "0130" or cur == "0430" or cur == "0730" or cur == "1030" :
		jpgfilename = time.strftime("http://kaz.fam.cx/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
		takentime = time.strftime("%H:%M:%S")
		snapshot()
		#mail("RA-Synecoculture@googlegroups.com", "Livecam mail from arduino!", "Our arduino has just taken a photo at " + takentime + ". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
		#mail("taki-taki-tackie@docomo.ne.jp", "Livecam mail from arduino!", "Our arduino has just taken a photo at "+takentime+". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
		time.sleep(30)
	
	'''
	if time.strftime("%M%S") == "0000" :
		jpgfilename = time.strftime("http://kaz.fam.cx/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
		takentime = time.strftime("%H:%M:%S")
		snapshot()
		#mail("taki-taki-tackie@docomo.ne.jp", "Livecam mail from arduino!", "Our arduino has just taken a photo at " + takentime + ". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
	if time.strftime("%S") == "00" :
		snapshot()
	'''
	
	# Read motion detect
	if ars.read(1) == 'w' :
		print time.strftime("%H:%M:%S ") + "Motion detected!"
		snapshot()

	time.sleep(1)




# Port closing
ars.close()

# EOF #
