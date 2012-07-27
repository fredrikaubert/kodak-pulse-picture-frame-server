# What
A kodak pulse server simulator that will allow you to show the content of a picture rss 
feed. This is based on the work of [Hajo Noerenberg](http://www.noerenberg.de/hajo/pub/kodak-pulse-picture-frame-server.php.txt)
and the proof-of-consept server. I've just adapted the code to download images from an
rss feed. 

# Why
The standard Kodak service allows you to send send images to the frame. But it does not
allow you to limit the number of photos to say the latest 10 photos. After sending a lot
of photos to the picture frame the new photos kind of drown in the number of old photos. 

# Requirements
This is not for everyone. You will need an Apache Web Server, and control of your 
dns/firewall. 

# Disclaimer
This might not be good for your frame. This is still kind of experimental and you should 
know your way around apache configuration and dns/firewall configuration. The solution 
turned out very good for me, but I take no responsibility for your picture frame and the
damage this may do to it. 

# Installation

The installation procedure is written from memory after doing a lot of trial and failure.
It might not be accurate. If you try it and find any error or other improvements to make
the job easier, please let me know (or send a pull request). 

## 1. Configure you Apache server
This is what I did on my Ubuntu Server. It might be different on your system, but it still
might push you in the right direction.

a. Generate a ssl certificate and install it in Apache webserver

	openssl genrsa -des3 -out server.key 2048
	openssl req -new -key server.key -out server.csr
	openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
	openssl rsa -in server.key -out server.key.insecure

	mv server.key server.key.secure
	mv server.key.insecure server.key

	mkdir /etc/apache2/ssl
	cp server.crt /etc/apache2/ssl
	cp server.key /etc/apache2/ssl

b. Create a site to handle the kodak trafic:
at  /etc/apache2/sites-available/kodak-pulse 

	<VirtualHost device.pulse.kodak.com:80>
		ServerAdmin webmaster@localhost
	#	RewriteRule /DeviceRest.* /kodak-pulse-picture-frame-server.php
		RewriteEngine  on
		DocumentRoot /var/www/kodak
		<Directory />
			Options FollowSymLinks
			AllowOverride All
		</Directory>
		<Directory /var/www/>
			Options Indexes FollowSymLinks MultiViews
			AllowOverride All
			Order allow,deny
			allow from all
		</Directory>	

		ScriptAlias /cgi-bin/ /usr/lib/cgi-bin/
		<Directory "/usr/lib/cgi-bin">
			AllowOverride All
			Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
			Order allow,deny
			Allow from all
		</Directory>

		ErrorLog ${APACHE_LOG_DIR}/error.log

		# Possible values include: debug, info, notice, warn, error, crit,
		# alert, emerg.
		LogLevel warn

		CustomLog ${APACHE_LOG_DIR}/access.log combined

	    Alias /doc/ "/usr/share/doc/"
   	 <Directory "/usr/share/doc/">
   	     Options Indexes MultiViews FollowSymLinks
   	     AllowOverride None
   	     Order deny,allow
   	     Deny from all
   	     Allow from 127.0.0.0/255.0.0.0 ::1/128
   	 </Directory>

	</VirtualHost>

Do the simular thing for handling https, referencing the previosly generated certificate
	SSLCertificateFile    /etc/apache2/ssl/server.crt
	SSLCertificateKeyFile /etc/apache2/ssl/server.key


c. Install the project files in the 
	/var/www/kodak
directory. Remember to change $rss_url to your favorite rss feed. I've created a pipe to
inverse the order of the pictures in a picasa rss feed and to limit the number of photos. 
See [http://pipes.yahoo.com/pipes/pipe.info?_id=d378e12925ff63939c05ce3eda60a8f0](http://pipes.yahoo.com/pipes/pipe.info?_id=d378e12925ff63939c05ce3eda60a8f0). 

You should add 
	&max-result=999999&imgmax=800 
to your picasa rss feed, to ensure that you don't miss any picture and to ensure that the
resolution of the picture fit your frame. Otherwise the images will not look all that 
crisp. If you have better resolution you should change the last value to match your resolution,
e.g. 1024. 
 

## 2. Configure your dns server to redirect requests to your server:
	device.pulse.kodak.com
	www.kodak.com

How this is done depends or your firewall/router, so you're on your own. 

## 3. Downgrade the firmware on your frame. 
The old firmware did not validate the ssl certificate, which is what enables this hack 
to work in the first place. This security flaw seems to be fixed with the latest firmware(?),
at least I cannot get it to work. Luckily for us, the old firmware is still available for
download and by telling the frame to download the old version it should (e.g. the "update"
file). I did this by downloading the file manually and and redirecting download.kodak.com 
to my server and hosting the file there, but I don't thing that is really necessary, so 
I left if out of the installation instructions. 

This step is obviously not needed if you have not upgraded your frame and have the old
firmware all ready. 

Go into the picture frame and do a check for an upgrade. If everything is set up correctly
the frame should check www.kodak.com/go something, which is in fact redirected to your 
server and hitting the update file, which will trick the frame into believing that the 
old firmware is in fact an upgrade. The frame will start the download process and after
a while it will be done and ready to hit our very own server.


## 4. Connect to the server and see your rss feed on the frame

Now, just boot your frame and see your rss feed. You might have to reset your frame
to factory settings (keep both buttons in the back pressed at the same time). You should
accept the settings suggested on the start page (with an emailadress and stuff). 

## 5. Picasa 
Use the rss feed for the dropbox album in picasaweb. Use the above mentioned pipe. You
can configure picasa to accept emails sent to a dedicated mailaddress, and put 
attached photos in the dropbox album. 

And voila, you have gone through all this trouble just to essentially have the same 
solution as Kodak provides, only that you now have the possibility to show only the latest
pictures. If only Kodak would add this functionality. 