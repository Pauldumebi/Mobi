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

    <!-- font awesome link-->
    <script src="https://kit.fontawesome.com/c4b75b0548.js" crossorigin="anonymous"></script>
    <title>9ijaKids Mobile - Home</title>
</head>

<style>
    
</style>

<body>
    <div class="container" id="">
        <h2 class="expired pt-4 d-none">This link has expired</h2>
        <h2 class="successful text-success  pt-4 d-none">Email verification successful</h2>
        <a href="index2.html">Click here to login</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        //If you want to use the current page's URL
        let url = window.location;
        let access_token = new URLSearchParams(url.search).get('token');
        let email = new URLSearchParams(url.search).get('key');
        console.log(access_token);
        console.log(email);

        async function checkToken() {
            debugger
            try {
                const response = await fetch(`api.php/verifyemail?email=${email}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                //result = data;
                // console.log(data);
                // console.log(typeof data.success);
                if (data.success == true) {
                    $('.successful').removeClass('d-none');
                    $('.expired').addClass('d-none')
                    if (data.network == 'mtn') {
                        window.location.href = '/mtnindex.html'
                    }  else
                    window.location.href = '/index.html'
                } else {
                    $('.expired').removeClass('d-none')
                    $('.successful').addClass('d-none');
                }
            } catch (error) {
                console.error(error);
            }
        }
        $(document).ready(function () {
            checkToken();
        });
    </script>

</body>

</html>