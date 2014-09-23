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
	time.sleep(3)
	print time.strftime("%H:%M:%S ") + "Data recieving..."
	for l in range(100):
		packet = ars.read(512)
		f.write(packet)
		#print(packet)
	f.close()
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








#Arduino's port opening & Check
ars = serial.Serial('/dev/ttyUSB4', baudrate=38400, timeout=1, writeTimeout=1)
time.sleep(5)
print time.strftime("%H:%M:%S ") + ars.read(160)

#Snapshot!
while True :
	cur = time.strftime("%I%M%S")
	if cur == "000000" or cur == "030000" or cur == "060000" or cur == "090000" :
		jpgfilename = time.strftime("http://kaz.fam.cx/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
		takentime = time.strftime("%H:%M:%S")
		snapshot()
		mail("RA-Synecoculture@googlegroups.com", "Livecam mail from arduino!", "Our arduino has just taken a photo at " + takentime + ". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
		mail("taki-taki-tackie@docomo.ne.jp", "Livecam mail from arduino!", "Our arduino has just taken a photo at "+takentime+". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
	if time.strftime("%M%S") == "0000" :
		jpgfilename = time.strftime("http://kaz.fam.cx/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
		takentime = time.strftime("%H:%M:%S")
		snapshot()
		mail("taki-taki-tackie@docomo.ne.jp", "Livecam mail from arduino!", "Our arduino has just taken a photo at " + takentime + ". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
	if time.strftime("%S") == "00" :
		snapshot()
	time.sleep(1)

#Port closing
ars.close()

#EOF
