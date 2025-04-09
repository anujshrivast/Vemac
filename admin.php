<?php
session_start();
include 'db_connect.php';
// Set the session timeout to 30 minutes (1800 seconds)
$inactive = 1800;

// Check if the timeout variable is set
if (isset($_SESSION['timeout'])) {
  // Calculate the session's lifetime
  $session_life = time() - $_SESSION['timeout'];
  if ($session_life > $inactive) {
    // Session has expired, destroy the session and redirect to login page
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
  }
}

// Update the timeout variable with the current time
$_SESSION['timeout'] = time();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

function getTotalStudents($conn)
{
  $query = "SELECT COUNT(*) AS total_students FROM users WHERE role = 'student'";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $row = $result->fetch_assoc();
  return $row['total_students'];
}

function getFeesCollected($conn)
{
  $query = "SELECT SUM(amount) AS fees_collected FROM fees";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $row = $result->fetch_assoc();
  return $row['fees_collected'];
}

function getPendingDues($conn)
{
  $query = "SELECT SUM(amount) AS pending_fees FROM fees WHERE status = 'pending'";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $row = $result->fetch_assoc();
  return $row['pending_fees'];
}

function getAdmissions($conn)
{
  $currentYear = date("Y");
  $query = "SELECT COUNT(*) as current_year_admissions FROM student_data WHERE YEAR(admission_date) = ?";

  if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['current_year_admissions'];
  } else {
    die("Error preparing statement: " . $conn->error);
  }
}


function getAllInstitutes($conn)
{
  $query = "SELECT * FROM institute_branch";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $institutes = [];
  while ($row = $result->fetch_assoc()) {
    $institutes[] = $row;
  }
  return ['institutes' => $institutes, 'rowCount' => count($institutes)];
}


function add_User($conn, $institute_name, $name, $email, $phone, $hashed_password, $role)
{

  $stmt = $conn->prepare("INSERT INTO users (institute_name, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $institute_name, $name, $email, $phone, $hashed_password, $role);

  $result = $stmt->execute();
  // Execute the statement
  if ($result === true) {
    echo "User created successfully.";
  } else {
    echo "Error: " . $stmt->error;
  }

  // Close the statement and connection
  $stmt->close();
  return $result;
}
function editUser($conn, $institute_name, $name, $email, $phone, $role, $id)
{
  $stmt = $conn->prepare("UPDATE users SET institute_name = ?, name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
  $stmt->bind_param("sssssi", $institute_name, $name, $email, $phone, $role, $id);

  // Execute the statement
  if ($stmt->execute()) {
    echo "User updated successfully.";
  } else {
    echo "Error: " . $stmt->error;
  }

  // Close the statement
  $stmt->close();
}


function removeUser($conn, $email)
{
  $query = "DELETE FROM users WHERE email = ?";
  $stmt = $conn->prepare($query);
  if ($stmt === false) {
    die('Query failed: ' . $conn->error);
  }
  $stmt->bind_param("s", $email);
  $result = $stmt->execute();
  if ($result === false) {
    die('Execution failed: ' . $stmt->error);
  } else {
    return $result;
  }
  $stmt->close();
}

function addInstitute($conn, $name, $address, $contact, $incharge, $incharge_contact)
{
  $query = "INSERT INTO institute_branch (Institute_Name, Institute_Address, Institute_Contact, Office_incharge, office_incharge_contact) VALUES (?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($query);
  if ($stmt === false) {
    die('Query failed: ' . $conn->error);
  } else {
    echo "Error: " . $stmt->error;
  }
  $stmt->bind_param("sssss", $name, $address, $contact, $incharge, $incharge_contact);
  $result = $stmt->execute();
  $stmt->close();
  return $result;
}

function removeInstitute($conn, $branch_id)
{
  $query = "DELETE FROM institute_branch WHERE branch_id = ?";
  if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
  } else {
    echo "Preparation failed: " . $conn->error . "<br>";
    return false;
  }
}


function editInstitute($conn, $id, $name, $address, $contact, $incharge, $incharge_contact)
{
  $sql = "UPDATE institute_branch SET Institute_Name = ?, Institute_Address = ?,Institute_Contact = ?, Office_incharge = ?, office_incharge_contact = ? WHERE branch_id = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new Exception("Error in SQL query: " . $conn->error);
  }
  $stmt->bind_param('sssssi', $name, $address, $contact, $incharge, $incharge_contact, $id);
  $stmt->execute();
  $stmt->close();
}


function getUsersByRole($conn, $role)
{
  $query = "SELECT * FROM users WHERE role = ?";
  $stmt = $conn->prepare($query);
  if ($stmt === false) {
    die('Query failed: ' . $conn->error);
  }
  $stmt->bind_param("s", $role);
  $stmt->execute();
  $result = $stmt->get_result();
  $users = [];
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
  $stmt->close();
  return $users;
}

function getAllUsers($conn)
{
  $query = "SELECT * FROM users";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $users = [];
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
  return $users;
}

function deleteStudentApplication($conn, $id)
{
  $stmt = $conn->prepare("DELETE FROM student_data WHERE id = ?");
  $stmt->bind_param("i", $id);
  $result = $stmt->execute();
  $stmt->close();
  return $result;
}

//Get all students admission data
function students_data($conn)
{
  $query = "SELECT * FROM student_data";
  $result = $conn->query($query);
  if ($result === false) {
    die('Query failed: ' . $conn->error);
  }
  $students = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $students[] = $row;
    }

    return $students;
  }
}

// Get all students by search
function get_all_students($conn, $search = '')
{
  $query = "SELECT * FROM student_data";

  if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
  }

  $result = $conn->query($query);
  $students = [];

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $students[] = $row;
    }
  }

  return $students;
}

