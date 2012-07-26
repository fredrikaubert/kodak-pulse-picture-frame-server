<?php

/**
 *
 * kodak-pulse-picture-frame-server.php V1.05
 *
 * Kodak Pulse Picture Frame Server (KCS Kodak Cloud Services) Emulator
 *
 * (C) Hajo Noerenberg 2010
 *
 * http://www.noerenberg.de/hajo/pub/kodak-pulse-picture-frame-server.php.txt
 *
 * Proof-of-concept code, you'll quickly get the idea about how the protocol works.
 *
 * Tested with a W730 model and firmware version '02/23/2010'.
 *
 * +++ WARNING +++
 * MODIFYING YOUR DEVICE WILL VOID YOUR WARRANTY! IT IS
 * POSSIBLE TO BRICK YOUR DEVICE! USE AT YOUR OWN RISK!
 * I AM NOT LIABLE FOR ANY DAMAGES TO YOUR SYSTEM OR
 * ANY LOSS OF DATA!
 *
 *
 * KODAK and PULSE are trademarks of Eastman Kodak Company, Rochester, NY 14650-0218.
 *
 *
 * - Setup
 *
 * 1.) Install the Apache Web Server, listening both on port 80 and 443 (SSL)
 *
 * 2.) Modify Apache'config to redirect all relevant requests to this PHP script. You
 *     can a.) modify the main config file or b.) set up a .htaccess file:
 *
 *     RewriteRule /DeviceRest.* /kodak-pulse-picture-frame-server.php
 *
 * 3.) Re-route all requests for device.pulse.kodak.com to your webserver. You
 *     can choose between two methods:
 *
 *     a.) Insert custom DNS entry for device.pulse.kodak.com in your DSL router:
 *         device.pulse.kodak.com <APACHE IP>
 *
 *     b.) DNAT traffic in your Linux-router:
 *         iptables -t nat -I PREROUTING -d device.pulse.kodak.com -p tcp --dport  80 -j DNAT --to <APACHE IP>
 *         iptables -t nat -I PREROUTING -d device.pulse.kodak.com -p tcp --dport 443 -j DNAT --to <APACHE IP>
 *
 * 4.) Switch on your photo frame.
 *
 *
 * - GUIDs/IDs used in this emulator (you do NOT have to change them!)
 *
 * ba538605-038e-b8ee-02c4-6925cad67189 = 'secret' Kodak API key
 * 55555555-deaf-dead-beef-555555555555 = device (picture frame) activation ID
 * 22222222-1234-5678-9012-123456789012 = user 'admin' profile ID
 * 13333337-1337-1337-1337-424242424242 = session auth token
 * 66666666-5555-3333-2222-222222222222 = user 'collection author' profile ID
 * 77777777-fefe-fefe-fefe-777777777777 = (picture) collection ID
 * 99999999-1111-2222-3333-420000000001 = entity (picture) ID (Example Pic 1)
 * 99999999-1111-2222-3333-420000000002 = entity (picture) ID (Example Pic 2)
 *
 * KCMLP012345678 = frame serial number (printed on the device)
 * NXV123456789 = activation code (printed on package)
 * 123789 = PIN (website activation)
 *
 *
 * - Security 
 *
 * There is a serious security issue with the official Kodak API Server (details
 * are not disclosed here). As of today, I strongly suggest not to
 * upload any personal data to Kodak's server.
 *
 *
 * - Download firmware image
 *
 * curl -v 'http://www.kodak.com/go/update?v=2010.02.23&m=W730&s=KCMLP012345678'
 * curl -v -O 'http://download.kodak.com/digital/software/pictureFrame/autoupdate_test/2010_09_06/Kodak_FW__Fuller.img'
 *
 * Documentation for the IMG file format: http://www.noerenberg.de/hajo/pub/amlogic-firmware-img-file-format.txt
 *
 * - Misc details
 *
 * The picture frame uses Amlogic's proprietary AVOS real-time operating system ('AVOS/1.1 libhttp/1.1'),
 * the MatrixSSL client lib and ZyDAS WLAN.
 *
 */

