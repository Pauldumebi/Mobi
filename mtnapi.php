<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

switch ($_SERVER['HTTP_ORIGIN']) {
    case 'https://partners.9ijakids.com': case 'https://partners.9ijakids.com/index.php/play':
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    break;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/SMTP.php';

function respond($code, $response)
{
    header("Content-Type:application/json");
    http_response_code($code);
    echo(is_array($response) ? json_encode($response) : $response);
    exit(0);
}

function getDb()
{
    $con = mysqli_connect("localhost", "rookietoosmart", "lAunch0ut!", "mobi");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit(0);
    }
    return $con;
}

function getPartnerDb()
{
    $con = mysqli_connect("localhost", "rookietoosmart", "lAunch0ut!", "partnerdb");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit(0);
    }
    return $con;
}

function formatIntlPhoneNo($phone)
{
    if (substr($phone, 0, 1) === '0') {
        return '234' . substr($phone, 1);
    }
    return $phone;
}

function getUser($phone, $db)
{
    $query = mysqli_query($db, "select phone, name, expires from mobi.users where phone = '$phone'");
    if (mysqli_num_rows($query) < 1)
        return null;
    $user = mysqli_fetch_array($query);
    return $user;
}

function logNetworkMsg($url, $network, $body, $ref, $db)
{
    $sql = "insert into mobi.messages (network, url, message, refId) 
        VALUES ('$network', '$url', '$body', '$ref')";
    $db->query($sql);
}

function gloSubscribe($phone, $package, $db)
{
    $url = 'http://174.143.201.191/gloSUB/api';
    $body = "username=9ijakids&password=hIpVS8t5vv&keyword=$package&phoneNo=$phone&shortcode=8012";
    logNetworkMsg($url, 'glo', $body, $phone, $db);
    $resp = file_get_contents($url . '?' . $body);
    logNetworkMsg($url, 'glo', $resp, $phone, $db);
    return $resp;
}

function gloUnSubscribe($phone, $package, $db)
{
    $url = 'http://174.143.201.191/gloSUB/api';
    $body = "username=9ijakids&password=hIpVS8t5vv&keyword=stop%20$package&phoneNo=$phone&shortcode=8012";
    logNetworkMsg($url, 'glo', $body, $phone, $db);
    $resp = file_get_contents($url . '?' . $body);
    logNetworkMsg($url, 'glo', $resp, $phone, $db);
    return $resp;
}

function mtnSubscribe($phone, $db)
{
    $url = 'http://174.143.201.191/mtnSDP/subscribe';
    $body = "msisdn=$phone&productID=23401220000029685";
    logNetworkMsg($url, 'mtn', $body, $phone, $db);
    $resp = file_get_contents($url . '?' . $body);
    logNetworkMsg($url, 'mtn', $resp, $phone, $db);
    return $resp;
}

function mtnUnSubscribe($phone, $db)
{
    $url = 'http://174.143.201.191/mtnSDP/unsubscribe/';
    $body = "msisdn=$phone&productID=23401220000029685";
    logNetworkMsg($url, 'mtn', $body, $phone, $db);
    $resp = file_get_contents($url . '?' . $body);
    logNetworkMsg($url, 'mtn', $resp, $phone, $db);
    return $resp;
}

function forgotPassword($email, $db, $link) {
    $template_file = "resetpassword.php";
    $sql = "select email from mobi.users where email = '$email'";
    $query = mysqli_query($db, $sql);
    $user = mysqli_fetch_array($query);
    $user = $user['name'];
        
        $swap_var = array(
            "{name}" => "$user",
            "{link}" => "$link"
        );
        //create the html message
        if (file_exists($template_file)) 
            $message = file_get_contents($template_file);
        else 
            die("unable to find file");
    
        //Search and replace all the swap_vars
        foreach (array_keys($swap_var) as $key) {
            $message = str_replace($key, $swap_var[$key], $message);
        }
        
    //Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;              
        $mail->isSMTP();               
        $mail->Host       = 'mail3.gridhost.co.uk';
        $mail->SMTPAuth   = true;    
        $mail->Username   = 'help@9ijakids.com';
        $mail->Password   = 'M0bi h@lp me';   
        $mail->SMTPSecure = 'ssl';   
        $mail->Port       = 465;                  
        $mail->DKIM_domain = '9ijakids.com';
        $mail->DKIM_private = '/var/www/mobi/emailbounce/9ijakidsemails.private.key'; 
        $mail->DKIM_selector = 'newsletter';
        $mail->DKIM_passphrase = '';
        $mail->DKIM_identity = $mail->From;
        
        //Recipients
        $mail->setFrom('help@9ijakids.com', 'Jane Bassey 9ijakids Learning Games');
        $mail->addAddress($email);
        
        $mail->addReplyTo('help@9ijakids.com', 'Jane Bassey 9ijakids Learning Games');
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password';
        $mail->Body    = $message;
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        
        $mail->send();
    } catch (Exception $e) {
        respond(404, array('success' => false, 'error' => "We are sorry but the email did not go through, please try again later. Mailer Error: {$mail->ErrorInfo}"));
    }

}

