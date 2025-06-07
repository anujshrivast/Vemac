<?php
include 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = [
        'institute_branch_id', 'name', 'dob', 'gender', 
        'student_phone', 'parent_phone', 'address',
        'admission_date', 'subjects', 'status'
    ];
    
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }
    
    // Validate phone numbers
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['student_phone'])) {
        $errors[] = "Invalid student phone number format (10-15 digits required).";
    }
    
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['parent_phone'])) {
        $errors[] = "Invalid parent phone number format (10-15 digits required).";
    }
    
    // Validate email if provided
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Validate date formats
    $currentDate = new DateTime();
    $dobDate = DateTime::createFromFormat('Y-m-d', $_POST['dob']);
    $admissionDate = DateTime::createFromFormat('Y-m-d', $_POST['admission_date']);
    
    if (!$dobDate || $dobDate > $currentDate) {
        $errors[] = "Invalid date of birth (must be in the past).";
    }
    
    if (!$admissionDate || $admissionDate > $currentDate) {
        $errors[] = "Invalid admission date (must be today or in the past).";
    }
    
    // Handle file upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Check file size (max 2MB)
        if ($_FILES['photo']['size'] > 2097152) {
            $errors[] = "Photo size exceeds 2MB limit.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($fileInfo, $_FILES['photo']['tmp_name']);
            
            if (in_array($detectedType, $allowedTypes)) {
                $uploadDir = 'uploads/students/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('student_') . '.' . $extension;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                    $photoPath = $destination;
                } else {
                    $errors[] = "Failed to upload photo.";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            }
        }
    }
    
    // If errors exist, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: admission.php");
        exit();
    }
    
    // Generate admission code (INST-YEAR-0001)
    $year = date('Y');
    $lastAdmission = $conn->query("SELECT admission_code FROM students WHERE admission_code LIKE 'INST-$year-%' ORDER BY student_id DESC LIMIT 1");
    
    if ($lastAdmission->num_rows > 0) {
        $lastCode = $lastAdmission->fetch_assoc()['admission_code'];
        $lastNumber = intval(substr($lastCode, -4));
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '0001';
    }
    
    $admission_code = "INST-$year-$newNumber";
    
    // Sanitize and prepare data
    $institute_branch_id = (int)$_POST['institute_branch_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $student_phone = $conn->real_escape_string($_POST['student_phone']);
    $parent_phone = $conn->real_escape_string($_POST['parent_phone']);
    $email = !empty($_POST['email']) ? $conn->real_escape_string($_POST['email']) : null;
    $address = $conn->real_escape_string($_POST['address']);
    $admission_date = $conn->real_escape_string($_POST['admission_date']);
    $subjects = $conn->real_escape_string($_POST['subjects']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Insert into database using prepared statement
    $stmt = $conn->prepare("INSERT INTO students (
        admission_code, institute_branch_id, name, dob, gender, 
        student_phone, parent_phone, email, address, photo,
        admission_date, subjects, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "sisssssssssss",
        $admission_code,
        $institute_branch_id,
        $name,
        $dob,
        $gender,
        $student_phone,
        $parent_phone,
        $email,
        $address,
        $photoPath,
        $admission_date,
        $subjects,
        $status
    );
    
    if ($stmt->execute()) {
        header("Location: admission.php?success=1&admission_code=$admission_code");
    } else {
        $_SESSION['errors'] = ["Database error: " . $stmt->error];
        $_SESSION['old_input'] = $_POST;
        header("Location: admission.php");
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: admission.php");
    exit();
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
        .photo-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
            display: none;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
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
                            <i class="fas fa-check-circle me-2"></i>
                            Student admission successful! Admission Code: <strong><?php echo htmlspecialchars($_GET['admission_code']); ?></strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0">
                                <?php foreach($_SESSION['errors'] as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <form action="process_admission.php" method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class="fas fa-school me-2"></i>Institute Information</h5>
                                
                                <div class="mb-3">
                                    <label for="institute_branch_id" class="form-label required-field">Institute Branch</label>
                                    <select class="form-select" id="institute_branch_id" name="institute_branch_id" required>
                                        <option value="">Select Institute Branch</option>
                                        <?php
                                        $branches = $conn->query("SELECT * FROM institute_branches WHERE status='Active'");
                                        while($branch = $branches->fetch_assoc()):
                                            $selected = isset($_SESSION['old_input']['institute_branch_id']) && $_SESSION['old_input']['institute_branch_id'] == $branch['branch_id'] ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($branch['institute_name'] . " - " . $branch['location']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <h5 class="mb-3 mt-4"><i class="fas fa-user me-2"></i>Personal Information</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_SESSION['old_input']['name']) ? htmlspecialchars($_SESSION['old_input']['name']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dob" class="form-label required-field">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" 
                                           value="<?php echo isset($_SESSION['old_input']['dob']) ? htmlspecialchars($_SESSION['old_input']['dob']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required-field">Gender</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="male" value="Male" 
                                                <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'Male') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="female" value="Female"
                                                <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'Female') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="other" value="Other"
                                                <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'Other') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="other">Other</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Student Photo</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <img id="photoPreview" src="#" alt="Photo Preview" class="photo-preview mt-2">
                                    <div class="form-text">Max size: 2MB (JPG, PNG, GIF)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class="fas fa-phone-alt me-2"></i>Contact Information</h5>
                                
                                <div class="mb-3">
                                    <label for="student_phone" class="form-label required-field">Student Phone</label>
                                    <input type="tel" class="form-control" id="student_phone" name="student_phone" 
                                           value="<?php echo isset($_SESSION['old_input']['student_phone']) ? htmlspecialchars($_SESSION['old_input']['student_phone']) : ''; ?>" required>
                                    <div class="form-text">Format: 10-15 digits</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="parent_phone" class="form-label required-field">Parent/Guardian Phone</label>
                                    <input type="tel" class="form-control" id="parent_phone" name="parent_phone" 
                                           value="<?php echo isset($_SESSION['old_input']['parent_phone']) ? htmlspecialchars($_SESSION['old_input']['parent_phone']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label required-field">Full Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_SESSION['old_input']['address']) ? htmlspecialchars($_SESSION['old_input']['address']) : ''; ?></textarea>
                                </div>
                                
                                <h5 class="mb-3 mt-4"><i class="fas fa-book me-2"></i>Academic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="admission_date" class="form-label required-field">Admission Date</label>
                                    <input type="date" class="form-control" id="admission_date" name="admission_date" 
                                           value="<?php echo isset($_SESSION['old_input']['admission_date']) ? htmlspecialchars($_SESSION['old_input']['admission_date']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subjects" class="form-label required-field">Subjects</label>
                                    <input type="text" class="form-control" id="subjects" name="subjects" 
                                           value="<?php echo isset($_SESSION['old_input']['subjects']) ? htmlspecialchars($_SESSION['old_input']['subjects']) : ''; ?>" required>
                                    <div class="form-text">Separate multiple subjects with commas</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label required-field">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Active" <?php echo (isset($_SESSION['old_input']['status']) && $_SESSION['old_input']['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo (isset($_SESSION['old_input']['status']) && $_SESSION['old_input']['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Suspended" <?php echo (isset($_SESSION['old_input']['status']) && $_SESSION['old_input']['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
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
    <!-- Photo Preview -->
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.style.display = 'block';
                    preview.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        document.getElementById('photo').addEventListener('change', function() {
            readURL(this);
        });
        
        // Clear old input from session after page load
        <?php unset($_SESSION['old_input']); ?>
    </script>
</body>
</html>