$r = $_SERVER['REQUEST_URI'];
$pull_intervall = 30; #seconds
$rss_url = 'http://pipes.yahoo.com/pipes/pipe.run?MaxResult=7&RSSFeed=https%3A%2F%2Fpicasaweb.google.com%2Fdata%2Ffeed%2Fbase%2Fuser%2F107135950873398578411%2Falbumid%2F5376824435897050881%3Falt%3Drss%26kind%3Dphoto%26authkey%3DGv1sRgCODokcb3-9C9LA%26hl%3Dno%26max-result%3D999999%26imgmax%3D800&_id=d378e12925ff63939c05ce3eda60a8f0&_render=rss';
$latest_picture_timestamp = getLatestUpdate($rss_url);
$latest_overall_timestamp = $latest_picture_timestamp;

$latest_picture_date = toDateString($latest_picture_timestamp);
$latest_overall_date = toDateString($latest_overall_timestamp);


$e = '<?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";

if ('/DeviceRest/activate' == $r) {

    /**
     *
     * Step 1: The picture frame connects to https://$deviceActivationURL and
     *         requests activation status and auth URL. Fortunately, the picture
     *         frame does not validate the SSL certificate's hostname.
     *
     * $deviceActivationURL is hardcoded into the firmware and thus
     * cannot be changed (at least, until someone decodes the fw image ;-))
     *
     * curl -v -k -d '<?xml version="1.0"? >
     *     <activationInfo>
     *         <deviceID>KCMLP012345678</deviceID>
     *         <apiVersion>1.0</apiVersion>
     *         <apiKey>ba538605-038e-b8ee-02c4-6925cad67189</apiKey>
     *         <activationCode>NXV123456789</activationCode>
     *     </activationInfo>'
     *     https://device.pulse.kodak.com/DeviceRest/activate
     *
     */


    if (1) { // always activated

        header('HTTP/1.1 412 Precondition Failed');

        print $e . '<activationResponseInfo>' .
                       '<deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>' .
                       '<deviceAuthorizationURL>https://device.pulse.kodak.com/DeviceRestV10/Authorize</deviceAuthorizationURL>' .
                       '<deviceProfileList>' .
                           '<admins>' .
                               '<profile>' .
                                   '<id>22222222-1234-5678-9012-123456789012</id>' .
                                   '<name>Firstname Lastname</name>' .
                                   '<emailAddress>user@mailprovider.com</emailAddress>' .
                               '</profile>' .
                           '</admins>' .
                       '</deviceProfileList>' .
                   '</activationResponseInfo>';

    } else {

        print $e . '<activationResponseInfo>' .
                       '<deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>' .
                       '<deviceAuthorizationURL>https://device.pulse.kodak.com/DeviceRestV10/Authorize</deviceAuthorizationURL>' .
                       '<consumerActivation>' . 
                           '<pin>123789</pin>' .
                           '<url>http://www.kodakpulse.com</url>' .
                       '</consumerActivation>' .
                       '<deviceProfileList><admins /></deviceProfileList>' .
                   '</activationResponseInfo>';

    }

    exit;

} elseif ('/DeviceRestV10/Authorize' == $r) {

    /**
     *
     * Step 2: The picture frame connects to $deviceAuthorizationURL (->Step 1) and
     *         requests auth token and API URL
     *
     * curl -v -k -d '<?xml version="1.0"? >
     *     <authorizationInfo>
     *         <deviceID>KCMLP012345678</deviceID>
     *         <deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>
     *         <deviceStorage>
     *             <bytesAvailable>447176504</bytesAvailable>
     *             <bytesTotal>448143360</bytesTotal>
     *             <picturesAvailable>4500</picturesAvailable>
     *             <picturesTotal>4500</picturesTotal>
     *         </deviceStorage>
     *     </authorizationInfo>'
     *     https://device.pulse.kodak.com/DeviceRestV10/Authorize
     *
     */

    if (1) { // always authorized

        print $e . '<authorizationResponseInfo>' .
                       '<authorizationToken>13333337-1337-1337-1337-424242424242</authorizationToken>' .
                       '<apiBaseURL>http://device.pulse.kodak.com/DeviceRestV10</apiBaseURL>' .
                       '<status>' .
                           "<overallStatus>$latest_overall_timestamp</overallStatus>" .
                           "<collectionStatus>$latest_picture_timestamp</collectionStatus>" .
                           '<settingsStatus>1287525781312</settingsStatus>' .
                           "<pollingPeriod>$$pull_intervall</pollingPeriod>" .
                       '</status>' .
                       '<deviceProfileList>' .
                           '<admins>' .
                               '<profile>' .
                                   '<id>22222222-1234-5678-9012-123456789012</id>' .
                                   '<name>Firstname Lastname</name>' .
                                   '<emailAddress>user@mailprovider.com</emailAddress>' .
                               '</profile>' .
                           '</admins>' .
                       '</deviceProfileList>' .
                   '</authorizationResponseInfo>';

    } else {

        header('HTTP/1.1 400 Bad Request');

    }

    exit;

}

