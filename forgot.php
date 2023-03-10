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
            <form method="post" action="forgot.php" enctype="multipart/form-data">
                <div id="group">
                    <label for="resetuser">Username:</label>
                    <input id="resetuser" type="text" name="resetuser" required>
                    <label for="resetemail">Email associated with account:</label>
                    <input id="resetemail" type="text" name="resetemail" required>
                </div>
                <input type="submit" name="action" value="Submit">
            </form>
            <p class="infotext" id="infomsg"></p>
    _END;

    if (isset($_POST['resetuser']) && isset($_POST['resetemail'])) {
        $user = htmlspecialchars($conn->real_escape_string($_POST['resetuser']));
        $email = htmlspecialchars($conn->real_escape_string($_POST['resetemail']));

        $db_email = $conn->prepare(
            "SELECT email 
            FROM credentials 
            INNER JOIN emails 
            ON credentials.account_id = emails.account_id 
            WHERE credentials.username = ?"
        );
        $db_email->bind_param("s", $user);
        $db_email->execute();
        $db_email = $db_email->get_result()->fetch_array(MYSQLI_NUM);
        if (isset($db_email[0]) && $email === $db_email[0]) {
            $db_email = $db_email[0];
            console_log("Success: " . $user . ", " . $db_email);
            //Handle rest here
        }
        notify_user("If the entered information was correct an email has been sent with further instructions.");
    }

    $conn->close();
    echo <<< _END

        </body>
    </html>
    _END;
?>