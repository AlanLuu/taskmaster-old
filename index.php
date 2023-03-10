<?php
    require_once "util.php";

    //Variables for the status of tasks
    $todo = "taskstodo";
    $in_progress = "tasksinprogress";
    $completed = "taskscompleted";

    session_start();
    if (isset($_SESSION['username'])) {
        //Prevent session hijacking
        if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] 
        || $_SESSION['ua'] !== $_SERVER['HTTP_USER_AGENT']) {
            //Automatically log out the user if this happens
            header("location:login.php");
            die();
        }

        //Session regeneration to prevent session fixation as shown in class
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id();
            $_SESSION['initiated'] = 1;
        }

        $session_user = htmlspecialchars($_SESSION['username']); //Sanitize username
        echo <<< _END
        <!DOCTYPE html>
        <html>
            <head>
                <style>
                    #$todo p, #$in_progress p, #$completed p {
                        font-size: 20px;
                    }
                    #userbanner {
                        font-size: 25px;
                        display: table;
                        margin: 0 auto;
                        line-height: 0;
                    }
                    #$todo, #$in_progress {
                        float: left;
                        width: 33%;
                        word-wrap: break-word;
                    }
                    #$completed {
                        float: right;
                        width: 33%;
                        word-wrap: break-word;
                    }
                    #$todo button, #$in_progress button, #$completed button {
                        margin-top: 10px;
                    }
                </style>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
                <script src='util.js'></script>
            </head>
            <body>
                <div id='userbanner'>
                    <p id='loggedinuser'>Logged in as <b>$session_user</b> <a href='logout.php'>Log out</a></p>
                </div>

                <form method="post" action="index.php" enctype="multipart/form-data">
                    Enter task to do: <input type="text" name="entertask" required>
                    <input type="submit" name="action" value="Add task">
                </form>

                <div id='$todo'>
                    <h2><u>Tasks to do</u></h2>
                </div>
                <div id='$in_progress'>
                    <h2><u>Tasks in progress</u></h2>
                </div>
                <div id='$completed'>
                    <h2><u>Tasks completed</u></h2>
                </div>
        _END;
    } else {
        //Redirects the user to the login page if not logged in yet
        header("location:login.php");
        die();
    }
    
    $conn = new mysqli($dbHost, $username, $password, $dbName);
    if ($conn->connect_error) process_request_error(mysqli_error($conn));

    //Helper method for inserting a task into the database
    function insert_content($username, $task, $task_status) {
        //table_name has already been sanitized outside of this function
        global $conn;

        /*
            Sanitize username for insertion into SQL

            This variable has already been sanitized with htmlspecialchars,
            outside of this function, but we also need to sanitize for SQL.
        */
        $username = $conn->real_escape_string($username);

        //$task has already been sanitized outside this function

        //Sanitize $task_status just to be safe
        $task_status = htmlspecialchars($conn->real_escape_string($task_status));

        $query = $conn->prepare("INSERT INTO content(task, task_status, username) VALUES(?, ?, ?)");
        $query->bind_param("sss", $task, $task_status, $username);
        $query->execute();
        $query->close();
    }

    function display_content() {
        global $conn, $session_user, $todo, $in_progress, $completed;
        
        $entry = $conn->prepare("SELECT * FROM content WHERE username = ?");
        $entry->bind_param("s", $session_user);
        $entry->execute();
        $entry = $entry->get_result();
        for ($i = 0; $i < $entry->num_rows; $i++) {
            $entry->data_seek($i);
            $row = $entry->fetch_array(MYSQLI_ASSOC);

            $user_task = $row['task'];
            $user_task_status = $row['task_status'];
            $task_id = $row['task_id'];
            if ($user_task_status === $todo) {
                echo "<script>displayTask('$user_task', '$todo', $task_id);</script>";
            } else if ($user_task_status === $in_progress) {
                echo "<script>displayTask('$user_task', '$in_progress', $task_id)</script>";
            } else {
                echo "<script>displayTask('$user_task', '$completed', $task_id)</script>";
            }
        }
        $entry->close();
    }

    //Handles the user input of their tasks
    if (isset($_POST["entertask"])) {
        //Sanitize user input
        $task = htmlspecialchars($conn->real_escape_string($_POST["entertask"]));
        insert_content($session_user, $task, $todo);

        /*
            Prevents the same entry from being inserted multiple times
            if the user refreshes the page
            https://stackoverflow.com/a/15287970
        */
        header("location:index.php");
    }

    display_content();

    /*
        Utilizes data sent from ajax to delete a task from the database
        when the user clicks the delete button next to a task
    */
    if (isset($_POST['task_to_delete'])) {
        $task_id_to_delete = htmlspecialchars($conn->real_escape_string($_POST['task_id']));

        $query = $conn->prepare("DELETE FROM content WHERE task_id = ?");
        $query->bind_param("i", $task_id_to_delete);
        $query->execute();
        $query->close();
    }
    
    /*
        Utilizes data sent from ajax to rename a task
    */
    if (isset($_POST['task_to_rename']) && isset($_POST['new_task_name'])) {
        $task_id_to_rename = htmlspecialchars($conn->real_escape_string($_POST['task_id']));
        $new_task_name = htmlspecialchars($conn->real_escape_string($_POST['new_task_name']));

        $query = $conn->prepare("UPDATE content SET task = ? WHERE task_id = ?");
        $query->bind_param("si", $new_task_name, $task_id_to_rename);
        $query->execute();
        $query->close();
    }

    /*
        Utilizes data sent from ajax to mark a task as completed
    */
    if (isset($_POST['task_to_mark_complete'])) {
        $task_id_to_mark_complete = htmlspecialchars($conn->real_escape_string($_POST['task_id']));

        $query = $conn->prepare("UPDATE content SET task_status = ? WHERE task_id = ?");
        $query->bind_param("si", $completed, $task_id_to_mark_complete);
        $query->execute();
        $query->close();
    }

    /*
        Utilizes data sent from ajax to mark a task as in progress
    */
    if (isset($_POST['task_to_mark_in_progress'])) {
        $task_id_to_mark_in_progress = htmlspecialchars($conn->real_escape_string($_POST['task_id']));

        $query = $conn->prepare("UPDATE content SET task_status = ? WHERE task_id = ?");
        $query->bind_param("si", $in_progress, $task_id_to_mark_in_progress);
        $query->execute();
        $query->close();
    }

    $conn->close();
    echo <<< _END

        </body>
    </html>
    _END;
?>