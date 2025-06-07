<?php 
include 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['name', 'dob', 'gender', 'email', 'password', 'admission_date', 'subjects', 'batch_code'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required.";
        }
    }
    
    // Check password match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // If errors exist, show them
    if (!empty($errors)) {
        session_start();
        $_SESSION['errors'] = $errors;
        header("Location: admission.php");
        exit();
    }
    
    // Sanitize and prepare data
    $name = $conn->real_escape_string($_POST['name']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : null;
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = isset($_POST['address']) ? $conn->real_escape_string($_POST['address']) : null;
    $admission_date = $conn->real_escape_string($_POST['admission_date']);
    $subjects = $conn->real_escape_string($_POST['subjects']);
    $batch_code = $conn->real_escape_string($_POST['batch_code']);
    $batch_id = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : null;
    
    // Generate student ID (you can customize this logic)
    $student_id = date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert into database
    $sql = "INSERT INTO students (student_id, name, dob, gender, phone, email, password, address, 
            admission_date, subjects, batch_code, batch_id) 
            VALUES ('$student_id', '$name', '$dob', '$gender', '$phone', '$email', '$password', 
            '$address', '$admission_date', '$subjects', '$batch_code', $batch_id)";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: admission.php?success=1&student_id=$student_id");
    } else {
        session_start();
        $_SESSION['errors'] = ["Error: " . $conn->error];
        header("Location: admission.php");
    }
    
    $conn->close();
} else {
    header("Location: admission.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Admission</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .form-header h2 {
            font-weight: 700;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <div class="form-header">
                        <h2><i class="fas fa-user-graduate me-2"></i>Student Admission Form</h2>
                        <p class="text-muted">Please fill all required fields carefully</p>
                    </div>

                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Student admission successful! Student ID: <?php echo htmlspecialchars($_GET['student_id']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="process_admission.php" method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Personal Information</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dob" class="form-label required-field">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required-field">Gender</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="female" value="Female">
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="other" value="Other">
                                            <label class="form-check-label" for="other">Other</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Account Information</h5>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label required-field">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label required-field">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Address Information</h5>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Full Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Academic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="admission_date" class="form-label required-field">Admission Date</label>
                                    <input type="date" class="form-control" id="admission_date" name="admission_date" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subjects" class="form-label required-field">Subjects</label>
                                    <input type="text" class="form-control" id="subjects" name="subjects" required>
                                    <div class="form-text">Separate multiple subjects with commas</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="batch_code" class="form-label required-field">Batch Code</label>
                                    <input type="text" class="form-control" id="batch_code" name="batch_code" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="batch_id" class="form-label">Batch ID</label>
                                    <input type="number" class="form-control" id="batch_id" name="batch_id">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Submit Admission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Password confirmation validation -->
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>