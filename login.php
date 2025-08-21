<?php
// Require your single database file. Adjust path if needed.
require 'database/database.php';
session_start();

// Create an instance of the Database class
$db = new Database();

// Redirect if already logged in
if (isset($_SESSION['client_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $login_error = "Invalid username or password."; // Default error message

    // 1. Fetch the user by username using our new method
    $user = $db->getClientByUsername($username);

    // 2. Verify the user exists and the password is correct
    if ($user && password_verify($password, $user['C_password'])) {
        // 3. Check if the user is active
        if (strtolower($user['Status']) !== 'active') {
            $_SESSION['login_error'] = "Account inactive. Please contact admin.";
            header("Location: index.php");
            exit();
        }

        // 4. Regenerate session ID for security
        session_regenerate_id(true);

        // 5. Set all necessary session variables
        $_SESSION['client_id'] = $user['Client_ID'];
        $_SESSION['client_fn'] = $user['Client_fn'];
        // IMPORTANT: Use consistent key 'C_username' to match header.php
        $_SESSION['C_username'] = $user['C_username']; 

        // 6. (Optional) Set status to Active if you want to do this only for already active accounts
        // $db->setClientStatus($user['Client_ID'], 'Active');

        // 7. Set session flag for the success alert on the dashboard
        $_SESSION['login_success'] = true;

        // 8. Redirect to the dashboard
        header("Location: dashboard.php");
        exit();
    }
    
    // If login fails, store error in session and redirect back to homepage
    // This provides a much better user experience than a blank error page.
    $_SESSION['login_error'] = $login_error;
    header("Location: index.php"); // Or whatever page has the login form
    exit();
}
?>