function saveOrUpdateFee(
  $conn,
  $fee_id,
  $instituteName,
  $studentName,
  $amount,
  $paymentDate,
  $paymentMethod,
  $status,
  $remark,
  $createdAt
) {

  // Check if fee already exists
  $checkSql = "SELECT fee_id FROM student_fees WHERE fee_id = ?";
  $stmt = $conn->prepare($checkSql);
  $stmt->bind_param("s", $fee_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // Update existing record
    $updateSql = "UPDATE student_fees SET 
            institute_name=?, student_name=?, amount=?, 
            payment_date=?, payment_method=?, status=?, 
            remark=?, created_at=? WHERE fee_id=?";

    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param(
      "ssdssssss",
      $instituteName,
      $studentName,
      $amount,
      $paymentDate,
      $paymentMethod,
      $status,
      $remark,
      $createdAt,
      $feeId
    );

    if ($stmt->execute()) {
      return "Fee updated successfully.";
    } else {
      return "Error updating fee: " . $stmt->error;
    }
  } else {
    // Insert new record
    $insertSql = "INSERT INTO student_fees 
            (fee_id, institute_name, student_name, amount, 
             payment_date, payment_method, status, remark, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param(
      "sssdsdsss",
      $feeId,
      $instituteName,
      $studentName,
      $amount,
      $paymentDate,
      $paymentMethod,
      $status,
      $remark,
      $createdAt
    );

    if ($stmt->execute()) {
      return "Fee added successfully.";
    } else {
      return "Error adding fee: " . $stmt->error;
    }
  }
}


// Get single student by ID
function get_student($conn, $name)
{
  $stmt = $conn->prepare("SELECT * FROM student_data WHERE first_name = ?");
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $result = $stmt->get_result();

  return $result->fetch_assoc();
}


// Get search term
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$students = get_all_students($conn, $search);



// Function to get all fees records
function getAllFees($conn)
{
  $query = "SELECT * FROM fees ORDER BY payment_date";
  $result = $conn->query($query);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get pending fees
function getPendingFees($conn)
{
  $query = "SELECT * FROM fees WHERE status = 'pending'";
  $result = $conn->query($query);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to update fee status
function updateFeeStatus($conn, $feeId, $status)
{
  $query = "UPDATE fees SET status = ? WHERE fee_id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("si", $status, $feeId);
  return $stmt->execute();
}

// // Function to send reminder
// function sendReminder($studentName, $amount, $dueDate) {
//     $message = "Dear $studentName,\n\nThis is a reminder that your fee of $amount is due on $dueDate. Please make the payment at the earliest.";
//     // Here you can integrate with an email service or SMS gateway to send the message
//     return mail($studentEmail, "Fee Reminder", $message);
// }


// Handle form submissions
$message = '';
$errors = [];
$success = false;



// Validate and sanitize input data
function validateInput($data)
{
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}


// Handle file uploads
function handleFileUpload($file, $max_size = 2097152)
{ // 2MB default
  $allowed_types = ['image/jpeg', 'image/png'];
  $upload_dir = 'uploads/Profile_photos/';

  if ($file['error'] !== UPLOAD_ERR_OK) {
    return ['error' => 'File upload error'];
  }

  if (!in_array($file['type'], $allowed_types)) {
    return ['error' => 'Only JPG and PNG images are allowed'];
  }

  if ($file['size'] > $max_size) {
    return ['error' => 'File size must be less than ' . ($max_size / 1024 / 1024) . 'MB'];
  }

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
  }

  $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $file_name = uniqid('student_') . '.' . $file_ext;
  $target_path = $upload_dir . $file_name;

  if (move_uploaded_file($file['tmp_name'], $target_path)) {
    return ['success' => true, 'path' => $target_path];
  } else {
    return ['error' => 'Failed to upload file'];
  }
}


// Save student data to database
function saveStudentData(
  $conn,
  $firstName,
  $lastName,
  $email,
  $phone,
  $dob,
  $gender,
  $photopath,
  $fulladdress,
  $course,
  $schoolType,
  $school,
  $parentName,
  $parentPhone,
  $referredBy,
  $Admission_Accpted_by,
  $institute_name,
  $status,
  $Admission_code
) {
  // Validate required fields
  $required = ['first_name', 'last_name', 'email', 'phone', 'dob', 'gender', 'Admission_Accpted_by', 'institute_name', 'Admission_code'];
  foreach ($required as $field) {
    if (empty($data[$field])) {
      throw new Exception("Missing required field: $field");
    }
  }



  // Prepare the SQL statement
  $stmt = $conn->prepare("INSERT INTO student_data (
      first_name, last_name, email, phone, dob, gender, photo_path,
      address, course, school_type, school,
      parent_name, parent_phone, Referred_by_About ,Admission_Accpted_by,institute_name ,status,Admission_code
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?,?,?,?,?)");

  if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
  }

  // Bind parameters using the variables
  $bound = $stmt->bind_param(
    "ssssssssssssssssss",
    $firstName,
    $lastName,
    $email,
    $phone,
    $dob,
    $gender,
    $photopath,
    $fulladdress,
    $course,
    $schoolType,
    $school,
    $parentName,
    $parentPhone,
    $referredBy,
    $Admission_Accpted_by,
    $institute_name,
    $status,
    $Admission_code
  );

  if (!$bound) {
    throw new Exception("Bind failed: " . $stmt->error);
  }

  // Execute and return status
  $result = $stmt->execute();

  if (!$result) {
    throw new Exception("Execute failed: " . $stmt->error);
  }

  return $result;
}



/**
 * Save or update student data in the database
 * @param mysqli $conn Database connection
 * @param array $data Student data
 * @param int|null $studentId ID of student to update (null for new record)
 * @return bool|int Returns true on success, or student ID if inserting new record
 * @throws Exception
 **/ function updateStudentData(
  $conn,
  $firstName,
  $lastName,
  $email,
  $phone,
  $dob,
  $gender,
  $photopath,
  $address,
  $course,
  $schoolType,
  $school,
  $parentName,
  $parentPhone,
  $referredBy,
  $Admission_Accpted_by,
  $institute_name,
  $status,
  $Admission_code,
  $id
) {

  // Prepare the update statement
  $stmt = $conn->prepare("UPDATE student_data SET
      first_name = ?, last_name = ?, email = ?, phone = ?, dob = ?, gender = ?, 
      photo_path = ?, address = ?, course = ?, school_type = ?, school = ?,
      parent_name = ?, parent_phone = ?, Referred_by_About = ?,
      Admission_Accpted_by = ?, institute_name = ?, status = ?, Admission_code = ?,
      updated_at = CURRENT_TIMESTAMP
      WHERE id = ?");

  if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
  }

  // Bind parameters
  $stmt->bind_param(
    "ssssssssssssssssssi",
    $firstName,
    $lastName,
    $email,
    $phone,
    $dob,
    $gender,
    $photopath,
    $address,
    $course,
    $schoolType,
    $school,
    $parentName,
    $parentPhone,
    $referredBy,
    $Admission_Accpted_by,
    $institute_name,
    $status,
    $Admission_code,
    $id
  );

  // Execute the statement
  $result = $stmt->execute();

  if (!$result) {
    throw new Exception("Database operation failed: " . $stmt->error);
  }

  return true;
}
/**
 * Toggle student active status
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param int $institute_id Institute ID
 * @return array [status: bool, new_status: bool, message: string]
 */

function toggleStudentStatus($conn, $student_id)
{
  try {
    // First verify student exists and get current status
    $check_stmt = $conn->prepare("SELECT id, is_active FROM student_data WHERE id = ?");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
      return [
        'status' => false,
        'message' => 'Student not found'
      ];
    }

    $student = $result->fetch_assoc();
    $new_status = $student['is_active'] ? 0 : 1;

    // Update status
    $update_stmt = $conn->prepare("UPDATE student_data SET is_active = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_status, $student_id);
    $update_stmt->execute();

    return [
      'status' => true,
      'new_status' => $new_status,
      'message' => 'Status updated successfully'
    ];
  } catch (Exception $e) {
    error_log("Student status toggle error: " . $e->getMessage());
    return [
      'status' => false,
      'message' => 'Database error'
    ];
  }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $form_type = $_POST['form_type'];


  switch ($form_type) {
    case 'admission_form':
      // Sanitize and validate inputs

      $firstName = $_POST['first_name'];
      $lastName = $_POST['last_name'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $dob = $_POST['dob'];
      $gender = $_POST['gender'];
      $photopath = $_POST['photo_path'];
      $address = $_POST['address'];
      $city = $_POST['city'];
      $state = $_POST['state'];
      $zip_code = $_POST['zip_code'];
      $country = $_POST['country'];
      $course = $_POST['Course'];
      $school_type = $_POST['school_type'];
      $school = $_POST['school'];
      $parent_name = $_POST['parent_name'];
      $parent_phone = $_POST['parent_phone'];
      $referredBy = $_POST['Referred_by_About'];
      $Admission_Accpted_by = $_POST['Admission_Accpted_by'];
      $institute_name = $_POST['institute_name'];
      $status = $_POST['status'];
      $Admission_code = $_POST['Admission_code'];
      $terms = isset($_POST['terms']) ? true : false;

      // Validate required fields
      if (!$terms) $errors['terms'] = 'You must accept the terms';


      if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $upload_result = handleFileUpload($_FILES['photo']);
        if (isset($upload_result['error'])) {
          $errors['photo'] = $upload_result['error'];
        } else {
          $photo_path = $upload_result['path'];
        }
      } elseif (!empty($_POST['existing_photo'])) {
        // Keep existing photo if no new upload
        $photo_path = $_POST['existing_photo'];
      }


      // Combine address components into single string
      $addressComponents = [
        $address,
        $city,
        $state,
        $zip_code,
        $country
      ];

      // Filter out empty components and join with commas
      $fullAddress = implode(', ', array_filter($addressComponents));

      // If no errors, insert into database
      if (empty($errors)) {
        $students['photo_path'] = $photo_path;

        try {
          if (saveStudentData(
            $conn,
            $firstName,
            $lastName,
            $email,
            $phone,
            $dob,
            $gender,
            $photopath,
            $fulladdress,
            $course,
            $schooltype,
            $school,
            $parentname,
            $parentphone,
            $referredBy,
            $Admission_Accpted_by,
            $institute_name,
            $status,
            $Admission_code
          )) {
            $success = true;
            // sendConfirmationEmail($student);

            // Clear form data after successful submission
            $student = array_map(function () {
              return '';
            }, $student);
          } else {
            $errors['database'] = 'Failed to save data: ' . $conn->error;
          }
        } catch (Exception $e) {
          $errors['database'] = 'Database error: ' . $e->getMessage();
        }
      }

      break;

    case 'update_admission_form':
      // Sanitize and validate inputs
      $id = $_POST['id'];
      $firstName = $_POST['first_name'];
      $lastName = $_POST['last_name'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $dob = $_POST['dob'];
      $gender = $_POST['gender'];
      $photopath = $_POST['photo_path'];
      $address = $_POST['address'];
      $course = $_POST['Course'];
      $schooltype = $_POST['school_type'];
      $school = $_POST['school'];
      $parentname = $_POST['parent_name'];
      $parentphone = $_POST['parent_phone'];
      $referredBy = $_POST['Referred_by_About'];
      $Admission_Accpted_by = $_POST['Admission_Accpted_by'];
      $institute_name = $_POST['institute_name'];
      $status = $_POST['status'];
      $Admission_code = $_POST['Admission_code'];
      $terms = isset($_POST['terms']) ? true : false;



      // Validate required fields
      if (!$terms) $errors['terms'] = 'You must accept the terms';


      if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $upload_result = handleFileUpload($_FILES['photo']);
        if (isset($upload_result['error'])) {
          $errors['photo'] = $upload_result['error'];
        } else {
          $photo_path = $upload_result['path'];
        }
      } elseif (!empty($_POST['existing_photo'])) {
        // Keep existing photo if no new upload
        $photo_path = $_POST['existing_photo'];
      }

      // If no errors, insert into database
      if (empty($errors)) {
        $students['photo_path'] = $photo_path;

        try {
          if (updateStudentData(
            $conn,
            $firstName,
            $lastName,
            $email,
            $phone,
            $dob,
            $gender,
            $photopath,
            $address,
            $course,
            $schooltype,
            $school,
            $parentname,
            $parentphone,
            $referredBy,
            $Admission_Accpted_by,
            $institute_name,
            $status,
            $Admission_code,
            $id
          )) {
            $success = true;
            // sendConfirmationEmail($student);

            // Clear form data after successful submission
            $student = array_map(function () {
              return '';
            }, $students);
          } else {
            $errors['database'] = 'Failed to save data: ' . $conn->error;
          }
        } catch (Exception $e) {
          $errors['database'] = 'Database error: ' . $e->getMessage();
        }
      }

      break;
    case 'toggle_student_status':
      $student_id = $_POST['id'];
      $is_active = $_POST['is_active'];

      // Validate CSRF token here

      $result = toggleStudentStatus($conn, $student_id);

      header('Content-Type: application/json');
      echo json_encode([
        'success' => $result['status'],
        'is_active' => $result['new_status'] ?? false,
        'message' => $result['message']
      ]);

      break;

    case 'fees_form':
      // Sanitize and validate inputs
      $fee_id = isset($_POST['fee_id']) ? $_POST['fee_id'] : null;
      $institute_name = isset($_POST['instituteName']) ? $_POST['instituteName'] : null;
      $student_name = isset($_POST['studentName']) ? $_POST['studentName'] : null;
      $payment_date = isset($_POST['paymentDate']) ? $_POST['paymentDate'] : null;
      $payment_method = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : null;
      $created_at = isset($_POST['createdAt']) ? $_POST['createdAt'] : null;
      $amount = isset($_POST['amount']) ? $_POST['amount'] : null;
      $status = isset($_POST['status']) ? $_POST['status'] : null;
      $remark = isset($_POST['remark']) ? $_POST['remark'] : null;

      // Now you can safely use these variables


      // Validate required fields
      if (empty($institute_name) || empty($student_name) || empty($amount) || empty($payment_date) || empty($payment_method)) {
        $errors[] = 'All fields are required.';
      }

      // If no errors, insert into database
      if (empty($errors)) {
        try {
          if (saveOrUpdateFee(
            $conn,
            $fee_id,
            $institute_name,
            $student_name,
            $amount,
            $payment_date,
            $payment_method,
            $status,
            $remark,
            $created_at
          )) {
            $success = true;
          } else {
            $errors[] = 'Failed to save data: ' . $conn->error;
          }
        } catch (Exception $e) {
          $errors[] = 'Database error: ' . $e->getMessage();
        }
      }

      break;

    case 'remove_user':
      $email = $_POST['email'];
      $result = removeUser($conn, $email);
      if ($result) {
        $feedback = 'User removed successfully!';
      } else {
        $feedback = 'Error removing user.';
      }
      break;

    case 'create_user':
      // Retrieve form data
      $institute_name = $_POST['institute_name'];
      $name = $_POST['name'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $password = $_POST['password'];
      $role = $_POST['role'];

      // Validate input data (you can add more validation as needed)
      if (empty($institute_name) || empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $message = 'All fields are required.';
      }
      //hashed_password for security
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      $result = add_User($conn, $institute_name, $name, $email, $phone, $hashed_password, $role);
      if ($result) {
        $feedback = 'User Add successfully!';
      } else {
        $feedback = 'Error Adding user.';
      }
      break;

    case 'edit_user':
      // Retrieve form data
      $id = $_POST['id'];
      $institute_name = $_POST['institute_name'];
      $name = $_POST['name'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $role = $_POST['role'];

      // Validate input data
      if (empty($institute_name) || empty($name) || empty($email) || empty($phone) || empty($role)) {
        $message = "All fields are required.";
      }
      $result = editUser($conn, $institute_name, $name, $email, $phone, $role, $id);
      break;

    case 'add_institute':
      $name = $_POST['Institute_Name'];
      $address = $_POST['Institute_Address'];
      $contact = $_POST['Institute_Contact'];
      $incharge = $_POST['Office_incharge'];
      $incharge_contact = $_POST['office_incharge_contact'];
      $result = addInstitute($conn, $name, $address, $contact, $incharge, $incharge_contact);
      if ($result) {
        $feedback = 'Institute added successfully!';
      } else {
        $feedback = 'Error adding institute.';
      }
      break;

    case 'remove_institute':
      if (isset($_POST['branch_id'])) {
        $institute_id = $_POST['branch_id'];
        $result = removeInstitute($conn, $institute_id);
        if ($result) {
          $feedback = 'Institute removed successfully!';
        } else {
          $feedback = 'Error removing institute.';
        }
      } else {
        $feedback = 'Institute ID not provided.';
      }
      break;

    case 'edit_institute':
      $id = $_POST['branch_id'];
      $name = $_POST['Institute_Name'];
      $address = $_POST['Institute_Address'];
      $contact = $_POST['Institute_Contact'];
      $incharge = $_POST['Office_incharge'];
      $incharge_contact = $_POST['office_incharge_contact'];
      $result = editInstitute($conn, $id, $name, $address, $contact, $incharge, $incharge_contact);
      if ($result) {
        $feedback = 'Institute edited successfully!';
      } else {
        $feedback = 'Error editing institute.';
      }
      break;

    default:
      $feedback = 'Unknown form submission.';
      break;
  }
}

// Fetch data
$totalStudents = getTotalStudents($conn);
$feesCollected = getFeesCollected($conn);
$pendingDues = getPendingDues($conn);
$admissions = getAdmissions($conn);
$instituteData = getAllInstitutes($conn);
$institutes = $instituteData['institutes'];
$rowCount = $instituteData['rowCount'];
$users = getAllUsers($conn);
$students = students_data($conn);
$selectedRole = isset($_GET['role']) ? $_GET['role'] : null;
if ($selectedRole) {
  $users = getUsersByRole($conn, $selectedRole);
}






/**
 * Send confirmation email
 */
// function sendConfirmationEmail($student)
// {
//   $to = $student['email'];
//   $subject = 'Admission Confirmation';
//   $message = "Dear {$student['first_name']},\n\n";
//   $message .= "Thank you for submitting your admission form.\n";
//   $message .= "We have received your application for {$student['course']}.\n";
//   $message .= "We will review your application and contact you soon.\n\n";
//   $message .= "Regards,\nAdmission Team";
//   $headers = 'From: admissions@yourschool.edu';

//   return mail($to, $subject, $message, $headers);
// }






?>

<!DOCTYPE html>
<html lang="en">
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - VEMAC</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    body {
      background-color: #f8f9fa;
    }

    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: white;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      display: block;
    }

    .sidebar a:hover {
      background-color: #495057;
    }

    .main-content {
      padding: 20px;
    }

    .card {
      margin-bottom: 20px;
    }

    .form-section {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .section-title {
      color: #0d6efd;
      border-bottom: 2px solid #0d6efd;
      padding-bottom: 8px;
      margin-bottom: 20px;
    }

    #cameraPreview {
      background: #f8f9fa;
      max-height: 200px;
    }

    #photoCanvas {
      display: none;
    }

    .camera-controls {
      margin-top: 10px;
      display: flex;
      gap: 10px;
    }

    .required-field::after {
      content: " *";
      color: red;
    }

    .error-message {
      color: #dc3545;
      font-size: 0.875em;
    }

    .success-message {
      color: #198754;
      font-size: 1.1em;
      font-weight: 500;
    }

    #photoPreview {
      width: 150px;
      height: 150px;
      border: 1px dashed #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      overflow: hidden;
    }

    #photoPreview img {
      max-width: 100%;
      max-height: 100%;
    }

    @media (max-width: 768px) {
      .sidebar {
        height: auto;
        padding-top: 10px;
      }

      .main-content {
        padding: 10px;
      }
    }
  </style>
