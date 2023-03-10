<?php
    require_once "util.php";

    echo <<< _END
    <!DOCTYPE html>
    <html>
        <head>
            <style>
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
            <h2>Sign up for Task Master</h2>
            <p class="infotext" id="infomsg"></p>

            <form method="post" action="register.php" enctype="multipart/form-data" onsubmit="return validate(this);">
                <div id="group">
                    <label for="signupuser">Username:</label> <br>
                    <input id="signupuser" type="text" name="signupuser">
                    <span class="infotext" id="infotextuser"></span>
                </div>
                <div id="group">
                    <label for="signuppass">Password:</label> <br>
                    <input id="signuppass" type="password" name="signuppass">
                    <span class="infotext" id="infotextpass"></span>
                </div>
                <div id="group">
                    <label for="signuppass2">Confirm Password:</label> <br>
                    <input id="signuppass2" type="password" name="signuppass2">
                    <span class="infotext" id="infotextpass2"></span>
                </div>
                <div id="group">
                    <label for="signupemail">Email (Optional, used for resetting password)</label> <br>
                    <input id="signupemail" type="text" name="signupemail">
                    <span class="infotext" id="infotextemail"></span>
                </div>
                <input type="submit" name="action" value="Sign Up">
            </form>

            <p>Already have an account? <a href='login.php'>Login</a></p>
    _END;

    function validate_username($username) {
        $username_max_chars = 15;

        if (strlen($username) === 0) {
            return "Username cannot be blank";
        }

        if (strlen($username) > $username_max_chars) {
            return "Username must be less than " . $username_max_chars . " characters";
        }

        return "";
    }
    function validate_password($password) {
        $password_min_chars = 2;

        if (strlen($password) === 0) {
            return "Password cannot be blank";
        }

        if (strlen($password) < $password_min_chars) {
            return "Password must be at least " . $password_min_chars . " character" + ($password_min_chars === 1 ? "s" : "");
        }

        //Password regex taken from here: https://stackoverflow.com/a/21456918
        $password_regex = "/^(?=.*[A-Za-z])(?=.*\\d)[A-Za-z\\d]{" . $password_min_chars . ",}$/";
        if (!(preg_match($password_regex, $password))) {
            return "Password must contain at least 1 letter and 1 number";
        }

        return "";
    }
    function validate_email($email) {
        //https://stackoverflow.com/a/9204568
        if ($email !== NULL && strlen($email) > 0 && !preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email))
            return "Invalid email format";

        return "";
    }

    //This code handles the users signing up on the website
    if (isset($_POST['signupuser']) && isset($_POST['signuppass']) && isset($_POST['signuppass2'])) {
        //Sanitize inputs
        $tmp_signup_user = htmlspecialchars($conn->real_escape_string($_POST['signupuser']));
        $tmp_signup_pass = htmlspecialchars($conn->real_escape_string($_POST['signuppass']));
        $tmp_signup_pass_2 = htmlspecialchars($conn->real_escape_string($_POST['signuppass2']));
        $tmp_signup_email = htmlspecialchars($conn->real_escape_string($_POST['signupemail']));
        $tmp_signup_email = strlen($tmp_signup_email) === 0 ? NULL : $tmp_signup_email;

        //Server side validation
        $username_error = validate_username($tmp_signup_user);
        $password_error = validate_password($tmp_signup_pass);
        $confirm_pass = $tmp_signup_pass === $tmp_signup_pass_2;
        $email_error = validate_email($tmp_signup_email);
        if (strlen($username_error) > 0 || strlen($password_error) > 0 || !$confirm_pass || strlen($email_error) > 0) {
            echo <<< _END
                <script>$("#infotextuser").html("$username_error");</script>
            _END;
            echo <<< _END
                <script>$("#infotextpass").html("$password_error");</script>
            _END;
            if (!$confirm_pass) {
                echo <<< _END
                    <script>$("#infotextpass2").html("Passwords do not match");</script>
                _END;
            } else {
                echo <<< _END
                    <script>$("#infotextpass2").html("");</script>
                _END;
            }
            echo <<< _END
                <script>$("#infotextemail").html("$email_error");</script>
            _END;
            die();
        }

        //Hash password
        $hashed_pass = password_hash($tmp_signup_pass, PASSWORD_BCRYPT, ['cost' => 12]);

        //Only the hashed value is stored in the database
        $success = $conn->prepare("INSERT INTO credentials(username, pass) VALUES(?, ?)");
        $success->bind_param("ss", $tmp_signup_user, $hashed_pass);
        try {
            //Store the hashed value
            $success->execute();            
        } catch (mysqli_sql_exception $e) {
            notify_user_and_die("Error: this username already has an account associated with it");
        } finally {
            $success->close();
        }

        //Insert optional email
        if ($tmp_signup_email !== NULL) {
            $user_id = $conn->prepare("SELECT account_id from credentials WHERE username = ?");
            $user_id->bind_param("s", $tmp_signup_user);
            $user_id->execute();
            $user_id = $user_id->get_result()->fetch_array(MYSQLI_NUM)[0];
            $success2 = $conn->prepare("INSERT INTO emails VALUES(?, ?)");
            $success2->bind_param("is", $user_id, $tmp_signup_email);
            try {
                $success2->execute();
            } catch (mysqli_sql_exception $e) {
                $fail = $conn->prepare("DELETE FROM credentials where username = ?");
                $fail->bind_param("s", $tmp_signup_user);
                $fail->execute();
                notify_user_and_die("Error: this email already has an account associated with it");
            } finally {
                $success2->close();
            }
        }

        //Automatically log the newly created account in
        session_start();
        $_SESSION['username'] = $tmp_signup_user;
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];
        header("Location: index.php");
    }
    
    $conn->close();
    echo <<< _END

        </body>
    </html>
    _END;
?>