<?php


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']); // Remove non-numeric characters
    $subjects = htmlspecialchars($_POST['subjects']);
    $role = $_POST['role']; // Corrected field name

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($subjects) || empty($role)) {
        die("All fields are required.");
    }

    // Prepare SQL query based on role
    if ($role === 'student') {
        $grade = isset($_POST['grade']) ? htmlspecialchars($_POST['grade']) : null;
        $sql = "INSERT INTO inquiries (role, name, email, phone, subjects, grade) VALUES (:role, :name, :email, :phone, :subjects, :grade)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':grade', $grade);
    } elseif ($role === 'teacher') {
        $qualification = isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : null;
        $preferredTime = isset($_POST['preferred_time']) ? $_POST['preferred_time'] : null;
        $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : null;
        $cv = null;

        // Handle file upload if a file was provided
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $cv = $_FILES['cv']['name']; // Get the file name
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            $fileExtension = strtolower(pathinfo($cv, PATHINFO_EXTENSION));

            // Validate file extension
            if (!in_array($fileExtension, $allowedExtensions)) {
                die("Invalid file format. Only PDF, DOC, and DOCX files are allowed.");
            }

            // Move uploaded file to a directory
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uploadPath = $uploadDir . basename($cv);
            if (!move_uploaded_file($_FILES['cv']['tmp_name'], $uploadPath)) {
                die("File upload failed.");
            }
        }

        $sql = "INSERT INTO inquiries (role, name, email, phone, subjects, qualification, preferred_time, message, cv_file) VALUES (:role, :name, :email, :phone, :subjects, :qualification, :preferredTime, :message, :cvFile)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':qualification', $qualification);
        $stmt->bindParam(':preferredTime', $preferredTime);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':cvFile', $cv);
    } else {
        die("Invalid role selected.");
    }

    // Bind common parameters
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':subjects', $subjects);

    // Execute the query
    try {
        $stmt->execute();
        echo "Form submitted successfully!";
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    die("Invalid request method.");
}
?>