</head>

<body>


  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
      <?= $_SESSION['success_message'] ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
      <?= $_SESSION['error_message'] ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>



  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-2 sidebar" style="background-color: #000; color: #fff;">
        <h3 class="text-center py-3">VEMAC</h3>
        <ul class="nav flex-column">
          <li><a href="#" onclick="showSection('overview')" style="color: #fff;">Overview</a></li>
          <li><a href="#" onclick="showSection('institute-management')" style="color: #fff;">Institute Management</a></li>
          <li><a href="#" onclick="showSection('student-management')" style="color: #fff;">Student Management</a></li>
          <li><a href="#" onclick="showSection('user-management')" style="color: #fff;">User Management</a></li>
          <li><a href="#" onclick="showSection('fees-management')" style="color: #fff;">Fees Management</a></li>
          <a href="logout.php" onclick="return confirm('You Want To LOGOUT Confirm?')" style="color: #fff;">Logout</a>
        </ul>
      </div>
      <!-- Main Content -->
      <div class="col-md-10 main-content">
        <h1 class="mb-4">Admin Dashboard</h1>

        <!-- Overview Panel -->
        <section id="overview" class="section  ">
          <h2>Overview</h2>
          <div class="row">
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Total Students</h5>
                  <p class="card-text"><?php echo $totalStudents; ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Fees Collected</h5>
                  <p class="card-text">₹<?php echo number_format($feesCollected, 2); ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Pending Dues</h5>
                  <p class="card-text">₹<?php echo number_format($pendingDues, 2); ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Admissions</h5>
                  <p class="card-text"><?php echo $admissions; ?></p>
                </div>
              </div>
            </div>
          </div>
        </section>


        <section id="institute-management" class="section mt-5 " style="display: none;">
          <h2>Institute Management</h2>
          <div class="card">
            <div class="card-body">
              <button class="btn btn-primary" name='add_institute' data-bs-toggle="modal" data-bs-target="#addInstituteModal">Add Institute</button>
              <div id="instituteFeedback" class="mt-3"></div>
              <table class="table mt-4">
                <thead>
                  <tr>
                    <th>S.NO</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Contact Number</th>
                    <th>Office Incharge</th>
                    <th>Incharge Number</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Loop based on the row count
                  for ($i = 0; $i < $rowCount; $i++) {
                    $institute = $institutes[$i];
                  ?>
                    <tr id="institute_data_<?php echo htmlspecialchars($institute['branch_id']); ?>">
                      <td><?php echo htmlspecialchars($institute['branch_id']); ?></td>
                      <td><?php echo htmlspecialchars($institute['Institute_Name']); ?></td>
                      <td><?php echo htmlspecialchars($institute['Institute_Address']); ?></td>
                      <td><?php echo htmlspecialchars($institute['Institute_Contact']); ?></td>
                      <td><?php echo htmlspecialchars($institute['Office_incharge']); ?></td>
                      <td><?php echo htmlspecialchars($institute['office_incharge_contact']); ?></td>
                      <td>
                        <button type="button" class="btn btn-secondary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editInstituteModal"
                          data-id="<?php echo $institute['branch_id']; ?>"
                          data-name="<?php echo htmlspecialchars($institute['Institute_Name']); ?>"
                          data-address="<?php echo htmlspecialchars($institute['Institute_Address']); ?>"
                          data-contact="<?php echo htmlspecialchars($institute['Institute_Contact']); ?>"
                          data-incharge="<?php echo htmlspecialchars($institute['Office_incharge']); ?>"
                          data-incharge-contact="<?php echo htmlspecialchars($institute['office_incharge_contact']); ?>">
                          Edit
                        </button>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="form_type" value="remove_institute">
                          <input type="hidden" name="branch_id" value="<?php echo $institute['branch_id']; ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this institute?')">Remove</button>
                        </form>
                      </td>
                    </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>


        <!-- Add Institute Modal -->
        <div class="modal fade" id="addInstituteModal" tabindex="-1" aria-labelledby="addInstituteModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addInstituteModalLabel">Add Institute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="POST" id="addInstituteForm">
                  <input type="hidden" name="form_type" value="add_institute">

                  <div class="mb-3">
                    <label for="Institute_Name" class="form-label">Institute Name</label>
                    <input type="text" class="form-control" id="Institute_Name" name="Institute_Name" required>
                  </div>
                  <div class="mb-3">
                    <label for="Institute_Address" class="form-label">Institute Address</label>
                    <input type="text" class="form-control" id="Institute_Address" name="Institute_Address" required>
                  </div>
                  <div class="mb-3">
                    <label for="Institute_Contact" class="form-label">Institute No.</label>
                    <input type="tel" class="form-control" id="Institute_Contact" name="Institute_Contact" required>
                  </div>
                  <div class="mb-3">
                    <label for="Office_incharge" class="form-label">Office Incharge Name</label>
                    <input type="text" class="form-control" id="Office_incharge" name="Office_incharge" required>
                  </div>
                  <div class="mb-3">
                    <label for="office_incharge_contact" class="form-label">Office Incharge Contact No.</label>
                    <input type="tel" class="form-control" id="office_incharge_contact" name="office_incharge_contact" required>
                  </div>
                  <button type="submit" name='add_institute' class="btn center btn-primary">Add Institute</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Edit Institute Modal -->
        <div class="modal fade" id="editInstituteModal" tabindex="-1" aria-labelledby="editInstituteModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editInstituteModalLabel">Edit Institute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="editInstituteForm" method="POST">
                  <input type="hidden" name="form_type" value="edit_institute">
                  <input type="hidden" name="branch_id" id="editBranchId"> <!-- Hidden field for branch_id -->

                  <div class="mb-3">
                    <label for="editInstituteName" class="form-label">Institute Name</label>
                    <input type="text" class="form-control" id="editInstituteName" name="Institute_Name" required>
                  </div>
                  <div class="mb-3">
                    <label for="editInstituteAddress" class="form-label">Institute Address</label>
                    <input type="text" class="form-control" id="editInstituteAddress" name="Institute_Address" required>
                  </div>
                  <div class="mb-3">
                    <label for="editInstituteContact" class="form-label">Institute No.</label>
                    <input type="tel" class="form-control" id="editInstituteContact" name="Institute_Contact" required>
                  </div>
                  <div class="mb-3">
                    <label for="editOfficeIncharge" class="form-label">Office Incharge Name</label>
                    <input type="text" class="form-control" id="editOfficeIncharge" name="Office_incharge" required>
                  </div>
                  <div class="mb-3">
                    <label for="editOfficeInchargeContact" class="form-label">Office Incharge Contact No.</label>
                    <input type="tel" class="form-control" id="editOfficeInchargeContact" name="office_incharge_contact" required>
                  </div>

                  <button type="submit" name='edit_institute' class="btn btn-primary">Edit Institute</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <section id="student-management" class="section mt-5 " style="display: none;">
          <div class="container mt-5">
            <h1 class="mb-4">Student Management System</h1>

            <?php if ($message): ?>
              <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Search Form -->
            <form method="get" class="mb-4">
              <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>" required>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                  <a href="admin.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
              </div>
            </form>
            <!-- Button to trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#admissionModal" style="margin-bottom: 10px;">
              <i class="bi bi-person-plus"></i> Apply for Admission
            </button>



            <!-- Students Table -->
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>Id</th>
                    <th>Institute</th>
                    <th>Name</th>
                    <th>Admission code</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $student): ?>
                    <tr>
                      <td><?= htmlspecialchars($student['id']) ?></td>
                      <td><?= htmlspecialchars($student['institute_name']) ?></td>
                      <td><?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars($student['last_name']) ?></td>
                      <td><?= htmlspecialchars($student['Admission_code']) ?></td>
                      <td><?= htmlspecialchars($student['phone']) ?></td>
                      <td><?= htmlspecialchars($student['course']) ?></td>
                      <td>
                        <button type="button" class="btn btn-secondary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#updateadmissionModal"
                          data-id="<?= $studentId ?>"
                          data-first-name="<?= htmlspecialchars($student['first_name'] ?? '') ?>"
                          data-last-name="<?= htmlspecialchars($student['last_name'] ?? '') ?>"
                          data-email="<?= htmlspecialchars($student['email'] ?? '') ?>"
                          data-phone="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                          data-dob="<?= htmlspecialchars($student['dob'] ?? '') ?>"
                          data-gender="<?= htmlspecialchars($student['gender'] ?? '') ?>"
                          data-photo="<?= htmlspecialchars($student['photo_path'] ?? '') ?>"
                          data-address="<?= htmlspecialchars($student['address'] ?? '') ?>"
                          data-course="<?= htmlspecialchars($student['course'] ?? '') ?>"
                          data-school-type="<?= htmlspecialchars($student['school_type'] ?? '') ?>"
                          data-school="<?= htmlspecialchars($student['school'] ?? '') ?>"
                          data-parent-name="<?= htmlspecialchars($student['parent_name'] ?? '') ?>"
                          data-parent-phone="<?= htmlspecialchars($student['parent_phone'] ?? '') ?>"
                          data-referred-by-about="<?= htmlspecialchars($student['Referred_by_About'] ?? '') ?>"
                          data-admission-accepted-by="<?= htmlspecialchars($student['Admission_Accpted_by'] ?? '') ?>"
                          data-institute-name="<?= htmlspecialchars($student['institute_name'] ?? '') ?>"
                          data-admission-code="<?= htmlspecialchars($student['Admission_code'] ?? '') ?>"
                          data-status="<?= htmlspecialchars($student['status'] ?? '') ?>">
                          <i class="fas fa-edit"></i> Edit
                        </button>


                        <form method="post" style="display: inline-block;">
                          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                          <input type="hidden" name="form_type" value="delete_student_application">
                          <input type="hidden" name="id" value="<?= $student['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"> <i class="fas fa-trash"></i> Delete</button>
                        </form>

                        <form method="post" style="display: inline-block;" id="status-form-<?= $student['id'] ?? '' ?>">
                          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                          <input type="hidden" name="form_type" value="toggle_student_status">
                          <input type="hidden" name="id" value="<?= $student['id'] ?? '' ?>">
                          <?php if (isset($student['is_active'])): ?>
                            <input type="hidden" name="is_active" value="<?= $student['is_active'] ? '0' : '1' ?>">
                            <input type="checkbox"
                              class="toggle-status-checkbox"
                              data-toggle="toggle"
                              data-on="<i class='fa-solid fa-circle-check'></i> Active"
                              data-off="<i class='fa-regular fa-circle-xmark'></i> Inactive"
                              data-onstyle="success"
                              data-offstyle="danger"
                              <?= $student['is_active'] ? 'checked' : '' ?>
                              onchange="toggleStatus(<?= $student['id'] ?>)">
                          <?php else: ?>
                            <button type="button" class="btn btn-danger" disabled>Student Data Missing</button>
                          <?php endif; ?>
                        </form>

                        <div id="code-container" style="display: none; white-space: pre-wrap; background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin-top: 20px;"></div>





                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Admission Modal -->
            <div class="modal fade" id="admissionModal" tabindex="-1" aria-labelledby="admissionModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="admissionModalLabel"><i class="bi bi-person-plus"></i> Student Admission Form</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="container py-5">
                      <div class="row justify-content-center">
                        <div class="col-lg-10">
                          <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                              <h2 class="mb-0"><i class="bi bi-person-plus"></i> Student Admission Form</h2>
                            </div>

                            <div class="card-body">


                              <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="form_type" value="update_admission_form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <!-- Personal Information Section -->
                                <div class="form-section">
                                  <h4 class="section-title"><i class="bi bi-person"></i> Personal Information</h4>
                                  <div class="row g-3">
                                    <div class="col-md-6">
                                      <label for="first_name" class="form-label required-field">First Name</label>
                                      <input type="text" class="form-control"
                                        id="first_name" name="first_name" required>

                                    </div>
                                    <div class="col-md-6">
                                      <label for="last_name" class="form-label required-field">Last Name</label>
                                      <input type="text" class="form-control"
                                        id="last_name" name="last_name"
                                        required>

                                    </div>
                                    <div class="col-md-6">
                                      <label for="email" class="form-label required-field">Email</label>
                                      <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                        id="email" name="email"
                                        required>

                                    </div>
                                    <div class="col-md-6">
                                      <label for="phone" class="form-label required-field">Phone</label>
                                      <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                        id="phone" name="phone"
                                        required>

                                    </div>
                                    <div class="col-md-4">
                                      <label for="dob" class="form-label required-field">Date of Birth</label>
                                      <input type="date" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>"
                                        id="dob" name="dob"
                                        required>

                                    </div>
                                    <div class="col-md-4">
                                      <label class="form-label required-field">Gender</label>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" value="Male"
                                          <?= ($student['gender'] === 'Male') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="male">Male</label>
                                      </div>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" value="Female" required
                                          <?= ($student['gender'] === 'Female') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="female">Female</label>
                                      </div>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="other" value="Other" required
                                          <?= ($student['gender'] === 'Other') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="other">Other</label>
                                      </div>

                                    </div>
                                    <div class="col-md-4">
                                      <label for="photo" class="form-label">Student Photo</label>

                                      <!-- Upload Option -->
                                      <input type="file" class="form-control mb-2" id="photo" name="photo" accept="image/jpeg, image/png">

                                      <!-- Static Preview -->
                                      <div id="photoPreview" class="mt-2">
                                        <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
                                          <img src="<?= htmlspecialchars($student['photo_path']) ?>" alt="Student Photo" class="img-thumbnail" style="max-width: 200px;">
                                        <?php else: ?>
                                          <span class="text-muted">No photo selected</span>
                                        <?php endif; ?>
                                      </div>
                                    </div>

                                    <small class="text-muted">Max 2MB (JPG, PNG) or capture from camera</small>

                                    <?php if (isset($errors['photo'])): ?>
                                      <div class="error-message"><?= htmlspecialchars($errors['photo']) ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                            </div>

                            <!-- Address Information Section -->
                            <div class="form-section">
                              <h4 class="section-title"><i class="bi bi-house"></i> Address Information</h4>
                              <div class="row g-3">
                                <div class="col-12">
                                  <label for="address" class="form-label required-field">Street Address</label>
                                  <input type="text" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                    id="address" name="address" required>

                                </div>
                                <div class="col-md-6">
                                  <label for="city" class="form-label required-field">City</label>
                                  <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                                    id="city" name="city" required>

                                </div>
                                <div class="col-md-3">
                                  <label for="state" class="form-label required-field">State/Province</label>
                                  <input type="text" class="form-control <?= isset($errors['state']) ? 'is-invalid' : '' ?>"
                                    id="state" name="state" required>

                                </div>

                                <div class="col-md-3">
                                  <label for="zip_code" class="form-label required-field">Pincode/Postal Code</label>
                                  <input type="text" class="form-control <?= isset($errors['zip_code']) ? 'is-invalid' : '' ?>"
                                    id="zip_code" name="zip_code" required>

                                </div>
                                <div class="col-6">
                                  <label for="country" class="form-label required-field">Country</label>
                                  <input type="text" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>"
                                    id="country" name="country" required>

                                </div>
                              </div>
                            </div>

                            <!-- Academic Information Section -->
                            <div class="form-section">
                              <h4 class="section-title"><i class="bi bi-book"></i> Academic Information</h4>
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label for="course" class="form-label required-field">Course/Program/Subjects</label>
                                  <input type="text" class="form-control" id="Course" name="Course" required>

                                </div>
                                <div class="col-md-6">
                                  <label for="school_type" class="form-label">Type of School/College</label>
                                  <input type="text" class="form-control" id="school_type" name="school_type" required>
                                </div>
                                <div class="col-md-6">
                                  <label for="school" class="form-label">School/College</label>
                                  <input type="text" class="form-control" id="school" name="school" required>
                                </div>
                              </div>
                            </div>

                            <!-- Parent/Guardian Information Section -->
                            <div class="form-section">
                              <h4 class="section-title"><i class="bi bi-people"></i> Parent/Guardian Information</h4>
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label for="parent_name" class="form-label required-field">Parent/Guardian Name</label>
                                  <input type="text" class="form-control"
                                    id="parent_name" name="parent_name" required>

                                </div>
                                <div class="col-md-6">
                                  <label for="parent_phone" class="form-label required-field">Parent/Guardian Phone</label>
                                  <input type="tel" class="form-control"
                                    id="parent_phone" name="parent_phone" required>

                                </div>

                              </div>
                            </div>



                            <!-- Other Information Section -->
                            <div class="form-section">
                              <h4 class="section-title"><i class="bi bi-people"></i> Other Information</h4>
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label for="Referred_by_About" class="form-label required-field">About Referred by</label>
                                  <input type="text" class="form-control"
                                    id="Referred_by_About" name="Referred_by_About">

                                </div>

                                <div class="col-md-4">
                                  <label for="Admission_Accpted_by" class="form-label required-field">Admission Accept by</label>
                                  <input type="text" class="form-control"
                                    id="Admission_Accpted_by" name="Admission_Accpted_by">

                                </div>

                                <div class="col-md-4">
                                  <label for="institute_name" class="form-label required-field">For Institute</label>
                                  <input type="text" class="form-control <?= isset($errors['institute_name']) ? 'is-invalid' : '' ?>"
                                    id="institute_name" name="institute_name">

                                </div>

                                <div class="col-md-4">
                                  <label for="Admission_code" class="form-label required-field">Admission Code</label>
                                  <input type="text" class="form-control "
                                    id="Admission_code" name="Admission_code">

                                </div>

                                <div class="col-md-6">
                                  <label class="form-label required-field">Status of Admission</label>
                                  <div class="d-flex gap-2"> <!-- Added flex container with gap between options -->
                                    <div class="form-check">
                                      <input class="form-check-input" type="radio" name="status" id="Approved" value="Approved"
                                        <?= ($student['status'] === 'Approved') ? 'checked' : '' ?>>
                                      <label class="form-check-label" for="Approved">Approved</label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input" type="radio" name="status" id="Pending" value="Pending" required
                                        <?= ($student['status'] === 'Pending') ? 'checked' : '' ?>>
                                      <label class="form-check-label" for="Pending">Pending</label>
                                    </div>
                                  </div>
                                  <?php if (isset($errors['status'])): ?>
                                    <div class="error-message mt-2"><?= htmlspecialchars($errors['status']) ?></div>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>

                            <!-- Terms and Submission -->
                            <div class="form-section">
                              <div class="form-check mb-3">
                                <input class="form-check-input"
                                  type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                  I certify that all information provided is accurate and complete
                                </label>

                              </div>

                              <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                  <i class="bi bi-send"></i> Submit Application
                                </button>
                              </div>
                            </div>
                            </form>
                          </div>

                          <div class="card-footer text-muted">
                            <small>All fields marked with * are required</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>



          <!-- Edit Admission Modal -->
          <div class="modal fade" id="updateadmissionModal" tabindex="-1" aria-labelledby="admissionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
              <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title" id="admissionModalLabel"><i class="bi bi-pencil-square"></i> Edit Student Admission</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="container py-3">
                    <div class="row justify-content-center">
                      <div class="col-lg-12">
                        <div class="card shadow">
                          <div class="card-header bg-primary text-white">
                            <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Student Record</h3>
                          </div>

                          <div class="card-body">
                            <form id="editAdmissionForm" method="POST" enctype="multipart/form-data">
                              <input type="hidden" name="form_type" value="update_admission_form">

                              <input type="hidden" name="student_id" id="student_id" value="">

                              <!-- Personal Information Section -->
                              <div class="form-section mb-4">
                                <h4 class="section-title text-primary"><i class="bi bi-person"></i> Personal Information</h4>
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label for="edit_first_name" class="form-label required-field">First Name</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_last_name" class="form-label required-field">Last Name</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_email" class="form-label required-field">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_phone" class="form-label required-field">Phone</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                                  </div>
                                  <div class="col-md-4">
                                    <label for="edit_dob" class="form-label required-field">Date of Birth</label>
                                    <input type="date" class="form-control" id="edit_dob" name="dob" required>
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label required-field">Gender</label>
                                    <div class="d-flex gap-3">
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="edit_male" value="Male">
                                        <label class="form-check-label" for="edit_male">Male</label>
                                      </div>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="edit_female" value="Female">
                                        <label class="form-check-label" for="edit_female">Female</label>
                                      </div>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="edit_other" value="Other">
                                        <label class="form-check-label" for="edit_other">Other</label>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="col-md-4">
                                    <label for="edit_photo" class="form-label">Student Photo</label>
                                    <input type="file" class="form-control mb-2" id="edit_photo" name="photo" accept="image/*">
                                    <div id="edit_photo_preview" class="mt-2">
                                      <img src="" alt="Current Photo" class="img-thumbnail" style="max-width: 150px; display: none;">
                                      <span class="text-muted no-photo">No photo available</span>
                                    </div>
                                    <small class="text-muted">Leave blank to keep current photo</small>
                                  </div>
                                </div>
                              </div>

                              <!-- Address Information Section -->
                              <div class="form-section mb-4">
                                <h4 class="section-title text-primary"><i class="bi bi-house"></i> Address Information</h4>
                                <div class="row g-3">
                                  <div class="col-12">
                                    <label for="edit_address" class="form-label required-field">Address</label>
                                    <input type="text" class="form-control" id="edit_address" name="address" required>
                                  </div>

                                </div>
                              </div>

                              <!-- Academic Information Section -->
                              <div class="form-section mb-4">
                                <h4 class="section-title text-primary"><i class="bi bi-book"></i> Academic Information</h4>
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label for="edit_course" class="form-label required-field">Course/Program</label>
                                    <input type="text" class="form-control" id="edit_course" name="Course" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_school_type" class="form-label">School Type</label>
                                    <input type="text" class="form-control" id="edit_school_type" name="school_type" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_school" class="form-label">School/College</label>
                                    <input type="text" class="form-control" id="edit_school" name="school" required>
                                  </div>
                                </div>
                              </div>

                              <!-- Parent/Guardian Information Section -->
                              <div class="form-section mb-4">
                                <h4 class="section-title text-primary"><i class="bi bi-people"></i> Parent/Guardian Information</h4>
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label for="edit_parent_name" class="form-label required-field">Parent Name</label>
                                    <input type="text" class="form-control" id="edit_parent_name" name="parent_name" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_parent_phone" class="form-label required-field">Parent Phone</label>
                                    <input type="tel" class="form-control" id="edit_parent_phone" name="parent_phone" required>
                                  </div>
                                </div>
                              </div>

                              <!-- Other Information Section -->
                              <div class="form-section mb-4">
                                <h4 class="section-title text-primary"><i class="bi bi-info-circle"></i> Other Information</h4>
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label for="edit_referred_by" class="form-label">Referred By</label>
                                    <input type="text" class="form-control" id="edit_referred_by" name="Referred_by_About">
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_admission_accepted_by" class="form-label">Admission Accepted By</label>
                                    <input type="text" class="form-control" id="edit_admission_accepted_by" name="Admission_Accpted_by">
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_institute_name" class="form-label">Institute Name</label>
                                    <input type="text" class="form-control" id="edit_institute_name" name="institute_name">
                                  </div>
                                  <div class="col-md-6">
                                    <label for="edit_admission_code" class="form-label">Admission Code</label>
                                    <input type="text" class="form-control" id="edit_admission_code" name="Admission_code">
                                  </div>
                                  <div class="col-md-6">
                                    <label class="form-label required-field">Admission Status</label>
                                    <div class="d-flex gap-3">
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="edit_approved" value="Approved">
                                        <label class="form-check-label" for="edit_approved">Approved</label>
                                      </div>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="edit_pending" value="Pending">
                                        <label class="form-check-label" for="edit_pending">Pending</label>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="edit_terms" name="terms" required>
                                <label class="form-check-label" for="edit_terms">
                                  I confirm that the updated information is accurate
                                </label>
                              </div>

                              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" data-bs-dismiss="modal">
                                  <i class="bi bi-x-circle"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                  <i class="bi bi-save"></i> Update Admission
                                </button>
                              </div>
                            </form>
                          </div>

                          <div class="card-footer text-muted">
                            <small>All fields marked with <span class="required-field">*</span> are required</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <section id="fees-management" class="section mt-5" style="display: none;">
            <h2>Fees Management</h2>
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Fees Management</h5>
                <p class="card-text">Manage fees for students.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feesModal"><i class="bi bi-person-plus"> Add Fees</button>

              </div>


              <!-- Navigation Tabs -->
              <ul class="nav nav-tabs" id="feesTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="all-fees-tab" data-bs-toggle="tab" data-bs-target="#all-fees" type="button" role="tab" aria-controls="all-fees" aria-selected="true">All Fees</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="pending-fees-tab" data-bs-toggle="tab" data-bs-target="#pending-fees" type="button" role="tab" aria-controls="pending-fees" aria-selected="false">Pending Fees</button>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content" id="feesTabContent">
                <!-- All Fees Tab -->
                <div class="tab-pane fade show active" id="all-fees" role="tabpanel" aria-labelledby="all-fees-tab">
                  <table class="table mt-3">
                    <thead>
                      <tr>
                        <th>Fee ID</th>
                        <th>Institute Name</th>
                        <th>Student Name</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Created At</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (getAllFees($conn) as $fee): ?>
                        <tr>
                          <td><?= htmlspecialchars($fee['fee_id']) ?></td>
                          <td><?= htmlspecialchars($fee['institute_name']) ?></td>
                          <td><?= htmlspecialchars($fee['student_name']) ?></td>
                          <td><?= htmlspecialchars($fee['amount']) ?></td>
                          <td><?= htmlspecialchars($fee['payment_date']) ?></td>
                          <td><?= htmlspecialchars($fee['payment_method']) ?></td>
                          <td><?= htmlspecialchars($fee['status']) ?></td>
                          <td><?= htmlspecialchars($fee['remark']) ?></td>
                          <td><?= htmlspecialchars($fee['created_at']) ?></td>
                          <td>
                            <button class="btn btn-primary btn-sm" onclick="sendReminder(<?= $fee['fee_id'] ?>)">Send Reminder</button>
                            <?php if ($fee['status'] === 'pending'): ?>
                              <button class="btn btn-success btn-sm" onclick="markAsPaid(<?= $fee['fee_id'] ?>)">Mark as Paid</button>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pending Fees Tab -->
                <div class="tab-pane fade" id="pending-fees" role="tabpanel" aria-labelledby="pending-fees-tab">
                  <table class="table mt-3">
                    <thead>
                      <tr>
                        <th>Fee ID</th>
                        <th>Institute Name</th>
                        <th>Student Name</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Created At</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (getPendingFees($conn) as $fee): ?>
                        <tr>
                          <td><?= htmlspecialchars($fee['fee_id']) ?></td>
                          <td><?= htmlspecialchars($fee['institute_name']) ?></td>
                          <td><?= htmlspecialchars($fee['student_name']) ?></td>
                          <td><?= htmlspecialchars($fee['amount']) ?></td>
                          <td><?= htmlspecialchars($fee['payment_date']) ?></td>
                          <td><?= htmlspecialchars($fee['payment_method']) ?></td>
                          <td><?= htmlspecialchars($fee['status']) ?></td>
                          <td><?= htmlspecialchars($fee['remark']) ?></td>
                          <td><?= htmlspecialchars($fee['created_at']) ?></td>
                          <td>
                            <button class="btn btn-primary btn-sm" onclick="sendReminder(<?= $fee['fee_id'] ?>)">Send Reminder</button>
                            <button class="btn btn-success btn-sm" onclick="markAsPaid(<?= $fee['fee_id'] ?>)">Mark as Paid</button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <div class="modal fade" id="feesModal" tabindex="-1" aria-labelledby="feesModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="feesModalLabel">Add student Fees</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <form id="feeForm" method="POST">
                    <input type="hidden" name="form_type" value="fees_form">

                    <div class="row g-3 mb-3">
                      <div class="col-md-4">
                        <label for="feeId" class="form-label">Fee ID</label>
                        <input type="text" class="form-control" id="feeId" name="fee_id" required>
                      </div>
                      <div class="col-md-4">
                        <label for="instituteName" class="form-label">Institute Name</label>
                        <input type="text" class="form-control" id="instituteName" name="instituteName" required>
                      </div>
                      <div class="col-md-4">
                        <label for="studentName" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="studentName" name="studentName" required>
                      </div>
                      <div class="col-md-4">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" required>
                      </div>
                    </div>

                    <div class="row g-3 mb-3">
                      <div class="col-md-4">
                        <label for="paymentDate" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="paymentDate" name="paymentDate" required>
                      </div>
                      <div class="col-md-4">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="paymentMethod">
                          <option value="Cash">Cash</option>
                          <option value="Card">Card</option>
                          <option value="Online">Online</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                          <option value="Paid">Paid</option>
                          <option value="Pending">Pending</option>
                        </select>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="remark" class="form-label">Remark</label>
                      <textarea class="form-control" id="remark" name="remark" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                      <label for="createdAt" class="form-label">Created At</label>
                      <input type="datetime-local" class="form-control" id="createdAt" name="createdAt" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="reset" class="btn btn-secondary ms-2">Clear</button>
                  </form>
                </div>













                  <!-- User Management Section -->
                  <section id="user-management" class="section mt-5 " style="display: none;">
                    <h2>User Management</h2>
                    <div class="card">
                      <div class="card-body">
                        <!-- Create User Buttons -->
                        <button class="btn btn-primary create-user-btn" data-role="office">Create Office Incharge</button>
                        <button class="btn btn-primary create-user-btn" data-role="staff">Create Staff</button>
                        <button class="btn btn-primary create-user-btn" data-role="student">Create Student</button>

                        <!-- Role Filter -->
                        <div class="mb-3">
                          <label for="roleFilter" class="form-label">Filter by Role</label>
                          <select id="roleFilter" class="form-select" onchange="window.location.href = '?role=' + this.value;">
                            <option value="">All Roles</option>
                            <option value="office" <?= $selectedRole === 'office' ? 'selected' : '' ?>>Office Incharge</option>
                            <option value="staff" <?= $selectedRole === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="student" <?= $selectedRole === 'student' ? 'selected' : '' ?>>Student</option>
                          </select>
                        </div>

                        <!-- User Feedback -->
                        <div id="userFeedback" class="mt-3"></div>

                        <!-- User Table -->
                        <table class="table mt-4">
                          <thead>
                            <tr>
                              <th>Institute</th>
                              <th>Role</th>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Contact</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($users as $user): ?>
                              <?php if (!$selectedRole || $user['role'] === $selectedRole): ?>
                                <tr id="user-<?= htmlspecialchars($user['email']) ?>">
                                  <td><?= htmlspecialchars($user['institute_name']) ?></td>
                                  <td><?= ucfirst($user['role']) ?></td>
                                  <td><?= htmlspecialchars($user['name']) ?></td>
                                  <td><?= htmlspecialchars($user['email']) ?></td>
                                  <td><?= htmlspecialchars($user['phone']) ?></td>
                                  <td>

                                    <button class="btn btn-secondary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                      data-id="<?= $user['id'] ?>"
                                      data-institute-name="<?= $user['institute_name'] ?>"
                                      data-name="<?= $user['name'] ?>"
                                      data-email="<?= $user['email'] ?>"
                                      data-phone="<?= $user['phone'] ?>"
                                      data-role="<?= $user['role'] ?>">
                                      Edit
                                    </button>
                                    <form method="POST" style="display:inline;">
                                      <input type="hidden" name="form_type" value="remove_user">
                                      <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this user?')">Remove</button>
                                    </form>
                                  </td>
                                </tr>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </section>


                  <!-- Edit User Modal -->
                  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editUserForm" action="" method="POST">
                            <input type="hidden" name="form_type" value="edit_user">
                            <input type="hidden" name="id" id="editUserId">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                              <label for="editUserInstitute" class="form-label">Institute Name</label>
                              <input type="text" class="form-control" id="editUserInstitute" name="institute_name" required>
                            </div>
                            <div class="mb-3">
                              <label for="editUserName" class="form-label">Name</label>
                              <input type="text" class="form-control" id="editUserName" name="name" required>
                            </div>
                            <div class="mb-3">
                              <label for="editUserEmail" class="form-label">Email</label>
                              <input type="email" class="form-control" id="editUserEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                              <label for="editUserPhone" class="form-label">Phone</label>
                              <input type="tel" class="form-control" id="editUserPhone" name="phone" required>
                            </div>
                            <div class="mb-3">
                              <label for="editUserRole" class="form-label">Role</label>
                              <input type="text" class="form-control" id="editUserRole" name="role" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update User</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Display success/error message -->
                  <?php if (!empty($result)) : ?>
                    <div class="alert <?= strpos($result, 'Error') === false ? 'alert-success' : 'alert-danger' ?>">
                      <?= $result ?>
                    </div>
                  <?php endif; ?>

                  <!-- Create User Modal (Single Modal for All User Types) -->
                  <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="createUserForm" action="" method="POST">
                            <input type="hidden" name="form_type" value="create_user">
                            <input type="hidden" name="role" id="createUserRole">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                              <label for="createUserInstitute" class="form-label">Institute Name</label>
                              <input type="text" class="form-control" id="createUserInstitute" name="institute_name" required>
                            </div>
                            <div class="mb-3">
                              <label for="createUserName" class="form-label">Name</label>
                              <input type="text" class="form-control" id="createUserName" name="name" required>
                            </div>
                            <div class="mb-3">
                              <label for="createUserEmail" class="form-label">Email</label>
                              <input type="email" class="form-control" id="createUserEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                              <label for="createUserPhone" class="form-label">Phone</label>
                              <input type="tel" class="form-control" id="createUserPhone" name="phone" required>
                            </div>
                            <div class="mb-3">
                              <label for="createUserPassword" class="form-label">Password</label>
                              <input type="password" class="form-control" id="createUserPassword" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Create User</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Bootstrap JS -->
                  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

                  <script>
                    function showSection(sectionId) {


                      // Show the selected section
                      const sectionToShow = document.getElementById(sectionId);
                      if (sectionToShow) {
                        sectionToShow.style.display = 'block';
                      }
                    }



                    document.addEventListener('DOMContentLoaded', function() {
                      // Get all edit buttons
                      const editButtons = document.querySelectorAll('.edit-btn');
                      // Add click event listener to each edit button
                      editButtons.forEach(button => {
                        button.addEventListener('click', () => {
                          // Get data from the button's data-* attributes
                          const branchId = button.getAttribute('data-id');
                          const instituteName = button.getAttribute('data-name');
                          const instituteAddress = button.getAttribute('data-address');
                          const instituteContact = button.getAttribute('data-contact');
                          const officeIncharge = button.getAttribute('data-incharge');
                          const officeInchargeContact = button.getAttribute('data-incharge-contact');

                          // Populate the modal fields
                          document.getElementById('editBranchId').value = branchId;
                          document.getElementById('editInstituteName').value = instituteName;
                          document.getElementById('editInstituteAddress').value = instituteAddress;
                          document.getElementById('editInstituteContact').value = instituteContact;
                          document.getElementById('editOfficeIncharge').value = officeIncharge;
                          document.getElementById('editOfficeInchargeContact').value = officeInchargeContact;
                        });
                      });
                    });

                    document.addEventListener('DOMContentLoaded', function() {
                      const createUserButtons = document.querySelectorAll('.create-user-btn');
                      const createUserModalLabel = document.getElementById('createUserModalLabel');
                      const createUserForm = document.getElementById('createUserForm');
                      const createUserRoleInput = document.getElementById('createUserRole');

                      createUserButtons.forEach(button => {
                        button.addEventListener('click', function() {
                          const role = this.getAttribute('data-role');
                          const roleText = this.textContent.trim();

                          // Update modal title
                          createUserModalLabel.textContent = roleText;

                          // Update form role input
                          createUserRoleInput.value = role;

                          // Show the modal
                          const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
                          modal.show();
                        });
                      });
                    });

                    document.addEventListener('DOMContentLoaded', function() {
                      const createUserButtons = document.querySelectorAll('.create-user-btn');
                      const createUserModalLabel = document.getElementById('createUserModalLabel');
                      const createUserForm = document.getElementById('createUserForm');
                      const createUserRoleInput = document.getElementById('createUserRole');

                      createUserButtons.forEach(button => {
                        button.addEventListener('click', function() {
                          const role = this.getAttribute('data-role');
                          const roleText = this.textContent.trim();

                          // Update modal title
                          createUserModalLabel.textContent = roleText;

                          // Update form role input
                          createUserRoleInput.value = role;

                          // Show the modal
                          const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
                          modal.show();
                        });
                      });
                    });


                    // JavaScript to populate the edit modal with user data
                    document.addEventListener('DOMContentLoaded', function() {
                      const editUserModal = document.getElementById('editUserModal');
                      editUserModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget; // Button that triggered the modal
                        const id = button.getAttribute('data-id');
                        const instituteName = button.getAttribute('data-institute-name');
                        const name = button.getAttribute('data-name');
                        const email = button.getAttribute('data-email');
                        const phone = button.getAttribute('data-phone');
                        const role = button.getAttribute('data-role');

                        // Populate the form fields
                        document.getElementById('editUserId').value = id;
                        document.getElementById('editUserInstitute').value = instituteName;
                        document.getElementById('editUserName').value = name;
                        document.getElementById('editUserEmail').value = email;
                        document.getElementById('editUserPhone').value = phone;
                        document.getElementById('editUserRole').value = role;
                      });
                    });

                    document.getElementById('createOfficeInchargeForm').addEventListener('submit', function(event) {
                      event.preventDefault();
                      const name = document.getElementById('officeInchargeName').value;
                      const email = document.getElementById('officeInchargeEmail').value;
                      const password = document.getElementById('officeInchargePassword').value;
                      const phone = document.getElementById('officeInchargePhone').value;
                      const institute_name = document.getElementById('officeInchargeInstitute').value;

                      // Call PHP function to add user (using AJAX in a real implementation)
                      const feedback = document.getElementById('userFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Office Incharge created successfully!</div>';
                      document.getElementById('createOfficeInchargeModal').querySelector('.btn-close').click();
                    });

                    document.getElementById('createStaffForm').addEventListener('submit', function(event) {
                      event.preventDefault();
                      const name = document.getElementById('staffName').value;
                      const email = document.getElementById('staffEmail').value;
                      const password = document.getElementById('staffPassword').value;
                      const phone = document.getElementById('staffPhone').value;
                      const institute_name = document.getElementById('staffInstitute').value;

                      // Call PHP function to add user (using AJAX in a real implementation)
                      const feedback = document.getElementById('userFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Staff created successfully!</div>';
                      document.getElementById('createStaffModal').querySelector('.btn-close').click();
                    });

                    document.getElementById('createStudentForm').addEventListener('submit', function(event) {
                      event.preventDefault();
                      const name = document.getElementById('studentName').value;
                      const email = document.getElementById('studentEmail').value;
                      const password = document.getElementById('studentPassword').value;
                      const phone = document.getElementById('studentPhone').value;
                      const institute_name = document.getElementById('studentInstitute').value;

                      // Call PHP function to add user (using AJAX in a real implementation)
                      const feedback = document.getElementById('userFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Student created successfully!</div>';
                      document.getElementById('createStudentModal').querySelector('.btn-close').click();
                    });

                    document.addEventListener('DOMContentLoaded', function() {
                      // Get all edit buttons
                      const editButtons = document.querySelectorAll('.edit-user-btn');

                      // Add click event listener to each edit button
                      editButtons.forEach(button => {
                        button.addEventListener('click', () => {
                          // Get data from the button's data-* attributes
                          const userId = button.getAttribute('data-id');
                          const userName = button.getAttribute('data-name');
                          const userEmail = button.getAttribute('data-email');

                          // Populate the modal fields
                          document.getElementById('editUserId').value = userId;
                          document.getElementById('editUserName').value = userName;
                          document.getElementById('editUserEmail').value = userEmail;
                        });
                      });
                    });

                    function editUser(id, name, email) {
                      document.getElementById('editUserId').value = id;
                      document.getElementById('editUserName').value = name;
                      document.getElementById('editUserEmail').value = email;
                      const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                      editModal.show();
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                      const photoInput = document.getElementById('photo');
                      const photoPreview = document.getElementById('photoPreview');

                      // Show existing photo if editing (PHP fallback)
                      const defaultPhoto = "<?= !empty($student['photo_path']) ? htmlspecialchars($student['photo_path']) : '' ?>";
                      if (defaultPhoto) {
                        photoPreview.innerHTML = `<img src="${defaultPhoto}" class="img-thumbnail" style="max-width: 200px;">`;
                      }

                      // Handle new image selection
                      photoInput.addEventListener('change', function(e) {
                        const file = this.files[0];

                        if (file) {
                          // Validate file type
                          if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                            alert('Only JPEG and PNG images are allowed!');
                            this.value = '';
                            return;
                          }

                          // Validate file size (e.g., 2MB max)
                          if (file.size > 2 * 1024 * 1024) {
                            alert('Image must be less than 2MB!');
                            this.value = '';
                            return;
                          }

                          // Preview the image
                          const reader = new FileReader();
                          reader.onload = function(e) {
                            photoPreview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
                          };
                          reader.readAsDataURL(file);
                        } else {
                          photoPreview.innerHTML = '<span class="text-muted">No photo selected</span>';
                        }
                      });
                    });




                    // Photo preview functionality
                    // document.getElementById('photo').addEventListener('change', function(e) {
                    //   const file = e.target.files[0];
                    //   if (file) {
                    //     const reader = new FileReader();
                    //     reader.onload = function(event) {
                    //       const preview = document.getElementById('photoPreview');
                    //       preview.innerHTML = '';
                    //       const img = document.createElement('img');
                    //       img.src = event.target.result;
                    //       preview.appendChild(img);
                    //     };
                    //     reader.readAsDataURL(file);
                    //   }
                    // });

                    // Form validation
                    document.getElementById('admissionForm').addEventListener('submit', function(e) {
                      let isValid = true;

                      // Clear previous errors
                      document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                      document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

                      // Validate required fields
                      const requiredFields = document.querySelectorAll('[required]');
                      requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                          field.classList.add('is-invalid');
                          document.getElementById(`${field.id}Error`).textContent = 'This field is required';
                          isValid = false;
                        }
                      });

                      // Validate email format
                      const email = document.getElementById('email');
                      if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                        email.classList.add('is-invalid');
                        document.getElementById('emailError').textContent = 'Please enter a valid email address';
                        isValid = false;
                      }

                      // Validate phone number
                      const phone = document.getElementById('phone');
                      if (phone.value && !/^[\d\s\+\-\(\)]{10,15}$/.test(phone.value)) {
                        phone.classList.add('is-invalid');
                        document.getElementById('phoneError').textContent = 'Please enter a valid phone number';
                        isValid = false;
                      }


                      // Validate file types for photo
                      const photo = document.getElementById('photo');
                      if (photo.files.length > 0) {
                        const allowedTypes = ['image/jpeg', 'image/png'];
                        if (!allowedTypes.includes(photo.files[0].type)) {
                          photo.classList.add('is-invalid');
                          document.getElementById('photoError').textContent = 'Only JPG and PNG images are allowed';
                          isValid = false;
                        }
                      }

                      if (!isValid) {
                        e.preventDefault();
                        // Scroll to first error
                        const firstError = document.querySelector('.is-invalid');
                        if (firstError) {
                          firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                          });
                        }
                      }
                    });

                    function removeUser(id) {
                      if (confirm('Are you sure you want to remove this user?')) {
                        // Call PHP function to remove user (using AJAX in a real implementation)
                        function removeUser(email) {
                          if (confirm('Are you sure you want to remove this user?')) {
                            const xhr = new XMLHttpRequest();
                            xhr.open('POST', 'admin.php', true); // Send request to the same file
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.onreadystatechange = function() {
                              if (xhr.readyState === 4 && xhr.status === 200) {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                  document.getElementById('user-' + email).remove();
                                  const feedback = document.getElementById('userFeedback');
                                  feedback.innerHTML = '<div class="alert alert-danger" role="alert">User removed successfully!</div>';
                                } else {
                                  alert(response.error);
                                }
                              }
                            };
                            xhr.send('email=' + encodeURIComponent(email));
                          }
                        }
                        const feedback = document.getElementById('userFeedback');
                        feedback.innerHTML = '<div class="alert alert-danger" role="alert">User removed successfully!</div>';
                        document.getElementById('user-' + id).remove();
                      }
                    }
                    // Fees Management Functions
                    function loadFeeData(feeId) {
                      // AJAX call to get fee data
                      fetch(`get_fee.php?id=${feeId}`)
                        .then(response => response.json())
                        .then(data => {
                          document.getElementById('formTitle').textContent = "Edit Student Fee";
                          document.getElementById('feeId').value = data.fee_id;
                          document.getElementById('instituteName').value = data.institute_name;
                          document.getElementById('studentName').value = data.student_name;
                          document.getElementById('amount').value = data.amount;
                          document.getElementById('paymentDate').value = data.payment_date;
                          document.getElementById('paymentMethod').value = data.payment_method;
                          document.getElementById('status').value = data.status;
                          document.getElementById('remark').value = data.remark;
                          document.getElementById('createdAt').value = data.created_at;

                          // Show the modal
                          const feeModal = new bootstrap.Modal(document.getElementById('feesModal'));
                          feeModal.show();
                        })
                        .catch(error => console.error('Error loading fee data:', error));
                    }

                    // Handle form submission
                    document.getElementById('feeForm').addEventListener('submit', function(e) {
                      e.preventDefault();

                      const formData = new FormData(this);
                      formData.append('form_type', 'fees_form');

                      fetch('admin.php', {
                          method: 'POST',
                          body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                          if (data.success) {
                            // Show success message
                            alert('Fee saved successfully!');
                            // Refresh the fees table
                            location.reload();
                          } else {
                            alert('Error: ' + data.error);
                          }
                        })
                        .catch(error => console.error('Error:', error));
                    });

                    // Function to mark fee as paid
                    function markAsPaid(feeId) {
                      if (confirm('Are you sure you want to mark this fee as paid?')) {
                        fetch('admin.php', {
                            method: 'POST',
                            headers: {
                              'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `form_type=update_fee_status&fee_id=${feeId}&status=Paid`
                          })
                          .then(response => response.json())
                          .then(data => {
                            if (data.success) {
                              alert('Fee status updated to Paid');
                              location.reload();
                            } else {
                              alert('Error: ' + data.error);
                            }
                          })
                          .catch(error => console.error('Error:', error));
                      }
                    }

                    // Function to send reminder
                    function sendReminder(feeId) {
                      if (confirm('Send payment reminder for this fee?')) {
                        fetch('send_reminder.php', {
                            method: 'POST',
                            headers: {
                              'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `fee_id=${feeId}`
                          })
                          .then(response => response.json())
                          .then(data => {
                            if (data.success) {
                              alert('Reminder sent successfully');
                            } else {
                              alert('Error: ' + data.error);
                            }
                          })
                          .catch(error => console.error('Error:', error));
                      }
                    }

                    // Initialize the fees modal
                    document.addEventListener('DOMContentLoaded', function() {
                      // Set default payment date to today
                      document.getElementById('paymentDate').valueAsDate = new Date();

                      // Set default created at to current datetime
                      const now = new Date();
                      document.getElementById('createdAt').value = now.toISOString().slice(0, 16);

                      // Handle edit buttons
                      document.querySelectorAll('.edit-fee-btn').forEach(button => {
                        button.addEventListener('click', function() {
                          const feeId = this.getAttribute('data-fee-id');
                          loadFeeData(feeId);
                        });
                      });

                      // New fee button
                      document.getElementById('newFeeBtn').addEventListener('click', function() {
                        document.getElementById('formTitle').textContent = "Add New Student Fee";
                        document.getElementById('feeId').value = '';
                        document.getElementById('feeForm').reset();

                        const feeModal = new bootstrap.Modal(document.getElementById('feesModal'));
                        feeModal.show();
                      });
                    });

                    function generatePerformanceReport() {
                      const feedback = document.getElementById('reportFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Performance report generated successfully!</div>';
                    }

                    function generateFeeCollectionReport() {
                      const feedback = document.getElementById('reportFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Fee collection report generated successfully!</div>';
                    }

                    function generateAdmissionReport() {
                      const feedback = document.getElementById('reportFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Admission report generated successfully!</div>';
                    }

                    function accessControl() {
                      const feedback = document.getElementById('settingsFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Access control settings updated successfully!</div>';
                    }

                    function securitySettings() {
                      const feedback = document.getElementById('settingsFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Security settings updated successfully!</div>';
                    }

                    function softwareConfigurations() {
                      const feedback = document.getElementById('settingsFeedback');
                      feedback.innerHTML = '<div class="alert alert-success" role="alert">Software configurations updated successfully!</div>';
                    }

                    document.querySelectorAll('.edit-button').forEach(button => {
                      button.addEventListener('click', function() {
                        // Get all data attributes
                        const studentId = this.getAttribute('data-id');

                        // Populate form fields
                        document.getElementById('student_id').value = studentId;
                        document.getElementById('edit_first_name').value = this.getAttribute('data-first-name');
                        document.getElementById('edit_last_name').value = this.getAttribute('data-last-name');
                        document.getElementById('edit_email').value = this.getAttribute('data-email');
                        document.getElementById('edit_phone').value = this.getAttribute('data-phone');
                        document.getElementById('edit_dob').value = this.getAttribute('data-dob');
                        document.getElementById('edit_address').value = this.getAttribute('data-address');
                        document.getElementById('edit_course').value = this.getAttribute('data-course');
                        document.getElementById('edit_school_type').value = this.getAttribute('data-school-type');
                        document.getElementById('edit_school').value = this.getAttribute('data-school');
                        document.getElementById('edit_parent_name').value = this.getAttribute('data-parent-name');
                        document.getElementById('edit_parent_phone').value = this.getAttribute('data-parent-phone');
                        document.getElementById('edit_referred_by').value = this.getAttribute('data-referred-by-about');
                        document.getElementById('edit_admission_accepted_by').value = this.getAttribute('data-admission-accepted-by');
                        document.getElementById('edit_institute_name').value = this.getAttribute('data-institute-name');
                        document.getElementById('edit_admission_code').value = this.getAttribute('data-admission-code');

                        // Handle radio buttons
                        const gender = this.getAttribute('data-gender');
                        if (gender) {
                          document.querySelector(`input[name="gender"][value="${gender}"]`).checked = true;
                        }

                        const status = this.getAttribute('data-status');
                        if (status) {
                          document.querySelector(`input[name="status"][value="${status}"]`).checked = true;
                        }

                        // Handle photo preview
                        const photoPreview = document.getElementById('edit_photo_preview');
                        const photoUrl = this.getAttribute('data-photo');
                        const imgElement = photoPreview.querySelector('img');
                        const noPhotoElement = photoPreview.querySelector('.no-photo');

                        if (photoUrl && photoUrl !== 'Photo Not Available') {
                          imgElement.src = photoUrl;
                          imgElement.style.display = 'block';
                          noPhotoElement.style.display = 'none';
                        } else {
                          imgElement.style.display = 'none';
                          noPhotoElement.style.display = 'inline';
                        }

                        // Show modal
                        new bootstrap.Modal(document.getElementById('updateadmissionModal')).show();
                      });
                    });







                    // Initially show the overview section
                    showSection('overview');


                    // Add event listeners to forms
                    document.getElementById('addInstituteForm').addEventListener('submit', addInstitute);
                    document.getElementById('editInstituteForm').addEventListener('submit', editInstitute);
                    document.getElementById('createOfficeInchargeForm').addEventListener('submit', createOfficeIncharge);
                    document.getElementById('createStaffForm').addEventListener('submit', createStaff);
                    document.getElementById('createStudentForm').addEventListener('submit', createStudent);
                  </script>
</body>

</html>