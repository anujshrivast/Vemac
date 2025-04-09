<?php
include 'db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role']; // Capture role from the form
    $institute_name = htmlspecialchars($_POST['institute_name']); // Ensure this is retrieved from the form
    $phone = htmlspecialchars($_POST['phone']); // Ensure this is retrieved from the form

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($role) || empty($institute_name) || empty($phone)) {
        die("All fields are required.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into the appropriate table
    $sql = "INSERT INTO users (institute_name, role, name, phone, email, password) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Handle prepare() failure
        die("SQL prepare error: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param('sssiss', $institute_name, $role, $name, $phone, $email, $hashedPassword);

    // Execute the statement
    if (!$stmt->execute()) {
        die("SQL execute error: " . $stmt->error);
    }

    echo "Registration successful for $role!";

    // Optionally redirect to login page after success
    header("Location: login.html");
    exit();

    $stmt->close();
} else {
    die("Invalid request method.");
}
?>
