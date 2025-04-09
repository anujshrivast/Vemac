<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Portal</title>

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
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="d-flex">
    <nav class="sidebar col-md-3 col-lg-2 d-md-block bg-dark text-white">
        <h4 class="text-center text-white">Student Portal</h4>
        <a href="#profile" onclick="showSection('profile')"><i class="fas fa-user"></i> Profile & Enrollment</a>
        <a href="#fees" onclick="showSection('fees')"><i class="fas fa-wallet"></i> Fee History</a>
        <a href="#schedule" onclick="showSection('schedule')"><i class="fas fa-calendar"></i> Class Schedule</a>
        <a href="#materials" onclick="showSection('materials')"><i class="fas fa-book"></i> Study Materials</a>
        <a href="#exams" onclick="showSection('exams')"><i class="fas fa-file-alt"></i> Exam Scores</a>
        <a href="#support" onclick="showSection('support')"><i class="fas fa-headset"></i> Support</a>
        <a href="logout.php">Logout</a> <!-- Logout link -->
    </nav>

    <!-- Content Area -->
    <div class="content col-md-9 col-lg-10">

        <!-- Profile & Enrollment -->
        <section id="profile" class="section">
            <h2>Profile & Enrollment Details</h2>
            <div class="card p-3">
                <h4>Student Name: Aryan Sharma</h4>
                <p><strong>Enrollment No:</strong> 20241001</p>
                <p><strong>Class:</strong> 10th Grade</p>
                <p><strong>Batch:</strong> A</p>
            </div>
        </section>

        <hr>

        <!-- Fee Payment History -->
        <section id="fees" class="section">
            <h2>Fee Payment History & Dues</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2025-02-01</td>
                        <td>₹5000</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>2025-03-01</td>
                        <td>₹5000</td>
                        <td><span class="badge bg-danger">Pending</span></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <hr>

        <!-- Class Schedule -->
        <section id="schedule" class="section">
            <h2>Class Schedule & Attendance</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Mathematics</td>
                        <td>10:00 AM - 11:00 AM</td>
                        <td><span class="badge bg-success">Present</span></td>
                    </tr>
                    <tr>
                        <td>Science</td>
                        <td>11:30 AM - 12:30 PM</td>
                        <td><span class="badge bg-danger">Absent</span></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <hr>

        <!-- Study Materials -->
        <section id="materials" class="section">
            <h2>Study Materials & Assignments</h2>
            <p><a href="math_notes.pdf" class="btn btn-primary">Download Math Notes</a></p>
            <p><a href="science_assignment.docx" class="btn btn-primary">Download Science Assignment</a></p>
        </section>

        <hr>

        <!-- Exam & Test Scores -->
        <section id="exams" class="section">
            <h2>Exam & Test Scores</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Test Date</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Mathematics</td>
                        <td>2025-02-10</td>
                        <td>85/100</td>
                    </tr>
                    <tr>
                        <td>Science</td>
                        <td>2025-02-15</td>
                        <td>78/100</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <hr>

        <!-- Support Ticket System -->
        <section id="support" class="section">
            <h2>Support Ticket System</h2>
            <form id="supportForm">
                <div class="mb-3">
                    <label for="queryType" class="form-label">Select Query Type:</label>
                    <select class="form-select" id="queryType">
                        <option>Fee Issue</option>
                        <option>Class Schedule Issue</option>
                        <option>Exam Result Query</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="queryMessage" class="form-label">Describe your issue:</label>
                    <textarea class="form-control" id="queryMessage" rows="3"></textarea>
                </div>
                <button type="button" class="btn btn-warning" onclick="submitQuery()">Submit Query</button>
            </form>
            <div id="supportResponse" class="mt-3"></div>
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

    function submitQuery() {
        const queryType = document.getElementById('queryType').value;
        const queryMessage = document.getElementById('queryMessage').value;
        const supportResponse = document.getElementById('supportResponse');

        // Simulate a response based on the query type
        let responseMessage = `Your query regarding "${queryType}" has been submitted. We will get back to you soon.`;
        if (queryMessage) {
            responseMessage += `<br>Your message: "${queryMessage}"`;
        }

        supportResponse.innerHTML = `<div class="alert alert-success" role="alert">${responseMessage}</div>`;
    }

    // Initially show the profile section
    showSection('profile');
</script>

</body>
</html>
