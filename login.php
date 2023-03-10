<?php
    require_once "util.php";

    echo <<< _END
    <!DOCTYPE html>
    <html>
        <head>
            <style>
                #description {
                    margin-top: 20px;
                }
                #group {
                    margin-bottom: 20px;
                }
                .infotext {
                    font-weight: bold;
                }
            </style>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
            <script src='util.js'></script>
        </head>
        <body>
            <h1>Task Master</h1>
            <p class="infotext" id="infomsg"></p>

            <form method="post" action="login.php" enctype="multipart/form-data">
                <div id="group">
                    <label for="loginuser">Username:</label> <br>
                    <input id="loginuser" type="text" name="loginuser" required>
                </div>
                <div id="group">
                    <label for="loginpass">Password:</label> <br>
                    <input id="loginpass" type="password" name="loginpass" required>
                    <a href="forgot.php">Forgot Password?</a>
                </div>
                <input type="submit" name="action" value="Log In">
            </form>

            <p>Don't have an account yet? <a href='register.php'> Create an account</a></p>

            <div id='description'>
                <p>
                    This is a simple task organizer that allows users to enter
                    their tasks and modify them as they wish.
                </p>
            </div>
    _END;

    //This code handles the users logging in to the website
    if (isset($_POST['loginuser']) && isset($_POST['loginpass'])) {
        $tmp_login_user = htmlspecialchars($conn->real_escape_string($_POST['loginuser']));
        $tmp_login_pass = htmlspecialchars($conn->real_escape_string($_POST['loginpass']));

        //Select the row with the matching
        $hash = $conn->prepare('SELECT pass FROM credentials WHERE username = ?');
        $hash->bind_param("s", $tmp_login_user);
        $hash->execute();
        $hash = $hash->get_result();
        if ($hash->num_rows) {
            $hash = $hash->fetch_array(MYSQLI_NUM)[0];
            if (password_verify($tmp_login_pass, $hash)) {
                //Password matches the hash, so authentication is successful
                session_start();
                $_SESSION['username'] = $tmp_login_user;

                //Prevent session hijacking and fixation
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];

                header("Location: index.php");
            } else {
                notify_user_and_die("Incorrect username or password. Please try again.");
            }
        } else {
            notify_user_and_die("Incorrect username or password. Please try again.");
        }

        $result->close();
    }
    
    $conn->close();
    echo <<< _END

        </body>
    </html>
    _END;
?>