function sendEmail ($email, $db) {
    // require 'vendor/autoload.php';
    $template_file = "templateEmail.php";
    $tokengen = openssl_random_pseudo_bytes(16);
        //Convert the binary data into hexadecimal representation.
        $token = bin2hex($tokengen);
        $expFormat = mktime(
            date("H"), date("i"), date("s"), date("m") ,date("d"), date("Y")
            );
        $createdDate = date("Y-m-d H:i:s",$expFormat);
        $sql = "select email from mobi.verifyemail where email = '$email'";
        $query = mysqli_query($db, $sql);
        $verifyemail = mysqli_fetch_array($query);
        if ($verifyemail) {
            respond(404, array('success' => false, 'message' => $email, 'error' => 'Please verify your email check your spam or junk if you do not find it in your inbox'));
        }
        $sql = "INSERT INTO mobi.verifyemail(email, token, createdDate)
                VALUES ('$email','$token', '$createdDate')";
            $db->query($sql);
            if($db->errno)
                respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
        $sql = "select * from mobi.users where email = '$email'";
        $query = mysqli_query($db, $sql);
        $user = mysqli_fetch_array($query);
        $user = $user['name'];
        
        $link = "http://mobi.9ijakids.com/verifyemail.php?key=".$email."&amp;token=".$token."";
        
        $swap_var = array(
            "{name}" => "$user",
            "{email}" => "$email",
            "{link}" => "$link"
        );
        //create the html message
        if (file_exists($template_file)) 
            $message = file_get_contents($template_file);
        else 
            die("unable to find file");
    
        //Search and replace all the swap_vars
        foreach (array_keys($swap_var) as $key) {
            $message = str_replace($key, $swap_var[$key], $message);
        }
        
    //Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;              
        $mail->isSMTP();               
        $mail->Host       = 'mail3.gridhost.co.uk';
        $mail->SMTPAuth   = true;    
        $mail->Username   = 'help@9ijakids.com';
        $mail->Password   = 'M0bi h@lp me';   
        $mail->SMTPSecure = 'ssl';   
        $mail->Port       = 465;                  
        $mail->DKIM_domain = '9ijakids.com';
        $mail->DKIM_private = '/var/www/mobi/emailbounce/9ijakidsemails.private.key'; 
        $mail->DKIM_selector = 'newsletter';
        $mail->DKIM_passphrase = '';
        $mail->DKIM_identity = $mail->From;
        
        //Recipients
        $mail->setFrom('help@9ijakids.com', 'Jane Bassey 9ijakids Learning Games');
        $mail->addAddress($email);
        
        $mail->addReplyTo('help@9ijakids.com', 'Jane Bassey 9ijakids Learning Games');
        $mail->isHTML(true);
        $mail->Subject = 'Please confirm your email to sign in and play the 9ijakids Mobile games';
        $mail->Body    = $message;
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        
        $mail->send();
    } catch (Exception $e) {
        respond(404, array('success' => false, 'error' => "We are sorry but the email did not go through, please try again later. Mailer Error: {$mail->ErrorInfo}"));
    }

}

if ($json = json_decode(file_get_contents("php://input"), true))
    $request = $json;
else if ($_POST)
    $request = $_POST;
else if ($_GET)
    $request = $_GET;
$log = strftime('%Y-%m-%d');
$time = strftime('%H:%M:%S');

