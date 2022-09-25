<?php
# dbconxchk.php TEST connection to the Server. 
# Fill in the variables for $dbname, $dbpass, $dbuser
# Run the code below to launch from console
# $ php -f dbconxchk.php

$dbname = 'mobi';
$dbuser = 'rookietoosmart';
$dbpass = 'lAunch0ut!';
$dbhost = 'localhost';

$con = mysqli_connect($dbhost, $dbuser, $dbpass) or die("Unable to Connect to '$dbhost'");
mysqli_select_db($con, $dbname) or die("Could not open the db '$dbname'");

$test_query = "SHOW TABLES FROM $dbname";
$result = mysqli_query($con, $test_query);

$tblCnt = 0;
while($tbl = mysqli_fetch_array($result)) {
  $tblCnt++;
  #echo $tbl[0]."<br />\n";
}

if (!$tblCnt) {
  echo "There are no tables<br />\n";
} else {
  echo "Successful connection tp database $dbname and it has $tblCnt tables<br />\n";
} 