/**
 *
 * Step 3++: The picture frame connects to $apiBaseURL (->Step 2) and
 *           requests device settings, collection status, ...
 *
 * The following functions are only available for picture frames with a
 * valid device (auth) token.
 *
 * curl -v -k -H 'DeviceToken: 13333337-1337-1337-1337-424242424242' <URL>
 *
 *     http://device.pulse.kodak.com/DeviceRestV10/status/0
 *     http://device.pulse.kodak.com/DeviceRestV10/status/1287591702353
 *     http://device.pulse.kodak.com/DeviceRestV10/settings
 *     http://device.pulse.kodak.com/DeviceRestV10/collection
 *     http://device.pulse.kodak.com/DeviceRestV10/profile/66666666-5555-3333-2222-222222222222
 *     http://device.pulse.kodak.com/DeviceRestV10/entity/99999999-1111-2222-3333-420000000001
 *     http://device.pulse.kodak.com/DeviceRestV10/entity/99999999-1111-2222-3333-420000000002
 *
 */

#if ('13333337-1337-1337-1337-424242424242' != $_SERVER['HTTP_DEVICETOKEN']) {
#
#    header('HTTP/1.1 424 Failed Dependency');
#    exit;

#}

if ('/DeviceRestV10/status/' == substr($r, 0, 22)) {

    $s = substr($r, 22);


    if ($latest_overall_timestamp != $s) {	// dummy mode: fixed serial, increment on change

        header('HTTP/1.1 425 Unordered Collection');
        print $e . '<status>' .
                       "<overallStatus>$latest_overall_timestamp</overallStatus>" .
                       "<collectionStatus>$latest_picture_timestamp</collectionStatus>" .
                       '<settingsStatus>1287525781312</settingsStatus>' .
                       "<pollingPeriod>$pull_intervall</pollingPeriod>" .
                   '</status>';
    }

} elseif ('/DeviceRestV10/settings' == $r) {

    print $e . '<deviceSettings>' .
                   '<name>My lovely Pulse Frame</name>' .
                   '<slideShowProperties>' .
                       '<duration>10</duration>' .
                       '<transition>FADE</transition>' .
                   '</slideShowProperties>' .
                   '<displayProperties>' . 
                       '<displayMode>ONEUP</displayMode>' .
                       '<showPictureInfo>false</showPictureInfo>' .
                       '<renderMode>FILL</renderMode>' .
                   '</displayProperties>' .
                   '<autoPowerProperties>' .
                       '<autoPowerEnabled>false</autoPowerEnabled>' .
                       '<wakeOnContent>true</wakeOnContent>' .
                       '<autoPowerTime autoType="ON">8:00:00</autoPowerTime>' .
                       '<autoPowerTime autoType="OFF">22:00:00</autoPowerTime>' .
                   '</autoPowerProperties>' .
                   '<defaultCollectionOrder>NAME</defaultCollectionOrder>' .
                   '<respondToLocalControls>true</respondToLocalControls>' .
                   '<language>en-us</language>' .
                   '<timeZoneOffset>0:00:00+2:00</timeZoneOffset>' .
                   '<managePictureStorage>true</managePictureStorage>' .
                   '<logLevel>OFF</logLevel>' .
                   '<enableNotification>false</enableNotification>' .
                   '<modificationDate>2010-10-20T20:18:03Z</modificationDate>' .
                   "<modificationTime>1287605883011</modificationTime>" .
              '</deviceSettings>';

} elseif ('/DeviceRestV10/collection' == $r) {
    print $e . '<collection>' .
                   '<story>' .
                        '<id>77777777-fefe-fefe-fefe-777777777777</id>' .
                        '<title>My Kodak Hacking Session Pics</title>' .
                        '<displayDate>2010-10-19T22:14:30Z</displayDate>' .
                        "<modificationDate>$latest_picture_date</modificationDate>" .
                        "<modificationTime>$latest_picture_timestamp</modificationTime>" .
                        '<authorProfileID>66666666-5555-3333-2222-222222222222</authorProfileID>' .
                        '<source>EMAIL</source>' .
                        '<contents>';
    
    $content = file_get_contents($rss_url);  
    $x = new SimpleXmlElement($content);
      
    foreach($x->channel->item as $entry) { 
	$id = md5($entry->link);
	$modtime = strtotime($entry->pubDate);
        $dat = date_parse($entry->pubDate);
        $moddate = "$dat[year]-$dat[month]-$dat[day]T$dat[hour]:$dat[minute]:$dat[second]Z";

	print               "<pictureSpec>" .
                               "<id>$id</id>" .
                               "<modificationDate>$moddate</modificationDate>" .
                               "<modificationTime>$modtime</modificationTime>" .
                            "</pictureSpec>";
    }
    print                   '</contents>' .
                   '</story>' .
              '</collection>';

} elseif ('/DeviceRestV10/profile/' == substr($r, 0, 23)) {

    print $e . '<profile>' .
                      '<id>66666666-5555-3333-2222-222222222222</id>' .
                      '<name>Firstname Lastname</name>' .
                      '<emailAddress>user@mailprovider.com</emailAddress>' .
                  '</profile>';

} elseif ('/DeviceRestV10/entity/' == substr($r, 0, 22)) {

    // /DeviceRestV10/entity/<id> accepts GET and DELETE
    $selectedid = substr($r, 22);


    $content = file_get_contents($rss_url);
    $x = new SimpleXmlElement($content);

    foreach($x->channel->item as $entry) {
        $id = md5($entry->link);
	if($selectedid == $id ) {
        	$modtime = strtotime($entry->pubDate);
        	$dat = date_parse($entry->pubDate);
		$title = $entry->title;
		$fileurl = $entry->enclosure[url];
        	$moddate = "$dat[year]-$dat[month]-$dat[day]T$dat[hour]:$dat[minute]:$dat[second]Z";
		$capturedate = $moddate;


        	print $e . "<picture>" .
                      "<id>$id</id>" .
                      "<title>$title</title>" .
                      "<captureDate>$capturedate</captureDate>" .
                      "<modificationDate>$moddate</modificationDate>" .
                      "<modificationTime>$modtime</modificationTime>" .
                      "<fileURL>$fileurl</fileURL>" .
                  "</picture>";
    	}
    }

} else {

    header('HTTP/1.1 404 Not Found');

}



function getLatestUpdate($feed_url) {  
      
    $content = file_get_contents($feed_url);  
    $x = new SimpleXmlElement($content);  
    $maxValue = 0;  
    foreach($x->channel->item as $entry) {  
    	$modtime = strtotime($entry->pubDate);
    	if( $maxValue < $modtime ) {
    		$maxValue = $modtime;
    	}
    }  
    return $maxValue;
    
}  

function toDateString($timestamp) {
        $dat = getdate($timestamp);
	return "$dat[year]-$dat[mon]-$dat[mday]T$dat[hours]:$dat[minutes]:$dat[seconds]Z";
}
