#Coding: utf-8
import serial
import time
import datetime
import shutil
import smtplib 
from email.MIMEMultipart import MIMEMultipart 
from email.MIMEBase import MIMEBase 
from email.MIMEText import MIMEText 
from email import Encoders 
import os

gmail_user = "camduino@gmail.com"
gmail_pwd = "synecoculture"


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




#Send mail!
while True :
	cur = time.strftime("%H%M%S")
	picturefile = "/var/www/uploaded_files/latest.jpg"
	stat = os.stat(picturefile)
	last_modified = stat.st_mtime
	dt = datetime.datetime.fromtimestamp(last_modified)
	pictdatestamp = dt.strftime("%Y-%m-%d %H:%M:%S")

	if cur == "063000" or cur == "073000" :
#		jpgfilename = time.strftime("http://kaz.fam.cx/photo/%Y-%m-%d/%H00-%H59/photo%Y%m%d%H%M%S.jpg")
#		takentime = time.strftime("%H:%M:%S")
#		mail("RA-Synecoculture@googlegroups.com", "Livecam mail from arduino!", "Our arduino has just taken a photo at " + takentime + ". This file is saved as " + jpgfilename + " .", "/var/www/photo/latest.jpg")
		mail("chasseurs-sans-frontieres@googlegroups.com", "Livecam mail from Camduino3G!", "APPENDED PHOTO WAS TAKEN AT " +pictdatestamp+ ".\n\n--\nThank you to cooperate for Camduino3G field test in Oiso!", picturefile)
#	if time.strftime("%M%S") == "0030" :
#		mail("taki-taki-tackie@docomo.ne.jp", "Livecam mail from Camduino3G!", "APPENDED PHOTO WAS TAKEN AT " +pictdatestamp+ ".\n\n--\nThank you to cooperate for Camduino3G field test in Oiso!", picturefile)
		
	time.sleep(1)


#EOF
