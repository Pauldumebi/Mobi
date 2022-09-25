<?php

if(isset($_POST['submit'])) {
    $email = $_POST['email'];
    $about = $_POST['subject'];
    $description = $_POST['description'];

    $to = "help@9ijakids.com";
    $subject = 'Support Request from mobi.9ijakids.com';
    $message = "email: ".$email."\n"."subject: ".$subject."\n"."About: ".$about."\n". "Wrote the following: "."\n\n".$description;
    $headers = "from: ".$email;

        if(mail($to, $subject, $message, $headers)) {
//            if(empty())
          echo"<div>Your message has been submitted successfully</div>";
        } else {
             echo"<p>Something went wrong!</p>";
        }

}
