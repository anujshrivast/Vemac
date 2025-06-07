<?php
// Include database connection securely
require_once 'db_connect.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Improved session handling with security settings
session_start([
    'cookie_lifetime' => 1800, // 30 minutes
    'cookie_secure'   => true,  // Only send cookies over HTTPS
    'cookie_httponly' => true,  // Prevent JavaScript access to cookies
    'cookie_samesite' => 'Lax', // Changed from 'Strict' to 'Lax' for better compatibility
    'use_strict_mode' => true   // Prevent session fixation
]);

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Error reporting configuration (disable display in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Session timeout (30 minutes)
$inactive = 1800;

// Check session timeout
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        header("Location: logout.php");
        exit();
    }
}
$_SESSION['timeout'] = time();

// Authentication check - only allow admin users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: logout.php");
    exit();
}

// Add this to check authentication for all requests
function checkAuthentication() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: logout.php");
        exit();
    }
}

// Call at the start of all secure pages
checkAuthentication();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Input validation functions
function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// User Management Functions
function getAllUsers($conn) {
    $users = [];
    $query = "SELECT id, username, name, email, phone, role, institute_name, status, created_at FROM users";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return $users;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

function getUserById($conn, $user_id) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function addUser($conn, $data) {
    $query = "INSERT INTO users (username, name, email, phone, password, role, institute_name, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    // Hash the password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt->bind_param("ssssssss", 
        $data['username'],
        $data['name'],
        $data['email'],
        $data['phone'],
        $hashed_password,
        $data['role'],
        $data['institute_name'],
        $data['status']
    );

    return $stmt->execute();
}

function updateUser($conn, $user_id, $data) {
    $query = "UPDATE users SET 
                username = ?,
                name = ?,
                email = ?,
                phone = ?,
                role = ?,
                institute_name = ?,
                status = ?
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sssssssi", 
        $data['username'],
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['role'],
        $data['institute_name'],
        $data['status'],
        $user_id
    );

    return $stmt->execute();
}

function deleteUser($conn, $user_id) {
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

function toggleUserStatus($conn, $user_id) {
    try {
        // Get current status
        $query = "SELECT id, status FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['status' => false, 'message' => 'User not found'];
        }

        $user = $result->fetch_assoc();
        $new_status = $user['status'] === 'active' ? 'inactive' : 'active';

        // Update status
        $query = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();

        return ['status' => true, 'new_status' => $new_status, 'message' => 'Status updated successfully'];
    } catch (Exception $e) {
        error_log("User status toggle error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Database error'];
    }
}

// Institute Management Functions
function getAllInstitutes($conn) {
    $institutes = [];
    $query = "SELECT * FROM institutes ORDER BY name";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return $institutes;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $institutes[] = $row;
    }

    return $institutes;
}

function getInstituteById($conn, $institute_id) {
    $query = "SELECT * FROM institutes WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $institute_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function addInstitute($conn, $data) {
    $query = "INSERT INTO institutes (name, address, phone, email, website, status) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssssss", 
        $data['name'],
        $data['address'],
        $data['phone'],
        $data['email'],
        $data['website'],
        $data['status']
    );

    return $stmt->execute();
}

function updateInstitute($conn, $institute_id, $data) {
    $query = "UPDATE institutes SET 
                name = ?,
                address = ?,
                phone = ?,
                email = ?,
                website = ?,
                status = ?
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssssssi", 
        $data['name'],
        $data['address'],
        $data['phone'],
        $data['email'],
        $data['website'],
        $data['status'],
        $institute_id
    );

    return $stmt->execute();
}

function deleteInstitute($conn, $institute_id) {
    $query = "DELETE FROM institutes WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("i", $institute_id);
    return $stmt->execute();
}

function toggleInstituteStatus($conn, $institute_id) {
    try {
        // Get current status
        $query = "SELECT id, status FROM institutes WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $institute_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['status' => false, 'message' => 'Institute not found'];
        }

        $institute = $result->fetch_assoc();
        $new_status = $institute['status'] === 'active' ? 'inactive' : 'active';

        // Update status
        $query = "UPDATE institutes SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $new_status, $institute_id);
        $stmt->execute();

        return ['status' => true, 'new_status' => $new_status, 'message' => 'Status updated successfully'];
    } catch (Exception $e) {
        error_log("Institute status toggle error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Database error'];
    }
}

