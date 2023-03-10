const TODO = "taskstodo";
const IN_PROGRESS = "tasksinprogress";
const COMPLETED = "taskscompleted";

/*
    Client side validation
*/
function validate(form) {
    /*
        These functions help validate the inputs that the user enters
        during the signup process
    */
    function validateUsername(name) {
        let usernameMaxChars = 15;

        if (name.length === 0) {
            return "Username cannot be blank";
        }

        if (name.length > usernameMaxChars) {
            return "Username must be less than " + usernameMaxChars + " characters";
        }

        return "";
    }
    function validatePassword(password) {
        let passwordMinChars = 2;
    
        if (password.length === 0) {
            return "Password cannot be blank";
        }
        
        if (password.length < passwordMinChars) {
            return `Password must be at least ${passwordMinChars} character${passwordMinChars !== 1 ? "s" : ""}`;
        }
    
        //Password regex taken from here: https://stackoverflow.com/a/21456918
        let passwordRegex = new RegExp(`^(?=.*[A-Za-z])(?=.*\\d)[A-Za-z\\d]{${passwordMinChars},}$`);
        if (!passwordRegex.test(password)) {
            return "Password must contain at least 1 letter and 1 number";
        }
    
        return "";
    }
    function validateEmail(email) {
        //Simple email address regex taken from here: https://stackoverflow.com/a/9204568
        if (email.length > 0 && !(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)))
            return "Invalid email format";
    
        return "";
    }

    let username = form['signupuser'].value;
    let pass = form['signuppass'].value;
    let pass2 = form['signuppass2'].value;
    let email = form['signupemail'].value;

    let usernameError = validateUsername(username);
    let passError = validatePassword(pass);
    let confirmPass = pass === pass2;
    let emailError = validateEmail(email);
    
    if (usernameError.length > 0 || passError.length > 0 || !confirmPass || emailError.length > 0) {
        $("#infotextuser").html(usernameError);
        $("#infotextpass").html(passError);
        $("#infotextpass2").html(!confirmPass ? "Passwords do not match" : "");
        $("#infotextemail").html(emailError);
        return false;
    }
    return true;
}

/*
    Helper function for creating a button
*/
function createButton(node, name, callback) {
    $("<button>").html(name).click(callback).appendTo(node);
}

/*
    Helper function for sending an ajax request to php
*/
function sendRequest(param) {
    let paramStr = "";
    for (const [key, value] of Object.entries(param)) {
        paramStr += `${key}=${value}&`;
    }
    paramStr = paramStr.substring(0, paramStr.length - 1);
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "index.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send(paramStr);
    xhr.onreadystatechange = () => xhr.readyState === 4 && xhr.status === 200 ? window.location.reload() : {};
}

/*
    This function inserts the tasks that the user enters, along with
    the corresponding buttons to modify those tasks
*/
function displayTask(task, div, task_id) {
    let p = $("<p>").html(task + "<br>");
    createButton(p, "Delete task", () => sendRequest({
        "task_to_delete": task,
        "task_id": task_id
    }));
    createButton(p, "Rename task", () => {
        //Sanitizing this user input occurs in the PHP code
        let renamedTask = window.prompt("Rename this task to what?");
        if (renamedTask !== null) {
            sendRequest({
                "task_to_rename": task,
                "new_task_name": renamedTask,
                "task_id": task_id
            });
        }
    });
    if (div === TODO || div === IN_PROGRESS) {
        createButton(p, "Mark as completed", () => sendRequest({
            "task_to_mark_complete": task,
            "task_id": task_id
        }));
    }
    if (div === TODO) {
        createButton(p, "Mark as in progress", () => sendRequest({
            "task_to_mark_in_progress": task,
            "task_id": task_id
        }));
    }
    p.appendTo("#" + div);
}