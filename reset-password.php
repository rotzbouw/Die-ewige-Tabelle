<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(!isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$new_password = $confirm_password = $new_password_err = $confirm_password_err = $username = $username_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // validate username
    if(empty(trim($_POST["username"])))
    {
        $username_err = "Please enter your username.";
    }
    else
    {
        $sql = "SELECT * FROM players WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql))
        {
            mysqli_stmt_bind_param($stmt, "s", trim($_POST["username"]));
            if (mysqli_stmt_execute($stmt))
            {
                mysqli_stmt_store_result($stmt);
                if ($stmt->num_rows == 0)
                {
                    $username_err = $_POST["username"] . " could not be found.";
                }
                else
                {
                    $username = trim($_POST["username"]);
                }
            }
        }
    }

    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have atleast 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm the password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check input errors before updating the database
    if(empty($username_err) && empty($new_password_err) && empty($confirm_password_err))
    {
        // Prepare an update statement
        $sql = "UPDATE players SET password = ? WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $param_password, $username);

            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Password updated successfully. Destroy the session, and redirect to login page
                session_destroy();
                header("location: login.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Reset Password</h2>
        <p>Please fill out this form to reset your password.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php
                if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true)
                {
                    $username = $_SESSION["username"];
                }
            ?>
            <div<?php echo (!empty($username_err)) ? 'has-error' : ''; ?>>
                <label>Username</label>
                <input type="username" name="username" value="<?php echo $username; ?>" <?php echo !empty($username) ? 'readonly' : ''; ?>>
                <span><?php echo $username_err; ?></span>
            </div>
            <div <?php echo (!empty($new_password_err)) ? 'has-error' : ''; ?>">
                <label>New Password</label>
                <input type="password" name="new_password" value="<?php echo $new_password; ?>">
                <span><?php echo $new_password_err; ?></span>
            </div>
            <div <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password">
                <span><?php echo $confirm_password_err; ?></span>
            </div>
            <div>
                <input type="submit" value="Submit">
                <a href="index.php">Cancel</a>
            </div>
        </form>
    </div>    
</body>
</html>
