<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Office Incharge Panel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .sidebar {
            height: 100vh;
            background: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 10px;
            text-decoration: none;
            display: block;
            color: white;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                height: auto;
                padding-top: 10px;
            }
            .content {
                margin-left: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="d-flex">
    <nav class="sidebar col-md-3 col-lg-2 d-md-block bg-dark text-white">
        <h4 class="text-center text-white">Office Incharge</h4>
        <a href="#" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a>
        <a href="#" onclick="showSection('student-management')"><i class="fas fa-user-graduate"></i> Student Management</a>
        <a href="#" onclick="showSection('fee-management')"><i class="fas fa-wallet"></i> Fee Management</a>
        <a href="#" onclick="showSection('batch-scheduling')"><i class="fas fa-calendar"></i> Batch Scheduling</a>
        <a href="#" onclick="showSection('attendance')"><i class="fas fa-check-circle"></i> Attendance Tracking</a>
        <a href="#" onclick="showSection('admission')"><i class="fas fa-user-plus"></i> Admissions</a>
        <a href="#" onclick="showSection('communication')"><i class="fas fa-envelope"></i> Communication</a>
        <a href="logout.php">Logout</a> <!-- Logout link -->
    </nav>

    <!-- Content Area -->
    <div class="content col-md-9 col-lg-10">

        <!-- Dashboard -->
        <section id="dashboard" class="section">
            <h2>Dashboard</h2>
            <p>Overview of students, payments, batches, and attendance.</p>
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-primary text-white p-3">
                        <h5>Total Students</h5>
                        <h2>150</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white p-3">
                        <h5>Pending Fees</h5>
                        <h2>₹25,000</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white p-3">
                        <h5>Active Batches</h5>
                        <h2>8</h2>
                    </div>
                </div>
            </div>
        </section>

        <hr>

        <!-- Student Management -->
        <section id="student-management" class="section">
            <h2>Student Management</h2>
            <button class="btn btn-primary my-2" onclick="addNewStudent()">Add New Student</button>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Batch</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Aryan Sharma</td>
                        <td>Class 8</td>
                        <td>Batch A</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editStudent('Aryan Sharma')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="removeStudent('Aryan Sharma')">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <hr>

        <!-- Fee Management -->
        <section id="fee-management" class="section">
            <h2>Fee Management</h2>
            <p>Track payments, pending dues, and send reminders.</p>
            <button class="btn btn-success" onclick="sendReminder()">Send Reminder</button>
        </section>

        <hr>

        <!-- Batch Scheduling -->
        <section id="batch-scheduling" class="section">
            <h2>Batch & Class Scheduling</h2>
            <p>Assign teachers and create class timetables.</p>
        </section>

        <hr>

        <!-- Attendance Tracking -->
        <section id="attendance" class="section">
            <h2>Attendance Tracking</h2>
            <button class="btn btn-info" onclick="markAttendance()">Mark Attendance</button>
        </section>

        <hr>

        <!-- Admission Management -->
        <section id="admission" class="section">
            <h2>Admission Management</h2>
            <button class="btn btn-primary" onclick="newAdmissions()">New Admissions</button>
        </section>

        <hr>

        <!-- Communication -->
        <section id="communication" class="section">
            <h2>Communication</h2>
            <button class="btn btn-warning" onclick="sendNotification()">Send Notification</button>
        </section>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.style.display = 'none';
        });
        // Show the selected section
        document.getElementById(sectionId).style.display = 'block';
    }

    function addNewStudent() {
        alert('Add New Student functionality is not implemented yet.');
    }

    function editStudent(studentName) {
        alert(`Edit functionality for ${studentName} is not implemented yet.`);
    }

    function removeStudent(studentName) {
        if (confirm(`Are you sure you want to remove ${studentName}?`)) {
            alert(`${studentName} has been removed.`);
        }
    }

    function sendReminder() {
        alert('Send Reminder functionality is not implemented yet.');
    }

    function markAttendance() {
        alert('Mark Attendance functionality is not implemented yet.');
    }

    function newAdmissions() {
        alert('New Admissions functionality is not implemented yet.');
    }

    function sendNotification() {
        alert('Send Notification functionality is not implemented yet.');
    }

    // Initially show the dashboard section
    showSection('dashboard');
</script>

</body>
</html>
