<?php
function getDb()
    {
        $con = mysqli_connect("localhost", "rookietoosmart", "lAunch0ut!", "mobi");
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            exit(0);
        }
        return $con;
    }
    $db = getDb();
    if(isset($_GET['token'])){
        $token = mysqli_real_escape_string($db, $_GET['token']);
        $sql = "select email from mobi.forgotpassword where token = '$token'";
        
        $query =  mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
            $row = mysqli_fetch_array($query);
            $token = $row['token'];
            $email = $row['email'];
        } else{
            header("location: index.html");
        }
    }

    if(isset($_POST['submit'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        if (strlen($_POST['password'])<6) {
            $msg = "<div class='alert alert-danger'>Password must be 6 characters long</div>";
        }
        $sql = "update mobi.users set password = '$hash' where email = '$email'";
        $query =  mysqli_query($db, $sql);
        $sql = "delete from mobi.forgotpassword where email = '$email'";
        $query =  mysqli_query($db, $sql);
        $msg = "<div class='alert alert-success'>Password updated successfully</div>";
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <!-- <link rel="stylesheet" href="style.css" /> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    <link rel="manifest" href="manifest.json" />

    <!-- ios support -->
    <link rel="apple-touch-icon" href="images/icons/logo-72.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-96.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-128.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-144.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-152.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-192.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-384.png" />
    <link rel="apple-touch-icon" href="images/icons/logo-512.png" />
    <meta name="apple-mobile-web-app-status-bar" content="#5fa73b" />
    <meta name="theme-color" content="#5fa73b" />

    <!-- toastr css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css"
        integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g=="
        crossorigin="anonymous" />


    <!-- font awesome link-->
    <script src="https://kit.fontawesome.com/c4b75b0548.js" crossorigin="anonymous"></script>
    <title>9ijaKids Mobile - Home</title>
</head>

<style>

</style>

<body>
    <div class="resetPassword" id="reset-password">
        <h1 class="text-center p-4 mb-5">Reset Password</h1>
        <div class="container" style="padding: 0 2rem;">
            <!-- <form name="myemailform"> -->
            <div class="form-group">
                <h5>Reset Password</h5>
                <input name="password" type="password" class="form-control" id="password" required>
            </div>
            <div class="form-group">
                <h5>Confirm Password</h5>
                <input name="subject" type="password" class="form-control height" id="confirm-password" required>
            </div>
            <small class="error text-danger"></small>
            <button name="submit" type="submit" class="btn btn-danger form-control mt-5" id='validate'
                onclick="changePassword()">Submit</button>
            <!-- </form> -->
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- toastr js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"
        integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw=="
        crossorigin="anonymous"></script>

    <script>
        //If you want to use the current page's URL
        let url = window.location;
        let urlParams = new URLSearchParams(window.location.search);
        let access_token = urlParams.get('token');
        let email = urlParams.get('key');

        function changePassword() {
            let password = $('#password').val()
            if (password !== $('#confirm-password').val()) {
                $("small").append(" <b>Password do not match!</b>.")
                $('#error').delay(5000).fadeOut('fast');
            } else {
                const req = {
                    accesstoken: access_token,
                    password: password,
                    email: email
                };
                fetch('api.php/updateforgetpassword', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(req)
                    }).then(resp => resp.json())
                    .then(data => {
                        if (data.success) {
                            let result = sessionStorage.setItem('result', JSON.stringify("success")),
                                setNetwork = sessionStorage.setItem('network', JSON.stringify(data.network));
                            window.location.href = '/reset.html'
                            toastr.success(
                                'Your Password has been succesfully updated', {
                                    timeOut: 5000,
                                    fadeOut: 1000,
                                }
                            )
                        } else {
                            toastr.error(
                                'Password update failed', {
                                    timeOut: 5000,
                                    fadeOut: 1000,
                                }
                            )
                        }
                    }).catch(err => {
                        $('#signup-error').html(err);
                    });
            }
        }
        async function checkToken() {
            debugger
            try {
                const response = await fetch(`api.php/Checktoken?token=${access_token}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success == true) {
                    let today = new Date(),
                        date = today.toISOString().slice(0, 10), // YYYY-MM-DD
                        time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds(),
                        dateTime = (`${date} ${time}`)
                    if (data.message.expirydate < dateTime) {
                        let result = sessionStorage.setItem('result', JSON.stringify("expired"))
                        window.location.href = '/reset.html'
                    }
                } else {
                    let result = sessionStorage.setItem('result', JSON.stringify("expired"))
                    window.location.href = '/reset.html'
                }
            } catch (error) {
                console.error(error);
            }

        }

        $(document).ready(function () {
            debugger
            checkToken();
            $('#link-error').hide();
        });
    </script>
</body>

</html>