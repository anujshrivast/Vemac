<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Admission</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://via.placeholder.com/1920x1080');
            background-size: cover;
            color: white;
            padding: 100px 20px;
            text-align: center;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-lg {
            max-width: 800px;
        }
        #photoPreview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
        }
        #photoPreview img {
            max-width: 100%;
            max-height: 100%;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>

    <!-- Hero Section with Apply Button -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Welcome to Our School</h1>
            <p class="lead mb-4">Quality education for a brighter future</p>
            <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#admissionModal">
                <i class="bi bi-pencil-square"></i> Apply Now
            </button>
        </div>
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
                    <form id="admissionForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label required-field">First Name</label>
                                <input type="text" class="form-control" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control" id="lastName" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label required-field">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label required-field">Phone</label>
                                <input type="tel" class="form-control" id="phone" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="dob" class="form-label required-field">Date of Birth</label>
                                <input type="date" class="form-control" id="dob" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required-field">Gender</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="male" required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="female">
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="photo" class="form-label">Student Photo</label>
                                <input type="file" class="form-control" id="photo" accept="image/*">
                                <div id="photoPreview" class="mt-2">
                                    <span class="text-muted">No photo selected</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label required-field">Address</label>
                            <input type="text" class="form-control" id="address" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="course" class="form-label required-field">Course</label>
                                <select class="form-select" id="course" required>
                                    <option value="">Select Course</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Business">Business</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery first, then Bootstrap JS Bundle with Popper -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Photo preview functionality
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photoPreview');
            
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '<span class="text-muted">No photo selected</span>';
            }
        });

        // Form submission handling
        document.getElementById('admissionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (this.checkValidity()) {
                alert('Form submitted successfully!');
                // Here you would typically send the data to your server
                
                // Close modal after submission
                const modal = bootstrap.Modal.getInstance(document.getElementById('admissionModal'));
                modal.hide();
                
                // Reset form
                this.reset();
                document.getElementById('photoPreview').innerHTML = '<span class="text-muted">No photo selected</span>';
            } else {
                // If form is invalid, show validation messages
                this.classList.add('was-validated');
            }
        });
    </script>
</body>
</html>