// Process form submissions with CSRF validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "CSRF token validation failed";
        header("Location: admin.php");
        exit();
    }

    $form_type = $_POST['form_type'] ?? '';
    $errors = [];
    $success = false;

    switch ($form_type) {
        case 'add_user':
            // Validate and sanitize inputs
            $data = [
                'username' => sanitizeInput($_POST['username'] ?? ''),
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'role' => sanitizeInput($_POST['role'] ?? ''),
                'institute_name' => sanitizeInput($_POST['institute_name'] ?? ''),
                'status' => sanitizeInput($_POST['status'] ?? 'active')
            ];

            // Validate required fields
            if (empty($data['username'])) $errors['username'] = 'Username is required';
            if (empty($data['name'])) $errors['name'] = 'Name is required';
            if (!validateEmail($data['email'])) $errors['email'] = 'Valid email is required';
            if (!validatePhone($data['phone'])) $errors['phone'] = 'Valid phone number is required';
            if (empty($data['password'])) $errors['password'] = 'Password is required';
            if (strlen($data['password']) < 8) $errors['password'] = 'Password must be at least 8 characters';
            if (empty($data['role'])) $errors['role'] = 'Role is required';

            if (empty($errors)) {
                if (addUser($conn, $data)) {
                    $_SESSION['success_message'] = "User added successfully!";
                    header("Location: admin.php?section=user-management");
                    exit();
                } else {
                    $errors['database'] = 'Failed to add user';
                }
            }
            break;

        case 'edit_user':
            $user_id = intval($_POST['user_id']);
            $data = [
                'username' => sanitizeInput($_POST['username'] ?? ''),
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'role' => sanitizeInput($_POST['role'] ?? ''),
                'institute_name' => sanitizeInput($_POST['institute_name'] ?? ''),
                'status' => sanitizeInput($_POST['status'] ?? 'active')
            ];

            // Validate required fields
            if (empty($data['username'])) $errors['username'] = 'Username is required';
            if (empty($data['name'])) $errors['name'] = 'Name is required';
            if (!validateEmail($data['email'])) $errors['email'] = 'Valid email is required';
            if (!validatePhone($data['phone'])) $errors['phone'] = 'Valid phone number is required';
            if (empty($data['role'])) $errors['role'] = 'Role is required';

            if (empty($errors)) {
                if (updateUser($conn, $user_id, $data)) {
                    $_SESSION['success_message'] = "User updated successfully!";
                    header("Location: admin.php?section=user-management");
                    exit();
                } else {
                    $errors['database'] = 'Failed to update user';
                }
            }
            break;

        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            if (deleteUser($conn, $user_id)) {
                $_SESSION['success_message'] = "User deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete user";
            }
            header("Location: admin.php?section=user-management");
            exit();
            break;

        case 'toggle_user_status':
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'CSRF token validation failed']);
                exit;
            }
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if ($user_id === false || $user_id === null) {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit;
            }
            $result = toggleUserStatus($conn, $user_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            break;

        case 'add_institute':
            $data = [
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'website' => sanitizeInput($_POST['website'] ?? ''),
                'status' => sanitizeInput($_POST['status'] ?? 'active')
            ];

            // Validate required fields
            if (empty($data['name'])) $errors['name'] = 'Institute name is required';
            if (empty($data['address'])) $errors['address'] = 'Address is required';
            if (!validatePhone($data['phone'])) $errors['phone'] = 'Valid phone number is required';
            if (!empty($data['email']) && !validateEmail($data['email'])) $errors['email'] = 'Valid email is required';

            if (empty($errors)) {
                if (addInstitute($conn, $data)) {
                    $_SESSION['success_message'] = "Institute added successfully!";
                    header("Location: admin.php?section=institute-management");
                    exit();
                } else {
                    $errors['database'] = 'Failed to add institute';
                }
            }
            break;

        case 'edit_institute':
            $institute_id = intval($_POST['institute_id']);
            $data = [
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'website' => sanitizeInput($_POST['website'] ?? ''),
                'status' => sanitizeInput($_POST['status'] ?? 'active')
            ];

            // Validate required fields
            if (empty($data['name'])) $errors['name'] = 'Institute name is required';
            if (empty($data['address'])) $errors['address'] = 'Address is required';
            if (!validatePhone($data['phone'])) $errors['phone'] = 'Valid phone number is required';
            if (!empty($data['email']) && !validateEmail($data['email'])) $errors['email'] = 'Valid email is required';

            if (empty($errors)) {
                if (updateInstitute($conn, $institute_id, $data)) {
                    $_SESSION['success_message'] = "Institute updated successfully!";
                    header("Location: admin.php?section=institute-management");
                    exit();
                } else {
                    $errors['database'] = 'Failed to update institute';
                }
            }
            break;

        case 'delete_institute':
            $institute_id = intval($_POST['institute_id']);
            if (deleteInstitute($conn, $institute_id)) {
                $_SESSION['success_message'] = "Institute deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete institute";
            }
            header("Location: admin.php?section=institute-management");
            exit();
            break;

        case 'toggle_institute_status':
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'CSRF token validation failed']);
                exit;
            }
            $institute_id = filter_input(INPUT_POST, 'institute_id', FILTER_VALIDATE_INT);
            if ($institute_id === false || $institute_id === null) {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Invalid institute ID']);
                exit;
            }
            $result = toggleInstituteStatus($conn, $institute_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            break;

        default:
            $_SESSION['error_message'] = "Unknown form submission";
            header("Location: admin.php");
            exit();
    }

    // If we got here, there were errors - store them in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Get all users and institutes for display
$users = getAllUsers($conn);
$institutes = getAllInstitutes($conn);

