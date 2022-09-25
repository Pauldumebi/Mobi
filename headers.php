<?php
echo "##################################################################### <br />\n  ";
echo " Detect header enrichment on 9ijakids Mobile Game Site <br />\n";
echo "###################################################################### <br />\n  ";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value <br />\n";
}
echo "---------------------------------------------------------------------------------- <br />\n ";
echo "<br />\n  ";

echo "#######################################<br />\n  ";

#$msisdn = $_SERVER['HTTP_MSISDN'];
$userIpAdd = getenv('REMOTE_ADDR');
#echo "Glo msisdn is: $msisdn <br />\n  ";
echo "IP address is: $userIpAdd <br />\n  ";

echo "---------------------------------<br />\n ";

#echo getenv('REMOTE_ADDR')."<br>";

if (!$_SERVER['HTTP_MSISDN']) {
    echo "Connection NOT with Glo Mobile Data <br />\n";
    echo "#################################### <br />\n  ";
    exit;
}
#echo $_SERVER['HTTP_MSISDN']."<br>";
#var_dump ($_SERVER['HTTP_MSISDN']);
echo "<br>";
#echo urlencode ($_SERVER['HTTP_MSISDN'])."<br>";
$msisdn = $_SERVER['HTTP_MSISDN'];
header("msisdn: $msisdn");
// $msisdn = $_SERVER['HTTP_MSISDN'];
echo "########################################################################## <br />\n  ";
echo "User connecting with Mobile Number : $msisdn <br />\n";
#echo "msisdn is: $msisdn <br />\n  ";
echo "########################################################################## <br />\n  ";