try {
    $db = getDb();
    if (stripos($_SERVER['REQUEST_URI'], '/checkStatus')!== false) {
        $tel = $request['phone'];
        if (!$tel || strlen($tel) < 10)
            respond(400, array('success' => false, 'error' => 'invalid input'));
        $phone = formatIntlPhoneNo($tel);
        $trimPhone = substr("$phone",0,6);
        $convertNumber = intval($trimPhone);
        if ($convertNumber == 234803 || $convertNumber == 234703  || $convertNumber == 234903 || $convertNumber == 234806 || $convertNumber == 234706 || $convertNumber == 234813 || $convertNumber == 234810 || $convertNumber == 234814 || $convertNumber == 234816) {
            $sql = "select phone, name, displayName, expires, network from mobi.users where phone = '$phone'";
            $query = mysqli_query($db, $sql);
            if (mysqli_num_rows($query) == 1) {
                $user = mysqli_fetch_array($query);
                $user = array('phone' => $user['phone'], 'network' => $user['network'], 'displayName' => $user['displayName'], 'name' => $user['name'], 'expires' => $user['expires']);
            }
        } else {
            respond(400, array('success' => false, 'error' => 'Please connect with an mtn network'));
        }
        if (!$user)
            respond(404, array('success' => false, 'error' => 'account not found'));
        respond(200, array('success' => true, 'data' => $user));
    } else if (stripos($_SERVER['REQUEST_URI'], '/signup') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        $email = $request['email'];
        $trimPhone = substr("$phone",0,6);
        $convertNumber = intval($trimPhone);
        if ($convertNumber == 234803 || $convertNumber == 234703  || $convertNumber == 234903 || $convertNumber == 234806 || $convertNumber == 234706 || $convertNumber == 234813 || $convertNumber == 234810 || $convertNumber == 234814 || $convertNumber == 234816) {
            $sql = "select * from mobi.users where phone = '$phone' or email = '$email'";
            $query = mysqli_query($db, $sql);
            if (mysqli_num_rows($query) == 1) {
                respond(409, array('success' => false, 'error' => 'You already have an account please login'));
            } else {
                if ($request['network'] === 'glo')
                $resp = gloSubscribe($phone, $request['package'], $db);
                else if ($request['network'] === 'mtn') {
                    $resp = mtnSubscribe($phone, $db);
                }
                if ($resp) {
                    if (stripos($resp, 'Subscription is being confirmed') >= 1 || stripos($resp, "The product has been subscribed to") >= 1 || stripos($resp, 'Temporary Order saved successfully!') >= 1) {
                        $hash = password_hash($request['password'], PASSWORD_DEFAULT);
                        $name = $request['name'];
                        $network = $request['network'];
                        $displayName = $request['displayName'];
                        // $address = $request['address'];
                        $expires = date('Y-m-d H:i:s');
                        if( stripos($resp, "The product has been subscribed to") >= 1) //already subscribed
                            $expires = date('Y-m-d H:i:s', strtotime($expires.' + 1 days')); //we don't know package, give one day
                        $sql = "INSERT INTO mobi.users(phone, password, email,name, displayName, network,expires)
                            VALUES ('$phone','$hash','$email','$name', '$displayName','$network','$expires')";
                        // $sql = "INSERT INTO mobi.users(phone, password, email,name, displayName,network, address,expires)
                        //     VALUES ('$phone','$hash','$email','$name','$displayName,'$network','$address', '$expires')";
                        $db->query($sql);
                        if($db->errno)
                            respond(500, array('success' => false, 'error' => 'db error: '.$db->error));
                        // $emailsent = sendEmail($email, $db);
                        $user = "null";
                        respond(201, array('success' => true, 'message' => ['Account created', 'Email sent', $user]));
                        // if($emailsent) {
                        //     $sql = "select email_verified from mobi.users where phone = '$phone'";
                        //     $query = mysqli_query($db, $sql);
                        //     $user = mysqli_fetch_array($query);
                        //     $user = $user['email_verified'];
                        //     respond(200, array('success' => true, 'message' => ['subscription confirmed', 'Email sent', $user]));
                        // }
                    } else
                        respond(400, array('success' => false, 'error' => 'subscription failed'));
                } else
                    respond(400, array('success' => false, 'error' => 'could not initiate subscription'));
            }
        }else {
            respond(400, array('success' => false, 'error' => 'Please signup with an mtn number'));
        }
        
    } else if (stripos($_SERVER['REQUEST_URI'], '/profile') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        $hash = password_hash($request['password'], PASSWORD_DEFAULT);
        $email = $request['email'];
        $name = $request['name'];
        $displayName = $request['displayName'];
        // $address = $request['address'];
        $sql = "UPDATE mobi.users SET name = '$name', displayName = '$displayName', email = '$email', password = '$hash' where phone = '$phone'";
        // $sql = "UPDATE mobi.users SET name = '$name', email = '$email', address = '$address', password = '$hash' where phone = '$phone'";
        $db->query($sql);
        if($db->errno)
            respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
        $sql = "select phone, name, displayName, email, expires from mobi.users where phone = '$phone'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
            $user = mysqli_fetch_array($query);
            $expiry_ok = date('Y-m-d H:i:s') < $user['expires'];
            $user = array('phone' => $user['phone'], 'name' => $user['name'],'displayName' => $user['displayName'], 'email' => $user['email'], 'expires' => $user['expires']);
            // $emailsent = sendEmail ($email, $db);
            respond(200, array('success' => true, 'message' => ['Profile Successfully Updated', $user] ));
            // if($emailsent) {
            //     respond(200, array('success' => true, 'message' => ['Profile Successfully Updated', $user] ));
            // }
        }
        else
            respond(400, array('success' => false, 'error' => 'failed to update profile'));
    }  else if (stripos($_SERVER['REQUEST_URI'], '/UpdateProfile') !== false) {
            $tel = $request['phone'];
            $name = $request['name'];
            $email = $request['email'];
            $address = $request['address'];
            $sql = "UPDATE mobi.users SET name = '$name', email = '$email', address = '$address' where phone = '$tel'";
                $db->query($sql);
                if($db->errno)
                    respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
                respond(200, array('success' => true, 'message' => 'Profile succesfully updated'));
    }  else if (stripos($_SERVER['REQUEST_URI'], '/addDisplayName') !== false) {
            $displayName = $request['displayName'];
            $phone = formatIntlPhoneNo($request['phone']);
            $sql = "select * from mobi.users where displayName = '$displayName'";
            $query = mysqli_query($db, $sql);
            if (mysqli_num_rows($query) == 1) {
                respond(409, array('success' => false, 'message' => 'Display name already exist, try something else'));
            } else {
                $sql = "UPDATE mobi.users SET displayName = '$displayName' where phone = '$phone'";
                $db->query($sql);
                if($db->errno)
                    respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
                $sql = "select phone, name, displayName, email, expires from mobi.users where phone = '$phone'";
                $query = mysqli_query($db, $sql);
                $user = mysqli_fetch_array($query);
                $user = array('phone' => $user['phone'], 'name' => $user['name'], 'displayName' => $user['displayName'], 'expires' => $user['expires'],  'email' => $user['email'] );
                respond(200, array('success' => true, 'data' => $user ,'message' => 'Display Name succesfully updated'));
            }  
    }  else if (stripos($_SERVER['REQUEST_URI'], '/UpdatePassword') !== false) {
        $phone = formatIntlPhoneNo($request['phone']);
        $newpassword = $request['newpassword'];
        $sql = "select phone, password from mobi.users where phone = '$phone'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
            $user = mysqli_fetch_array($query);
            $pass_ok = password_verify($request['password'], $user['password']);
            if ($pass_ok) {
                $hash = password_hash($newpassword, PASSWORD_DEFAULT);
                $sql = "UPDATE mobi.users SET password = '$hash' where phone = '$phone'";
                $db->query($sql);
                respond(200, array('success' => true, 'message' => 'Password Updated'));
            }
            else
                respond(403, array('success' => false, 'error' => 'wrong current password'));
        } else
            respond(400, array('success' => false, 'error' => 'User not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/GetProfile") !== false) {
        $tel = $_GET['phone'];
        $sql = "select phone, name, password, email, address, plan, network, expires from mobi.users where phone = '$tel'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
                $user = mysqli_fetch_array($query);
                $user = array('phone' => $user['phone'], 'name' => $user['name'], 'email' => $user['email'], 'address' => $user['address'], 'expires' => $user['expires'], 'plan' => $user['plan'], 'network' => $user['network']);
            }   if (!$user) 
                respond(404, array('success' => false, 'error' => 'User not found'));
            respond(200, array('success' => true, 'data' => $user));
    } else if (stripos($_SERVER['REQUEST_URI'], '/Unsubscribe') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        if($request['package'] == 'Daily' || $request['package'] == 'Weekly' || $request['package'] == 'Monthly')
            $resp = gloUnSubscribe($phone, $request['package'], $db);
        else
            $resp = mtnUnSubscribe($phone, $db);
        
        if (stripos($resp, 'successfully unsubscribed') >= 1 || stripos($resp, 'Success') >= 1)
            respond(200, array('success' => true, 'message' => 'subscription canceled', 'data'=> $resp));
        else
            respond(400, array('success' => false, 'error' => 'Unsubscription attempt not successful', 'data' => $resp));
    } else if (stripos($_SERVER['REQUEST_URI'], '/forgotPassword') !== false) {
        $email = $request['email'];
        // $tel = $request['phone'];
        // $phone = formatIntlPhoneNo($tel);
        $sql = "select email from mobi.users where email = '$email'";
        $query = mysqli_query($db, $sql);
        // $user = mysqli_fetch_array($query);
        // $user = $user['email'];
        if (mysqli_num_rows($query) == 1) {
            $sql = "select * from mobi.forgotpassword where email = '$email'";
            $query = mysqli_query($db, $sql);
            $existingLink = mysqli_fetch_array($query);
            if ($existingLink) {
                $sql = "delete from mobi.forgotpassword where email = '$email'";
                $query = mysqli_query($db, $sql);
            }
            $token = md5($email).rand(10,9999);
            $expFormat = mktime(
            date("H"), date("i"), date("s"), date("m") ,date("d"), date("Y")
            );
            $createdDate = date("Y-m-d H:i:s",$expFormat);
            $sql = "INSERT INTO mobi.forgotpassword(email, createdDate, token)
                    VALUES ('$email','$createdDate','$token')";
                $db->query($sql);
                if($db->errno)
                    respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
            $link = "mobi.9ijakids.com/forgotPassword.php?key=".$email."&amp;token=".$token."";
            forgotPassword($email, $db, $link);
            respond(200, array('success' => true, 'message' => 'Password reset link has been sent to your email'));
        } else {
            respond(404, array('success' => false, 'error' => 'Sorry this email is not associated with an account'));
        }
        if (!$user)
            respond(404, array('success' => false, 'error' => 'account not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/verifyemail") !== false) {
        $email = $_GET['email'];
        $sql = "select * from mobi.verifyemail where email = '$email'";
        $query = mysqli_query($db, $sql);
        $user = mysqli_fetch_array($query);
        if (mysqli_num_rows($query) == 1) {
            $sql = "UPDATE mobi.users SET email_verified = 'verified' where email = '$email'";
            $db->query($sql);
            if($db->errno)
                respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
            $sql = "delete from mobi.verifyemail where email = '$email'";
            $query =  mysqli_query($db, $sql);
            if($db->errno)
                respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
            $sql = "select network from mobi.users where email = '$email'";
            $query =  mysqli_query($db, $sql);
            $user = mysqli_fetch_array($query);
            $network = $user["network"];
            respond(200, array('success' => true, 'message' => 'Email verified', 'network' => $network));
        } else
        respond(404, array('success' => false, 'message' => 'This link has expired'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/updateforgetpassword') !== false) {
        $emailId = $request['email'];
        $token = $request['accesstoken'];
        $sql = "SELECT * FROM mobi.forgotpassword WHERE token= '$token' and email= '$emailId'";
        $query = mysqli_query($db, $sql);
        $row = mysqli_num_rows($query);
        $sql = "SELECT * FROM mobi.users WHERE email= '$emailId'";
        $query = mysqli_query($db, $sql);
        $network = mysqli_fetch_array($query);
        $network = $network["network"];
        if($row){
            $hash = password_hash($request['password'], PASSWORD_DEFAULT);
            $sql = "update mobi.users set password = '$hash' where email = '$emailId'";
            $query =  mysqli_query($db, $sql);
            $sql = "delete from mobi.forgotpassword where email = '$emailId'";
            $query =  mysqli_query($db, $sql);
            respond(200, array('success' => true, 'message' => 'Password changed successfully', 'network'=> $network));
        } else 
            respond(404, array('success' => false, 'error' => 'Something went wrong. Please try again later'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/Userscore') !== false) {
        $gameid = $request['gameid'];
        $player = $request['player'];
        $score = $request['score'];
        $endDate = $request['endDate'];
        $todayDate = date("Y-m-d");
        if ($endDate >  $todayDate) {
            $sql = "INSERT INTO mobi.competition_leaderboard (gameid, player,score) VALUES ('$gameid', '$player','$score')";
            $db->query($sql);
                if( $db->errno)
                    respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
                else 
                    respond(200, array('success' => true, 'message' => 'Userscore successfully inserted'));
        }
    } else if (stripos($_SERVER['REQUEST_URI'], '/Leaderboard') !== false) {
        $db =getPartnerDb();
        $gameid = $request['gameid'];
        $sql = "select * from partnerdb.Game where Gameid='$gameid'";
        $query = mysqli_query($db, $sql);
        $user = mysqli_fetch_array($query);
        $currentGameTitle = $user['GameTitle'];
        $currentGameStartDate = $user['StartDate'];
        $currentGameEndDate = $user['EndDate'];
        $formattedCurrentGameEndDate = explode(" ",$currentGameEndDate)[0].' '.'18:00:00';

        //Get Previous game
        $formattedEndDateToGetPreviousGame = explode(" ",$currentGameStartDate)[0].' '.'17:59:59';
        $sql_2 = "select * from partnerdb.Game where EndDate='$formattedEndDateToGetPreviousGame'";
        $query_2 = mysqli_query($db, $sql_2);
        $user_2 = mysqli_fetch_array($query_2);
        $previousGameTitle = $user_2['GameTitle'];
        $previousGameStartDate = $user_2['StartDate'];
        $previousGameID = $user_2['GameID'];
        $previousGameEndDate = $user_2['EndDate'];
        // $formattedPreviousGameStartDate = date('Y-m-d', strtotime($previousGameStartDate));
        // $formattedPreviousGameEndDate = date('Y-m-d', strtotime($previousGameEndDate));
        
            $sql_final_Leaderboard = "SELECT id, player, MAX(score) as score, date FROM mobi.competition_leaderboard WHERE date BETWEEN '$previousGameStartDate' AND '$previousGameEndDate' and gameid = $previousGameID group by player ORDER BY score desc limit 10";
            $query = mysqli_query($db, $sql_final_Leaderboard);


            $sql_final_Leaderboard2 = "SELECT id, player, score, date FROM mobi.competition_leaderboard WHERE date BETWEEN '$previousGameStartDate' AND '$previousGameEndDate' and gameid = $previousGameID ORDER BY score desc limit 10";
            $query2 = mysqli_query($db, $sql_final_Leaderboard2);

            $i = -1;
            $j = 0;
            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_array($query)) {
                    $i++;
                    $j++;
                    $result_final_Leaderboard[$i]['position'] = $j;
                    $result_final_Leaderboard[$i]['player'] = $row["player"];
                    $result_final_Leaderboard[$i]['score'] = $row["score"];
                    $result_final_Leaderboard[$i]['date'] = $row["date"];
                }
            }elseif(mysqli_num_rows($query2) > 0) {
                while ($row = mysqli_fetch_array($query2)) {
                    $i++;
                    $j++;
                    $result_final_Leaderboard[$i]['position'] = $j;
                    $result_final_Leaderboard[$i]['player'] = $row["player"];
                    $result_final_Leaderboard[$i]['score'] = $row["score"];
                    $result_final_Leaderboard[$i]['date'] = $row["date"];
                }
            }
        
        $sql = "SELECT id, player, MAX(score) as score, date FROM mobi.competition_leaderboard WHERE date BETWEEN '$currentGameStartDate' AND '$currentGameEndDate' group by player ORDER BY score desc limit 10";
        $query = mysqli_query($db, $sql);

        $sql2 = "SELECT id, player, score, date FROM mobi.competition_leaderboard WHERE date BETWEEN '$currentGameStartDate' AND '$currentGameEndDate' ORDER BY score desc limit 10";
        $query2 = mysqli_query($db, $sql2);

        $i = -1;
        $w = 0;

        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_array($query)) {
                $i++;
                $w++;
                $result[$i]['position'] = $w;
                $result[$i]['player'] = $row["player"];
                $result[$i]['score'] = $row["score"];
                $result[$i]['date'] = $row["date"];
            }
            respond(200, array('success' => true, 'currentGameTitle'=> $currentGameTitle,'currentGameStartDate'=> $currentGameStartDate, 'currentGameEndDate'=> $currentGameEndDate, 'previousGameTitle' => $previousGameTitle, 'previousGameStartDate' => $previousGameStartDate, 'previousGameEndDate' => $previousGameEndDate, 'final_result' => $result_final_Leaderboard, 'data'=> $result));
        } elseif(mysqli_num_rows($query2) > 0) {
            while ($row = mysqli_fetch_array($query2)) {
                $i++;
                $w++;
                $result[$i]['position'] = $w;
                $result[$i]['player'] = $row["player"];
                $result[$i]['score'] = $row["score"];
                $result[$i]['date'] = $row["date"];
            }
            respond(200, array('success' => true, 'currentGameTitle'=> $currentGameTitle,'currentGameStartDate'=> $currentGameStartDate, 'currentGameEndDate'=> $currentGameEndDate, 'previousGameTitle' => $previousGameTitle, 'previousGameStartDate' => $previousGameStartDate, 'previousGameEndDate' => $previousGameEndDate, 'final_result' => $result_final_Leaderboard, 'data'=> $result));
        }
        else {
            respond(200, array('success' => true, 'currentGameTitle'=> $currentGameTitle,'currentGameStartDate'=> $currentGameStartDate, 'currentGameEndDate'=> $currentGameEndDate, 'final_result' => $result_final_Leaderboard, 'previousGameStartDate' => $previousGameStartDate, 'previousGameEndDate' => $previousGameEndDate, 'message'=> 'No table found'));
        }
    } else if (stripos($_SERVER['REQUEST_URI'], "/Checktoken") !== false) {
        $token = $_GET['token'];
        $sql = "SELECT * FROM mobi.forgotpassword WHERE token= '$token'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
            $user = mysqli_fetch_array($query);
            $user = array('token' => $user['token'], 'createdDate' => $user['createdDate']);
            respond(200, array('success' => true, 'message' => $user));            
        } else 
            respond(404, array('success' => false, 'error' => 'This link has expired'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/subscribeAgain') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        if($request['network'] === 'glo')
            $resp = gloSubscribe($phone, $request['package'], $db);
        else if ($request['network'] === 'mtn')
            $resp = mtnSubscribe($phone, $db);
        if (stripos($resp, 'Subscription is being confirmed') >= 1 || stripos($resp, 'Temporary Order saved successfully!') >= 1) {
            respond(200, array('success' => true, 'message' => 'subscription confirmed'));
        } elseif ($resp)
            respond(400, array('success' => false, 'error' => 'subscription failed'));
        else
            respond(400, array('success' => false, 'error' => 'could not initiate subscription'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/mtnSubscribe') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        $sql = "SELECT expires FROM mobi.users where phone = '$phone'";
        $query = mysqli_query($db, $sql);
        $user = mysqli_fetch_array($query);
        $expiry = $user['expires'];
        $date =  date("Y-m-d h:i:sa");
        if ($expiry > $date) {
            respond(404, array('success' => false, 'error' => 'You have an active subscription please login'));
        } else {
            $resp = mtnSubscribe($phone, $db);
            if (stripos($resp, 'Subscription is being confirmed') >= 1 || stripos($resp, 'Temporary Order saved successfully!') >= 1) {
                respond(200, array('success' => true, 'message' => 'subscription confirmed'));
            } elseif ($resp)
                respond(400, array('success' => false, 'error' => 'subscription failed'));
            else
            respond(400, array('success' => false, 'error' => 'could not initiate subscription'));
        } 
    }
    else if (stripos($_SERVER['REQUEST_URI'], '/confirmToken') !== false) {
        $tel = $request['phone'];
        $phone = formatIntlPhoneNo($tel);
        $sql = "SELECT * FROM mobi.users where phone = '$phone'";
        $query = mysqli_query($db, $sql);
        $emailVerified = mysqli_fetch_array($query);
        $emailVerified = $emailVerified['email_verified'];
        respond(200, array('success' => true, 'message' => $emailVerified));
        
    } else if (stripos($_SERVER['REQUEST_URI'], '/mtnUpdate') !== false) {
        logNetworkMsg($_SERVER['REQUEST_URI'], 'mtn', json_encode($request), '', $db);
        $entry = ['time' => $time, 'request' => $request, 'callback' => '/mtnUpdate', 'info' => $_SERVER];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
        if($request["status"] !== "SUCCESS")
        respond(200, "");
        if($request["type"] === "SUB" OR $request["type"] === "REN" OR $request["type"] === "UNSUB"){
            $subscribetext = $request["subscribetext"];
            $sql = "SELECT * FROM mobi.plans where keyword = '$subscribetext'";
            $query = mysqli_query($db, $sql);
            $mtnplandetails = mysqli_fetch_array($query);
            $amount = $mtnplandetails['price'];
            $plan = $mtnplandetails['frequency'];
        }
            $phone = $request["msisdn"];
            $user = getUser($phone, $db);
            $telco = $request["mno"];
            $transaction_id = $request["transaction_id"];
            $transaction_time = $request["requesttime"];
            $service_id = $request["productid"];
            $type = $request["type"];
            if ($request["type"] === "UNSUB") {
                $histsql = "INSERT INTO mobi.unsubscriptions(phone, amount,transaction_time, transaction_id,service_id,plan,type,network) VALUES ('$phone', '$amount','$transaction_time','$transaction_id','$service_id','$plan','$type','$telco')";
                $db->query($histsql);
                respond(200, array('success' => true, 'message' => 'UnSubscription successful'));
                exit();
            }
            $expires = date('Y-m-d H:i:s');
            if($request["type"] === "SUB"){
                $expires = date('Y-m-d H:i:s', strtotime($expires.' + 9 days'));
            } else if ( $request["type"] === "REN")
                $expires = date('Y-m-d H:i:s', strtotime($expires.' + 7 days'));
            $histsql = "INSERT INTO mobi.subscriptions(phone, amount,transaction_time, transaction_id,service_id,plan,type,network) VALUES ('$phone', '$amount','$transaction_time','$transaction_id','$service_id','$plan','$type','$telco')";
            $db->query($histsql);
            // subscribe on 9ijakids partner api
            $url = "https://partners.9ijakids.com/index.php?partnerId=254367&accessToken=g55fcaa6-3859-2809-sg56-93sa&action=subscribe&userPassport=$phone&expiryDate=" . urlencode($expires);
            $contents = file_get_contents($url);
            logNetworkMsg($url, 'partner-api', $contents, $phone, $db);
            if($user == null){ // insert new record, awaiting profile creation
                $telco=$request["mno"];
                $sql = "INSERT INTO mobi.users(phone, expires, plan, network) VALUES ('$phone', '$expires', '$plan','$telco')";
                $db->query($sql);
                if($db->errno);
                    respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
            } else { //update user's expires column
                $sql = "update mobi.users set expires = '$expires', plan = '$plan' where phone = '$phone'";
                $db->query($sql);
                // var_dump($sql);
                if($db->errno)
                    respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
                else
                    respond(200, array('success' => true, 'message' => 'Subscription update successful'));
            }
        respond(200, array('success' => true, 'message' => 'Successfully logged'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/airtelUpdate') !== false) {
        logNetworkMsg($_SERVER['REQUEST_URI'], 'airtel', json_encode($request), '', $db);
        $entry = ['time' => $time, 'request' => $request, 'callback' => '/airtelUpdate', 'info' => $_SERVER];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
            respond(200, array('success' => true, 'message' => 'Successfully logged'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/gloUpdate') !== false) {
        logNetworkMsg($_SERVER['REQUEST_URI'], 'glo', json_encode($request), '', $db);
        $entry = ['time' => $time, 'request' => $request, 'callback' => '/gloUpdate', 'info' => $_SERVER];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
        if($request["status"] !== "SUCCESS")
            respond(200, "");
        $phone = $request["msisdn"];
        $user = getUser($phone, $db);
        $telco = $request["mno"];
        $transaction_id = $request["transaction_id"];
        $transaction_time = $request["transaction_time"];
        $service_id = $request["service_id"];
        $type = $request["type"];
        if ($request["type"] === "UNSUB") {
            $plan = $request["amount"];
            $sql = "SELECT * FROM mobi.plans where price = '$plan'";
            $query = mysqli_query($db, $sql);
            $typePlan = mysqli_fetch_array($query);
            $typeofplan = $typePlan['frequency'];
            $histsql = "INSERT INTO mobi.unsubscriptions(phone, amount,transaction_time, transaction_id,service_id,plan,type,network) VALUES ('$phone', '$plan','$transaction_time','$transaction_id','$service_id','$typeofplan','$type','$telco')";
            $db->query($histsql);
            respond(200, array('success' => true, 'message' => 'UnSubscription successful'));
            exit();
        }
        $expires = date('Y-m-d H:i:s');
        if($request["type"] === "SUB" OR $request["type"] === "REN"){
            $amount = $request["amount"];
            switch ($amount){
                case "20":
                    $expires = date('Y-m-d H:i:s', strtotime($expires.' + 1 days'));
                    $plan = 'Daily';
                    break;
                case "50":
                    $expires = date('Y-m-d H:i:s', strtotime($expires.' + 7 days'));
                    $plan = 'Weekly';
                    break;
                case "200":
                    $expires = date('Y-m-d H:i:s', strtotime($expires.' + 1 months'));
                    $plan = 'Monthly';
                    break;
            }
            $histsql = "INSERT INTO mobi.subscriptions(phone, amount,transaction_time, transaction_id,service_id,plan,type,network) VALUES ('$phone', '$amount','$transaction_time','$transaction_id','$service_id','$plan','$type','$telco')";
            $db->query($histsql);
            // subscribe on 9ijakids partner api
            $url = "https://partners.9ijakids.com/index.php?partnerId=254367&accessToken=g55fcaa6-3859-2809-sg56-93sa&action=subscribe&userPassport=$phone&expiryDate=" . urlencode($expires);
            $contents = file_get_contents($url);
            logNetworkMsg($url, 'partner-api', $contents, $phone, $db);
        }
        if($user == null){ // insert new record, awaiting profile creation
		    $telco=$request["mno"];
            $sql = "INSERT INTO mobi.users(phone, expires, plan, network) VALUES ('$phone', '$expires', '$plan','$telco')";
            $db->query($sql);
            if($db->errno);
                respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
        } else { //update user's expires column
            $sql = "update mobi.users set expires = '$expires', plan = '$plan' where phone = '$phone'";
            $db->query($sql);
            // var_dump($sql);
            if($db->errno)
                respond(500, array('success' => false, 'message' => 'db error: '.$db->error));
            else
                respond(200, array('success' => true, 'message' => 'Subscription update successful'));
        }
    } else if (stripos($_SERVER['REQUEST_URI'], '/login') !== false) {
        $phone = formatIntlPhoneNo($request['phone']);
        $sql = "select email from mobi.users where phone = '$phone'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {  
            // $email = mysqli_fetch_array($query);
            // $email = $email['email'];
            // $sql = "select token from mobi.verifyemail where email = '$email'";
            // $query = mysqli_query($db, $sql);
            // $token = mysqli_fetch_array($query);
            // $token = $token['token'];
            // if ($token) {
            //     respond(400, array('success' => false, 'message' => $email, 'error' => 'Please verify your email check your spam or junk if you do not find it in your inbox'));
            // } 
            // else {
                // $sql = "select phone, name, password, email_verified, expires from mobi.users where phone = '$phone'";
                // $query = mysqli_query($db, $sql);
                // $emailVerified = mysqli_fetch_array($query);
                // $emailVerified = $emailVerified['email_verified'];
                // if ($emailVerified == null) {
                //     $emailsent = sendEmail ($email, $db);
                //     if  ($emailsent) {
                //         respond(400, array('success' => false, 'error' => 'Please verify your email', 'message' => $email));
                //     }
                //     else {
                //         respond(404, array('success' => false, 'error' => 'We are sorry but the email did not go through, please try again later'));
                //     }
                // } 
                // else {
                    $sql = "select phone, name, displayName, password, email, expires from mobi.users where phone = '$phone'";
                    $query = mysqli_query($db, $sql);
                    $user = mysqli_fetch_array($query);
                    $pass_ok = password_verify($request['password'], $user['password']);
                    $expiry_ok = date('Y-m-d H:i:s') < $user['expires'];
                    $user = array('phone' => $user['phone'], 'name' => $user['name'],'displayName' => $user['displayName'], 'expires' => $user['expires'],  'email' => $user['email'] );
                    if (!$expiry_ok) {
                        respond(403, array('success' => false, 'error' => 'subscription has expired, please subscribe again'));
                    }
                    else if(!$pass_ok)
                        respond(403, array('success' => false, 'error' => 'login failed, Invalid password'));
                    else if ($pass_ok & $expiry_ok)
                        respond(200, array('success' => true, 'message' => $user));     
                // }
            } else 
                respond(401, array('success' => false, 'error' => 'login failed: not registered'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/games') !== false) {
        $resp = file_get_contents("https://partners.9ijakids.com/index.php?action=catalog&partnerId=254367&accessToken=g55fcaa6-3859-2809-sg56-93sa");
        if($resp)
            respond(200, array('success'=> true, 'message' => $resp));
        else
            respond(500, array('success' => false, 'error' => 'error getting games list'));
    }
    else
        respond(404, array('success' => false, 'error' => 'resource or endpoint not found'));
} catch (Exception $e) {
    try {
        $entry = ['time' => $time, 'request' => $request, 'error' => json_encode($e)];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
        respond(500, array('success' => false, 'error' => $e->getMessage()));
    }
    catch (Exception $ex) {
        respond(500, array('success' => false, 'error' => $e->getMessage().'|'.$ex->getMessage()));
    }
} finally {
    if ($db)
        $db->close();
}