// Check for messages in URL
$message = isset($_GET['message']) ? sanitizeInput($_GET['message']) : '';
$section = isset($_GET['section']) ? sanitizeInput($_GET['section']) : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VEMAC</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --body-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --sidebar-bg: #000000;
            --sidebar-text: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --table-header-bg: #f8f9fc;
            --table-row-hover: rgba(0, 0, 0, 0.02);
            --primary-color-rgb: 78, 115, 223;
            --success-color-rgb: 28, 200, 138;
            --warning-color-rgb: 246, 194, 62;
            --danger-color-rgb: 231, 74, 59;
        }

        [data-bs-theme="dark"] {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #2a3042;
            --dark-color: #d1d3e2;
            --body-bg: #1a1a2e;
            --card-bg: #16213e;
            --text-color: #e6e6e6;
            --sidebar-bg: #000000;
            --sidebar-text: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --table-header-bg: #2a3042;
            --table-row-hover: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--body-bg);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar {
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar a {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            border-radius: 5px;
            margin: 5px 10px;
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background-color: var(--sidebar-hover);
            transform: translateX(5px);
        }

        .sidebar a.active {
            background-color: var(--primary-color);
            font-weight: 600;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin 0.3s;
        }

        .card {
            margin-bottom: 20px;
            background-color: var(--card-bg);
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .card-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .form-section {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control,
        .form-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--secondary-color);
            padding: 10px 15px;
            border-radius: 5px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        /* Enhanced Table Styles */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--text-color);
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px 15px;
            position: sticky;
            top: 0;
            border: none;
        }

        .table thead th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 0;
        }

        .table thead th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 0;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:nth-child(even) {
            background-color: rgba(var(--primary-color-rgb), 0.05);
        }

        .table tbody tr:hover {
            background-color: rgba(var(--primary-color-rgb), 0.1);
            transform: translateX(2px);
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table td:first-child {
            border-left: 3px solid transparent;
        }

        .table tr:hover td:first-child {
            border-left: 3px solid var(--primary-color);
        }

        /* Status badges */
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Action buttons */
        .btn-table-action {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
            margin: 2px;
            min-width: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-table-action i {
            margin-right: 5px;
        }

        /* Table responsive container */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Dark mode table adjustments */
        [data-bs-theme="dark"] .table thead th {
            background-color: var(--dark-color);
            color: var(--card-bg);
        }

        [data-bs-theme="dark"] .table tbody tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.03);
        }

        [data-bs-theme="dark"] .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.07);
        }

        [data-bs-theme="dark"] .table td {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .table {
            color: var(--text-color);
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--table-header-bg);
            border-bottom: 2px solid var(--secondary-color);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .table tbody tr:hover {
            background-color: var(--table-row-hover);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .nav-tabs .nav-link {
            color: var(--text-color);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }

        .required-field::after {
            content: " *";
            color: var(--danger-color);
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.875em;
        }

        .success-message {
            color: var(--success-color);
            font-size: 1.1em;
            font-weight: 500;
        }

        /* Toggle Switch Styles */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-right: 0.5em;
        }

        .form-switch .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .form-switch .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Dark mode toggle */
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar a span {
                display: none;
            }

            .sidebar a i {
                margin-right: 0;
                font-size: 1.2rem;
            }

            .main-content {
                margin-left: 70px;
            }

            .sidebar-brand {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px;
                margin: 2px;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-brand {
                display: block;
                text-align: center;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--card-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        /* Animation for cards */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Badge styles */
        .badge {
            padding: 5px 10px;
            font-weight: 600;
            border-radius: 20px;
        }

        /* Status badges */
        .badge-success {
            background-color: var(--success-color);
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: #000;
        }

        .badge-danger {
            background-color: var(--danger-color);
        }

        /* Custom modal styles */
        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: none;
            border-radius: 10px;
        }

        .modal-header {
            border-bottom: 1px solid var(--secondary-color);
        }

        .modal-footer {
            border-top: 1px solid var(--secondary-color);
        }

        /* Custom tab styles */
        .nav-tabs {
            border-bottom: 1px solid var(--secondary-color);
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--primary-color);
        }

        /* Custom input group styles */
        .input-group-text {
            background-color: var(--table-header-bg);
            color: var(--text-color);
            border: 1px solid var(--secondary-color);
        }

        /* Custom pagination */
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-link {
            color: var(--primary-color);
            background-color: var(--card-bg);
            border: 1px solid var(--secondary-color);
        }

        .page-link:hover {
            color: var(--primary-color);
            background-color: var(--table-header-bg);
            border-color: var(--secondary-color);
        }

        /* Custom dropdown styles */
        .dropdown-menu {
            background-color: var(--card-bg);
            border: 1px solid var(--secondary-color);
        }

        .dropdown-item {
            color: var(--text-color);
        }

        .dropdown-item:hover {
            background-color: var(--table-row-hover);
            color: var(--text-color);
        }

        /* Custom progress bar */
        .progress {
            background-color: var(--table-header-bg);
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        /* Custom alert styles */
        .alert {
            border: none;
            color: white;
        }

        .alert-success {
            background-color: var(--success-color);
        }

        .alert-danger {
            background-color: var(--danger-color);
        }

        .alert-info {
            background-color: var(--info-color);
        }

        .alert-warning {
            background-color: var(--warning-color);
            color: #000;
        }

        /* DataTables custom styling */
        .dataTables_wrapper .dataTables_filter input {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--secondary-color);
            padding: 5px 10px;
            border-radius: 4px;
        }

        .dataTables_wrapper .dataTables_length select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--secondary-color);
        }

        .dataTables_wrapper .dataTables_info {
            color: var(--text-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-color) !important;
            border: 1px solid var(--secondary-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: 1px solid var(--primary-color);
        }

        /* Responsive table container */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Loading spinner */
        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        /* Action buttons spacing */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Fixed header for tables */
        .table-fixed-header {
            position: relative;
        }

        .table-fixed-header thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
</head>

<body>
    <!-- Dark Mode Toggle Button -->
    <button class="dark-mode-toggle" id="darkModeToggle">
        <i class="bi bi-moon-fill"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="sidebar-brand text-center py-3">
                    <h3 class="text-white">VEMAC</h3>
                </div>
                <ul class="nav flex-column mt-3">
                    <li>
                        <a href="#" onclick="showSection('dashboard')" class="<?= $section === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('user-management')" class="<?= $section === 'user-management' ? 'active' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('institute-management')" class="<?= $section === 'institute-management' ? 'active' : '' ?>">
                            <i class="bi bi-building"></i>
                            <span>Institute Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('system-settings')" class="<?= $section === 'system-settings' ? 'active' : '' ?>">
                            <i class="bi bi-gear"></i>
                            <span>System Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('reports')" class="<?= $section === 'reports' ? 'active' : '' ?>">
                            <i class="bi bi-graph-up"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="mt-auto">
                        <a href="logout.php" class="logout-link">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Toast Container -->
                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <!-- Toasts will be added here dynamically -->
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Dashboard Panel -->
                <section id="dashboard" class="section" style="<?= $section === 'dashboard' ? '' : 'display: none;' ?>">
                    <div class="container-fluid px-0 mb-2">
                        <div class="row g-4">
                            <div class="col-12 col-md-8">
                                <div class="p-2 h-100">
                                    <h4 class="mb-1">
                                        Welcome, <span class="text-primary fw-bold"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span> 👋
                                    </h4>
                                    <p class="mb-0 text-muted">
                                        You are logged in as <span class="fw-semibold">Administrator</span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 d-flex justify-content-md-end align-items-start">
                                <div class="d-flex gap-2 mt-2 mt-md-0 flex-wrap">
                                    <button class="btn btn-sm btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="bi bi-person-plus me-1"></i> Add User
                                    </button>
                                    <button class="btn btn-sm btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addInstituteModal">
                                        <i class="bi bi-building-add me-1"></i> Add Institute
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border-start border-primary border-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-primary">Total Users</h5>
                                            <p class="card-text display-6 fw-bold"><?= count($users); ?></p>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-people text-primary fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#" onclick="showSection('user-management')" class="text-primary text-decoration-none small">
                                            View users <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-success border-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-success">Active Users</h5>
                                            <p class="card-text display-6 fw-bold">
                                                <?= count(array_filter($users, function($user) { return $user['status'] === 'active'; })); ?>
                                            </p>
                                        </div>
                                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-person-check text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#" onclick="showSection('user-management')" class="text-success text-decoration-none small">
                                            View active users <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-info border-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-info">Institutes</h5>
                                            <p class="card-text display-6 fw-bold"><?= count($institutes); ?></p>
                                        </div>
                                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-building text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#" onclick="showSection('institute-management')" class="text-info text-decoration-none small">
                                            View institutes <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-warning border-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-warning">Active Institutes</h5>
                                            <p class="card-text display-6 fw-bold">
                                                <?= count(array_filter($institutes, function($institute) { return $institute['status'] === 'active'; })); ?>
                                            </p>
                                        </div>
                                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-building-check text-warning fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#" onclick="showSection('institute-management')" class="text-warning text-decoration-none small">
                                            View active institutes <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Recent Users</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php 
                                        $recentUsers = array_slice(array_reverse($users), 0, 5);
                                        if (empty($recentUsers)): ?>
                                            <div class="list-group-item">
                                                <p class="mb-1 text-muted">No recent users found</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <a href="#" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?= htmlspecialchars($user['name']) ?></h6>
                                                        <small><?= date('M d, Y', strtotime($user['created_at'] ?? 'now')) ?></small>
                                                    </div>
                                                    <p class="mb-1"><?= htmlspecialchars($user['role']) ?></p>
                                                    <small><?= htmlspecialchars($user['institute_name'] ?? 'No institute') ?></small>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Recent Institutes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php 
                                        $recentInstitutes = array_slice(array_reverse($institutes), 0, 5);
                                        if (empty($recentInstitutes)): ?>
                                            <div class="list-group-item">
                                                <p class="mb-1 text-muted">No recent institutes found</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($recentInstitutes as $institute): ?>
                                                <a href="#" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?= htmlspecialchars($institute['name']) ?></h6>
                                                        <small><?= date('M d, Y', strtotime($institute['created_at'] ?? 'now')) ?></small>
                                                    </div>
                                                    <p class="mb-1"><?= htmlspecialchars($institute['email']) ?></p>
                                                    <small><?= htmlspecialchars($institute['phone']) ?></small>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- User Management Section -->
                <section id="user-management" class="section" style="<?= $section === 'user-management' ? '' : 'display: none;' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">User Management</h2>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> Add User
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                            <?php endif; ?>

                            <!-- Users Table -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Institute</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <span class="text-muted">#</span><?= htmlspecialchars($user['id']) ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-placeholder rounded-circle me-2 bg-light text-dark d-flex align-items-center justify-content-center"
                                                            style="width: 32px; height: 32px;">
                                                            <?= substr(htmlspecialchars($user['name']), 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                            <div class="text-muted small">@<?= htmlspecialchars($user['username']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $user['role'] === 'admin' ? 'primary' : 
                                                        ($user['role'] === 'office' ? 'info' : 'secondary') 
                                                    ?>">
                                                        <?= ucfirst($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($user['institute_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <div><?= htmlspecialchars($user['email']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?> border border-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?> border-opacity-25">
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap">
                                                        <button class="btn btn-table-action btn-outline-primary btn-sm"
                                                                onclick="editUser(<?= $user['id'] ?>)">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <button class="btn btn-table-action btn-outline-danger btn-sm ms-2"
                                                                onclick="confirmDeleteUser(<?= $user['id'] ?>)">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                        <div class="form-check form-switch ms-2 d-flex align-items-center">
                                                            <input class="form-check-input user-status-toggle"
                                                                type="checkbox"
                                                                role="switch"
                                                                id="userStatusToggle<?= $user['id'] ?>"
                                                                data-user-id="<?= $user['id'] ?>"
                                                                <?= $user['status'] === 'active' ? 'checked' : '' ?>>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Institute Management Section -->
                <section id="institute-management" class="section" style="<?= $section === 'institute-management' ? '' : 'display: none;' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Institute Management</h2>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInstituteModal">
                            <i class="bi bi-building-add"></i> Add Institute
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <!-- Institutes Table -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="institutesTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Institute</th>
                                            <th>Contact</th>
                                            <th>Website</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($institutes as $institute): ?>
                                            <tr>
                                                <td>
                                                    <span class="text-muted">#</span><?= htmlspecialchars($institute['id']) ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($institute['name']) ?></strong>
                                                    <div class="text-muted small"><?= htmlspecialchars($institute['address']) ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($institute['email']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($institute['phone']) ?></div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($institute['website'])): ?>
                                                        <a href="<?= htmlspecialchars($institute['website']) ?>" target="_blank" class="text-decoration-none">
                                                            <?= htmlspecialchars(parse_url($institute['website'], PHP_URL_HOST)) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $institute['status'] === 'active' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $institute['status'] === 'active' ? 'success' : 'secondary' ?> border border-<?= $institute['status'] === 'active' ? 'success' : 'secondary' ?> border-opacity-25">
                                                        <?= ucfirst($institute['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap">
                                                        <button class="btn btn-table-action btn-outline-primary btn-sm"
                                                                onclick="editInstitute(<?= $institute['id'] ?>)">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <button class="btn btn-table-action btn-outline-danger btn-sm ms-2"
                                                                onclick="confirmDeleteInstitute(<?= $institute['id'] ?>)">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                        <div class="form-check form-switch ms-2 d-flex align-items-center">
                                                            <input class="form-check-input institute-status-toggle"
                                                                type="checkbox"
                                                                role="switch"
                                                                id="instituteStatusToggle<?= $institute['id'] ?>"
                                                                data-institute-id="<?= $institute['id'] ?>"
                                                                <?= $institute['status'] === 'active' ? 'checked' : '' ?>>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- System Settings Section -->
                <section id="system-settings" class="section" style="<?= $section === 'system-settings' ? '' : 'display: none;' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">System Settings</h2>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form id="systemSettingsForm">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">General Settings</h5>
                                        <div class="mb-3">
                                            <label for="systemName" class="form-label">System Name</label>
                                            <input type="text" class="form-control" id="systemName" value="VEMAC">
                                        </div>
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-select" id="timezone">
                                                <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                                <option value="UTC">UTC</option>
                                                <!-- Add more timezones as needed -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Security Settings</h5>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable2FA" checked>
                                                <label class="form-check-label" for="enable2FA">Enable Two-Factor Authentication</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="passwordComplexity" checked>
                                                <label class="form-check-label" for="passwordComplexity">Require Complex Passwords</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="sessionTimeout" checked>
                                                <label class="form-check-label" for="sessionTimeout">Enable Session Timeout (30 min)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Save Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Reports Section -->
                <section id="reports" class="section" style="<?= $section === 'reports' ? '' : 'display: none;' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Reports</h2>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">User Activity Report</h5>
                                    <div class="mb-3">
                                        <label for="userActivityDateRange" class="form-label">Date Range</label>
                                        <select class="form-select" id="userActivityDateRange">
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month" selected>This Month</option>
                                            <option value="year">This Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary" id="generateUserActivityReport">
                                        <i class="bi bi-file-earmark-bar-graph"></i> Generate Report
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Institute Usage Report</h5>
                                    <div class="mb-3">
                                        <label for="instituteReportSelect" class="form-label">Institute</label>
                                        <select class="form-select" id="instituteReportSelect">
                                            <option value="all">All Institutes</option>
                                            <?php foreach ($institutes as $institute): ?>
                                                <option value="<?= $institute['id'] ?>"><?= htmlspecialchars($institute['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary" id="generateInstituteReport">
                                        <i class="bi bi-building-gear"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <div id="reportResultsContainer">
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-graph-up fs-1"></i>
                                    <p>Generate a report to view data</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="addUserForm" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="form_type" value="add_user">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label required-field">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                            <div class="error-message" id="usernameError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label required-field">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                            <div class="error-message" id="nameError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label required-field">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                            <div class="error-message" id="emailError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label required-field">Phone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                            <div class="error-message" id="phoneError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label required-field">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="error-message" id="passwordError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirmPassword" class="form-label required-field">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                            <div class="error-message" id="confirmPasswordError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label required-field">Role</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="">Select Role</option>
                                                <option value="admin">Administrator</option>
                                                <option value="office">Office Incharge</option>
                                                <option value="teacher">Teacher</option>
                                            </select>
                                            <div class="error-message" id="roleError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_name" class="form-label">Institute</label>
                                            <select class="form-select" id="institute_name" name="institute_name">
                                                <option value="">Select Institute</option>
                                                <?php foreach ($institutes as $institute): ?>
                                                    <option value="<?= htmlspecialchars($institute['name']) ?>"><?= htmlspecialchars($institute['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="editUserForm" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="form_type" value="edit_user">
                                <input type="hidden" id="edit_user_id" name="user_id">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_username" class="form-label required-field">Username</label>
                                            <input type="text" class="form-control" id="edit_username" name="username" required>
                                            <div class="error-message" id="editUsernameError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_name" class="form-label required-field">Full Name</label>
                                            <input type="text" class="form-control" id="edit_name" name="name" required>
                                            <div class="error-message" id="editNameError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_email" class="form-label required-field">Email</label>
                                            <input type="email" class="form-control" id="edit_email" name="email" required>
                                            <div class="error-message" id="editEmailError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_phone" class="form-label required-field">Phone</label>
                                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                                            <div class="error-message" id="editPhoneError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_role" class="form-label required-field">Role</label>
                                            <select class="form-select" id="edit_role" name="role" required>
                                                <option value="">Select Role</option>
                                                <option value="admin">Administrator</option>
                                                <option value="office">Office Incharge</option>
                                                <option value="teacher">Teacher</option>
                                            </select>
                                            <div class="error-message" id="editRoleError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_name" class="form-label">Institute</label>
                                            <select class="form-select" id="edit_institute_name" name="institute_name">
                                                <option value="">Select Institute</option>
                                                <?php foreach ($institutes as $institute): ?>
                                                    <option value="<?= htmlspecialchars($institute['name']) ?>"><?= htmlspecialchars($institute['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_status" class="form-label">Status</label>
                                            <select class="form-select" id="edit_status" name="status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Institute Modal -->
                <div class="modal fade" id="addInstituteModal" tabindex="-1" aria-labelledby="addInstituteModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addInstituteModalLabel">Add New Institute</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="addInstituteForm" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="form_type" value="add_institute">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_name" class="form-label required-field">Institute Name</label>
                                            <input type="text" class="form-control" id="institute_name" name="name" required>
                                            <div class="error-message" id="instituteNameError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_phone" class="form-label required-field">Phone</label>
                                            <input type="tel" class="form-control" id="institute_phone" name="phone" required>
                                            <div class="error-message" id="institutePhoneError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="institute_email" name="email">
                                            <div class="error-message" id="instituteEmailError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_website" class="form-label">Website</label>
                                            <input type="url" class="form-control" id="institute_website" name="website">
                                            <div class="error-message" id="instituteWebsiteError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="institute_address" class="form-label required-field">Address</label>
                                            <textarea class="form-control" id="institute_address" name="address" rows="3" required></textarea>
                                            <div class="error-message" id="instituteAddressError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="institute_status" class="form-label">Status</label>
                                            <select class="form-select" id="institute_status" name="status">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Institute</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Institute Modal -->
                <div class="modal fade" id="editInstituteModal" tabindex="-1" aria-labelledby="editInstituteModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editInstituteModalLabel">Edit Institute</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="editInstituteForm" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="form_type" value="edit_institute">
                                <input type="hidden" id="edit_institute_id" name="institute_id">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_name" class="form-label required-field">Institute Name</label>
                                            <input type="text" class="form-control" id="edit_institute_name" name="name" required>
                                            <div class="error-message" id="editInstituteNameError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_phone" class="form-label required-field">Phone</label>
                                            <input type="tel" class="form-control" id="edit_institute_phone" name="phone" required>
                                            <div class="error-message" id="editInstitutePhoneError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="edit_institute_email" name="email">
                                            <div class="error-message" id="editInstituteEmailError"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_website" class="form-label">Website</label>
                                            <input type="url" class="form-control" id="edit_institute_website" name="website">
                                            <div class="error-message" id="editInstituteWebsiteError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="edit_institute_address" class="form-label required-field">Address</label>
                                            <textarea class="form-control" id="edit_institute_address" name="address" rows="3" required></textarea>
                                            <div class="error-message" id="editInstituteAddressError"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_institute_status" class="form-label">Status</label>
                                            <select class="form-select" id="edit_institute_status" name="status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Update Institute</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- JavaScript Libraries -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

                <script>
                    // ======================
                    // DOM Ready Initialization
                    // ======================
                    $(document).ready(function() {
                        // Initialize DataTables for all tables
                        $('#usersTable, #institutesTable').DataTable({
                            responsive: true,
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search...",
                            },
                            dom: '<"top"f>rt<"bottom"lip><"clear">',
                            pageLength: 25,
                            initComplete: function() {
                                // Apply theme-specific styling after table initialization
                                this.api().columns().every(function() {
                                    const column = this;
                                    $('input', this.header()).on('keyup change', function() {
                                        if (column.search() !== this.value) {
                                            column.search(this.value).draw();
                                        }
                                    });
                                });
                            }
                        });

                        // Initialize tooltips
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function(tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });

                        // Initialize dark mode from localStorage
                        initDarkMode();

                        // Initialize form validation
                        initFormValidation();
                    });

                    // ======================
                    // Section Navigation
                    // ======================
                    function showSection(sectionId) {
                        // Update URL without reloading
                        const url = new URL(window.location.href);
                        url.searchParams.set('section', sectionId);
                        window.history.pushState({}, '', url);

                        // Hide all sections
                        document.querySelectorAll('.section').forEach(section => {
                            section.style.display = 'none';
                        });

                        // Update active link in sidebar
                        document.querySelectorAll('.sidebar a').forEach(link => {
                            link.classList.remove('active');
                        });

                        // Find and activate corresponding link
                        const links = document.querySelectorAll('.sidebar a');
                        links.forEach(link => {
                            if (link.getAttribute('onclick')?.includes(sectionId)) {
                                link.classList.add('active');
                            }
                        });

                        // Show the selected section
                        const sectionToShow = document.getElementById(sectionId);
                        if (sectionToShow) {
                            sectionToShow.style.display = 'block';

                            // Check if table exists and is a DataTable before trying to recalc
                            const table = sectionToShow.querySelector('table');
                            if (table && $.fn.DataTable.isDataTable(table)) {
                                try {
                                    $(table).DataTable().columns.adjust().responsive.recalc();
                                } catch (e) {
                                    console.error('DataTable recalculation error:', e);
                                }
                            }
                        }

                        // Scroll to top
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }

                    // ======================
                    // Form Handling
                    // ======================
                    function initFormValidation() {
                        // Add User Form Validation
                        document.getElementById('addUserForm')?.addEventListener('submit', function(e) {
                            if (!validateAddUserForm()) {
                                e.preventDefault();
                            }
                        });

                        // Edit User Form Validation
                        document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
                            if (!validateEditUserForm()) {
                                e.preventDefault();
                            }
                        });

                        // Add Institute Form Validation
                        document.getElementById('addInstituteForm')?.addEventListener('submit', function(e) {
                            if (!validateAddInstituteForm()) {
                                e.preventDefault();
                            }
                        });

                        // Edit Institute Form Validation
                        document.getElementById('editInstituteForm')?.addEventListener('submit', function(e) {
                            if (!validateEditInstituteForm()) {
                                e.preventDefault();
                            }
                        });
                    }

                    function validateAddUserForm() {
                        let isValid = true;
                        const form = document.getElementById('addUserForm');
                        
                        // Clear previous errors
                        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                        
                        // Validate username
                        const username = form.querySelector('#username');
                        if (!username.value.trim()) {
                            form.querySelector('#usernameError').textContent = 'Username is required';
                            isValid = false;
                        }
                        
                        // Validate name
                        const name = form.querySelector('#name');
                        if (!name.value.trim()) {
                            form.querySelector('#nameError').textContent = 'Full name is required';
                            isValid = false;
                        }
                        
                        // Validate email
                        const email = form.querySelector('#email');
                        if (!email.value.trim()) {
                            form.querySelector('#emailError').textContent = 'Email is required';
                            isValid = false;
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            form.querySelector('#emailError').textContent = 'Valid email is required';
                            isValid = false;
                        }
                        
                        // Validate phone
                        const phone = form.querySelector('#phone');
                        if (!phone.value.trim()) {
                            form.querySelector('#phoneError').textContent = 'Phone is required';
                            isValid = false;
                        } else if (!/^\d{10}$/.test(phone.value)) {
                            form.querySelector('#phoneError').textContent = 'Valid phone number is required';
                            isValid = false;
                        }
                        
                        // Validate password
                        const password = form.querySelector('#password');
                        if (!password.value.trim()) {
                            form.querySelector('#passwordError').textContent = 'Password is required';
                            isValid = false;
                        } else if (password.value.length < 8) {
                            form.querySelector('#passwordError').textContent = 'Password must be at least 8 characters';
                            isValid = false;
                        }
                        
                        // Validate confirm password
                        const confirmPassword = form.querySelector('#confirmPassword');
                        if (!confirmPassword.value.trim()) {
                            form.querySelector('#confirmPasswordError').textContent = 'Please confirm password';
                            isValid = false;
                        } else if (confirmPassword.value !== password.value) {
                            form.querySelector('#confirmPasswordError').textContent = 'Passwords do not match';
                            isValid = false;
                        }
                        
                        // Validate role
                        const role = form.querySelector('#role');
                        if (!role.value) {
                            form.querySelector('#roleError').textContent = 'Role is required';
                            isValid = false;
                        }
                        
                        if (!isValid) {
                            showToast('Please fill all required fields correctly', 'warning');
                            scrollToFirstInvalid(form);
                        }
                        
                        return isValid;
                    }

                    function validateEditUserForm() {
                        let isValid = true;
                        const form = document.getElementById('editUserForm');
                        
                        // Clear previous errors
                        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                        
                        // Validate username
                        const username = form.querySelector('#edit_username');
                        if (!username.value.trim()) {
                            form.querySelector('#editUsernameError').textContent = 'Username is required';
                            isValid = false;
                        }
                        
                        // Validate name
                        const name = form.querySelector('#edit_name');
                        if (!name.value.trim()) {
                            form.querySelector('#editNameError').textContent = 'Full name is required';
                            isValid = false;
                        }
                        
                        // Validate email
                        const email = form.querySelector('#edit_email');
                        if (!email.value.trim()) {
                            form.querySelector('#editEmailError').textContent = 'Email is required';
                            isValid = false;
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            form.querySelector('#editEmailError').textContent = 'Valid email is required';
                            isValid = false;
                        }
                        
                        // Validate phone
                        const phone = form.querySelector('#edit_phone');
                        if (!phone.value.trim()) {
                            form.querySelector('#editPhoneError').textContent = 'Phone is required';
                            isValid = false;
                        } else if (!/^\d{10}$/.test(phone.value)) {
                            form.querySelector('#editPhoneError').textContent = 'Valid phone number is required';
                            isValid = false;
                        }
                        
                        // Validate role
                        const role = form.querySelector('#edit_role');
                        if (!role.value) {
                            form.querySelector('#editRoleError').textContent = 'Role is required';
                            isValid = false;
                        }
                        
                        if (!isValid) {
                            showToast('Please fill all required fields correctly', 'warning');
                            scrollToFirstInvalid(form);
                        }
                        
                        return isValid;
                    }

                    function validateAddInstituteForm() {
                        let isValid = true;
                        const form = document.getElementById('addInstituteForm');
                        
                        // Clear previous errors
                        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                        
                        // Validate name
                        const name = form.querySelector('#institute_name');
                        if (!name.value.trim()) {
                            form.querySelector('#instituteNameError').textContent = 'Institute name is required';
                            isValid = false;
                        }
                        
                        // Validate phone
                        const phone = form.querySelector('#institute_phone');
                        if (!phone.value.trim()) {
                            form.querySelector('#institutePhoneError').textContent = 'Phone is required';
                            isValid = false;
                        } else if (!/^\d{10}$/.test(phone.value)) {
                            form.querySelector('#institutePhoneError').textContent = 'Valid phone number is required';
                            isValid = false;
                        }
                        
                        // Validate email if provided
                        const email = form.querySelector('#institute_email');
                        if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            form.querySelector('#instituteEmailError').textContent = 'Valid email is required';
                            isValid = false;
                        }
                        
                        // Validate website if provided
                        const website = form.querySelector('#institute_website');
                        if (website.value.trim() && !/^https?:\/\/.+\..+/.test(website.value)) {
                            form.querySelector('#instituteWebsiteError').textContent = 'Valid website URL is required';
                            isValid = false;
                        }
                        
                        // Validate address
                        const address = form.querySelector('#institute_address');
                        if (!address.value.trim()) {
                            form.querySelector('#instituteAddressError').textContent = 'Address is required';
                            isValid = false;
                        }
                        
                        if (!isValid) {
                            showToast('Please fill all required fields correctly', 'warning');
                            scrollToFirstInvalid(form);
                        }
                        
                        return isValid;
                    }

                    function validateEditInstituteForm() {
                        let isValid = true;
                        const form = document.getElementById('editInstituteForm');
                        
                        // Clear previous errors
                        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                        
                        // Validate name
                        const name = form.querySelector('#edit_institute_name');
                        if (!name.value.trim()) {
                            form.querySelector('#editInstituteNameError').textContent = 'Institute name is required';
                            isValid = false;
                        }
                        
                        // Validate phone
                        const phone = form.querySelector('#edit_institute_phone');
                        if (!phone.value.trim()) {
                            form.querySelector('#editInstitutePhoneError').textContent = 'Phone is required';
                            isValid = false;
                        } else if (!/^\d{10}$/.test(phone.value)) {
                            form.querySelector('#editInstitutePhoneError').textContent = 'Valid phone number is required';
                            isValid = false;
                        }
                        
                        // Validate email if provided
                        const email = form.querySelector('#edit_institute_email');
                        if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            form.querySelector('#editInstituteEmailError').textContent = 'Valid email is required';
                            isValid = false;
                        }
                        
                        // Validate website if provided
                        const website = form.querySelector('#edit_institute_website');
                        if (website.value.trim() && !/^https?:\/\/.+\..+/.test(website.value)) {
                            form.querySelector('#editInstituteWebsiteError').textContent = 'Valid website URL is required';
                            isValid = false;
                        }
                        
                        // Validate address
                        const address = form.querySelector('#edit_institute_address');
                        if (!address.value.trim()) {
                            form.querySelector('#editInstituteAddressError').textContent = 'Address is required';
                            isValid = false;
                        }
                        
                        if (!isValid) {
                            showToast('Please fill all required fields correctly', 'warning');
                            scrollToFirstInvalid(form);
                        }
                        
                        return isValid;
                    }

                    function scrollToFirstInvalid(form) {
                        const firstInvalid = form.querySelector('.error-message:not(:empty)');
                        if (firstInvalid) {
                            const input = firstInvalid.previousElementSibling;
                            input.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            input.focus();
                        }
                    }

                    // ======================
                    // User Management Functions
                    // ======================
                    function editUser(userId) {
                        fetch(`get_user.php?id=${userId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const user = data.user;
                                    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                                    
                                    // Populate form fields
                                    document.getElementById('edit_user_id').value = user.id;
                                    document.getElementById('edit_username').value = user.username;
                                    document.getElementById('edit_name').value = user.name;
                                    document.getElementById('edit_email').value = user.email;
                                    document.getElementById('edit_phone').value = user.phone;
                                    document.getElementById('edit_role').value = user.role;
                                    document.getElementById('edit_institute_name').value = user.institute_name || '';
                                    document.getElementById('edit_status').value = user.status;
                                    
                                    // Show modal
                                    modal.show();
                                } else {
                                    showToast(data.message || 'Error loading user data', 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('Network error loading user data', 'danger');
                            });
                    }

                    function confirmDeleteUser(userId) {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Create form data with CSRF token
                                const formData = new FormData();
                                formData.append('form_type', 'delete_user');
                                formData.append('user_id', userId);
                                formData.append('csrf_token', '<?= $csrf_token ?>');

                                fetch('admin.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        if (!response.ok) throw new Error('Network response was not ok');
                                        return response.text();
                                    })
                                    .then(() => {
                                        showToast('User deleted successfully', 'success');
                                        setTimeout(() => location.reload(), 1500);
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showToast('Error deleting user', 'danger');
                                    });
                            }
                        });
                    }

                    // User status toggle
                    document.querySelectorAll('.user-status-toggle').forEach(toggle => {
                        toggle.addEventListener('change', function() {
                            const userId = this.dataset.userId;
                            const formData = new FormData();
                            formData.append('form_type', 'toggle_user_status');
                            formData.append('user_id', userId);
                            formData.append('csrf_token', '<?= $csrf_token ?>');

                            fetch('admin.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status) {
                                        showToast(data.message || 'Status updated', 'success');
                                    } else {
                                        showToast(data.message || 'Failed to update status', 'danger');
                                        // Revert the toggle if failed
                                        this.checked = !this.checked;
                                    }
                                })
                                .catch(() => {
                                    showToast('Network error', 'danger');
                                    this.checked = !this.checked;
                                });
                        });
                    });

                    // ======================
                    // Institute Management Functions
                    // ======================
                    function editInstitute(instituteId) {
                        fetch(`get_institute.php?id=${instituteId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const institute = data.institute;
                                    const modal = new bootstrap.Modal(document.getElementById('editInstituteModal'));
                                    
                                    // Populate form fields
                                    document.getElementById('edit_institute_id').value = institute.id;
                                    document.getElementById('edit_institute_name').value = institute.name;
                                    document.getElementById('edit_institute_phone').value = institute.phone;
                                    document.getElementById('edit_institute_email').value = institute.email || '';
                                    document.getElementById('edit_institute_website').value = institute.website || '';
                                    document.getElementById('edit_institute_address').value = institute.address;
                                    document.getElementById('edit_institute_status').value = institute.status;
                                    
                                    // Show modal
                                    modal.show();
                                } else {
                                    showToast(data.message || 'Error loading institute data', 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('Network error loading institute data', 'danger');
                            });
                    }

                    function confirmDeleteInstitute(instituteId) {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this! All users associated with this institute will be affected.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Create form data with CSRF token
                                const formData = new FormData();
                                formData.append('form_type', 'delete_institute');
                                formData.append('institute_id', instituteId);
                                formData.append('csrf_token', '<?= $csrf_token ?>');

                                fetch('admin.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        if (!response.ok) throw new Error('Network response was not ok');
                                        return response.text();
                                    })
                                    .then(() => {
                                        showToast('Institute deleted successfully', 'success');
                                        setTimeout(() => location.reload(), 1500);
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showToast('Error deleting institute', 'danger');
                                    });
                            }
                        });
                    }

                    // Institute status toggle
                    document.querySelectorAll('.institute-status-toggle').forEach(toggle => {
                        toggle.addEventListener('change', function() {
                            const instituteId = this.dataset.instituteId;
                            const formData = new FormData();
                            formData.append('form_type', 'toggle_institute_status');
                            formData.append('institute_id', instituteId);
                            formData.append('csrf_token', '<?= $csrf_token ?>');

                            fetch('admin.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status) {
                                        showToast(data.message || 'Status updated', 'success');
                                    } else {
                                        showToast(data.message || 'Failed to update status', 'danger');
                                        // Revert the toggle if failed
                                        this.checked = !this.checked;
                                    }
                                })
                                .catch(() => {
                                    showToast('Network error', 'danger');
                                    this.checked = !this.checked;
                                });
                        });
                    });

                    // ======================
                    // Reports Functions
                    // ======================
                    document.getElementById('generateUserActivityReport')?.addEventListener('click', function() {
                        const dateRange = document.getElementById('userActivityDateRange').value;
                        
                        // Show loading state
                        const container = document.getElementById('reportResultsContainer');
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Generating report...</p>
                            </div>
                        `;
                        
                        // Simulate API call
                        setTimeout(() => {
                            container.innerHTML = `
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">User Activity Report</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>User</th>
                                                        <th>Last Login</th>
                                                        <th>Activity Count</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Admin User</td>
                                                        <td>${new Date().toLocaleString()}</td>
                                                        <td>42</td>
                                                        <td><span class="badge bg-success">Active</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Office User</td>
                                                        <td>${new Date(Date.now() - 86400000).toLocaleString()}</td>
                                                        <td>15</td>
                                                        <td><span class="badge bg-success">Active</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Teacher User</td>
                                                        <td>${new Date(Date.now() - 2592000000).toLocaleString()}</td>
                                                        <td>3</td>
                                                        <td><span class="badge bg-secondary">Inactive</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary">
                                                <i class="bi bi-download"></i> Download Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }, 1500);
                    });

                    document.getElementById('generateInstituteReport')?.addEventListener('click', function() {
                        const instituteId = document.getElementById('instituteReportSelect').value;
                        
                        // Show loading state
                        const container = document.getElementById('reportResultsContainer');
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Generating report...</p>
                            </div>
                        `;
                        
                        // Simulate API call
                        setTimeout(() => {
                            container.innerHTML = `
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Institute Usage Report</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Summary</h6>
                                                <ul class="list-group mb-3">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Total Users
                                                        <span class="badge bg-primary rounded-pill">12</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Active Users
                                                        <span class="badge bg-success rounded-pill">8</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Students
                                                        <span class="badge bg-info rounded-pill">150</span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Activity</h6>
                                                <ul class="list-group mb-3">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Logins (30 days)
                                                        <span class="badge bg-primary rounded-pill">42</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Fee Transactions
                                                        <span class="badge bg-success rounded-pill">₹15,420</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Attendance Records
                                                        <span class="badge bg-warning rounded-pill">1,250</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary">
                                                <i class="bi bi-download"></i> Download Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }, 1500);
                    });

                    // ======================
                    // UI Components
                    // ======================
                    // Toast notifications
                    function showToast(message, type) {
                        const toastContainer = document.querySelector('.toast-container');
                        const toastId = 'toast-' + Date.now();
                        const toast = document.createElement('div');

                        toast.id = toastId;
                        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
                        toast.setAttribute('role', 'alert');
                        toast.setAttribute('aria-live', 'assertive');
                        toast.setAttribute('aria-atomic', 'true');

                        toast.innerHTML = `
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="bi ${type === 'success' ? 'bi-check-circle' : 
                                      type === 'danger' ? 'bi-exclamation-triangle' : 
                                      type === 'warning' ? 'bi-exclamation-circle' : 'bi-info-circle'} me-2"></i>
                                    ${message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        `;

                        toastContainer.appendChild(toast);

                        // Auto-remove after delay
                        setTimeout(() => {
                            const bsToast = bootstrap.Toast.getOrCreateInstance(toast);
                            bsToast.hide();
                            toast.addEventListener('hidden.bs.toast', () => toast.remove());
                        }, 5000);

                        // Position toasts
                        const toasts = document.querySelectorAll('.toast');
                        toasts.forEach((t, i) => {
                            t.style.bottom = `${i * 60 + 20}px`;
                        });
                    }

                    // ========================
                    // Dark mode functionality
                    // ========================
                    function initDarkMode() {
                        const darkModeToggle = document.getElementById('darkModeToggle');
                        const htmlElement = document.documentElement;

                        // Check for saved preference or use OS preference
                        const savedTheme = localStorage.getItem('theme') ||
                            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

                        // Apply the saved theme
                        if (savedTheme === 'dark') {
                            htmlElement.setAttribute('data-bs-theme', 'dark');
                            darkModeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
                        } else {
                            htmlElement.removeAttribute('data-bs-theme');
                            darkModeToggle.innerHTML = '<i class="bi bi-moon-fill"></i>';
                        }

                        // Toggle dark mode
                        darkModeToggle.addEventListener('click', function() {
                            const html = document.documentElement;
                            const isDark = html.getAttribute('data-bs-theme') === 'dark';

                            if (isDark) {
                                html.removeAttribute('data-bs-theme');
                                localStorage.setItem('theme', 'light');
                                this.innerHTML = '<i class="bi bi-moon-fill"></i>';
                            } else {
                                html.setAttribute('data-bs-theme', 'dark');
                                localStorage.setItem('theme', 'dark');
                                this.innerHTML = '<i class="bi bi-sun-fill"></i>';
                            }

                            // Force redraw
                            html.style.display = 'none';
                            html.offsetHeight; // Trigger reflow
                            html.style.display = '';
                        });
                    }

                    // ======================
                    // logout confirmation
                    // ======================

                    document.querySelectorAll('.logout-link').forEach(link => {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            Swal.fire({
                                title: 'Are you sure?',
                                text: "You will be logged out of the system",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, logout!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'logout.php';
                                }
                            });
                        });
                    });
                </script>
</body>

</html>