<?php 
include 'db_connect.php' ;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vemac - Vedic Maths Classes</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-light">
    <div class="container-fluid">

      <a class="navbar-brand" href="#">
        <img src="./images/Vedic Maths Classes logo.png" alt="" width="30" height="24">
      </a>

        <a href="#" class="navbar-brand">Vemac</a>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav">
                <a href="#home" class="nav-item nav-link active">Home</a>
                <a href="#About" class="nav-item nav-link">Profile</a>
                <a href="#inquiry" class="nav-item nav-link">Inquiry</a>
                <a href="#contact" class="nav-item nav-link">Contact</a>
            </div>
            <div class="navbar-nav ms-auto">
                <a href="./login.html" class="nav-item nav-link">Login</a>
            </div>
        </div>
    </div>
</nav>

  <!-- Home Section -->
  <section id="home" class="home fade-in">
    <div class="container  text-center ">
      <a href="register.html" id="join" class="btn btn-primary  btn-lg">Join Now</a>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features container">
    <div class="row">
      <div class="col-md-4 feature-item">
        <i class="fas fa-cogs"></i>
        <h4>Customizable Solutions</h4>
        <p>Our solutions are tailored to meet your specific business requirements.</p>
      </div>
      <div class="col-md-4 feature-item">
        <i class="fas fa-user-friends"></i>
        <h4>Dedicated Support</h4>
        <p>Our team is always ready to assist you with any challenges you face.</p>
      </div>
      <div class="col-md-4 feature-item">
        <i class="fas fa-chart-line"></i>
        <h4>Growth Focused</h4>
        <p>We aim to help your business grow and achieve its full potential.</p>
      </div>
    </div>
  </section>
  
  
   <!-- About Section -->
<section id="About" class="bg-light  text-dark">
  <div class="container py-4">
    <header class="text-center mb-4">
      <h1 class="display-4 fw-bold">Roshan Sir</h1>
      <p class="text-warning mt-2">
        Unlocking the beauty of numbers and the wonders of problem-solving
      </p>
      <p class="text-secondary mt-2">
        I'm Roshan Sir, your dedicated Mathematics teacher, here to make every equation an exciting discovery!
      </p>
    </header>
    <div class="row g-4 ">
      <div class="col-md-3 text-center text-md-start">
        <img 
          alt="Roshan Sir's profile picture" 
          class="rounded-circle mb-3 mx-auto mx-md-0 d-block" 
          src="./images/profile.jpg" 
          width="150" 
          height="150" 
        />
        <div class="d-flex justify-content-center justify-content-md-start gap-3 mb-3">
          <a class="text-primary" href="#">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a class="text-info" href="#">
            <i class="fab fa-twitter"></i>
          </a>
          <a class="text-danger" href="#">
            <i class="fab fa-instagram"></i>
          </a>
          <a class="text-secondary" href="#">
            <i class="fas fa-envelope"></i>
          </a>
        </div>
        <div class="bg-white p-4 mt-4 rounded shadow">
          <h2 class="h5 fw-bold mb-3">Tutoring Subjects</h2>
          <ul class="list-unstyled">
            <li>Mathematics</li>
            <li>Physics</li>
            <li>Chemistry</li>
            <li>Vedic Maths</li>
          </ul>
        </div>
        <div class="col-md-12 mt-4 ">
        <div class="bg-white  p-4 rounded shadow">
          <h2 class="h5 fw-bold mb-3">Contact Information</h2>
          <p class="mb-3">Book your Session now with Calendly</p>
          <div class="d-flex flex-column gap-2">
            <a class="d-flex align-items-center text-primary" href="#">
              <i class="fas fa-calendar-alt me-2"></i>Calendly
            </a>
            <a class="d-flex align-items-center text-primary" href="#">
              <i class="fab fa-facebook-f me-2"></i>Facebook
            </a>
            <a class="d-flex align-items-center text-danger" href="#">
              <i class="fab fa-instagram me-2"></i>Instagram
            </a>
            <a class="d-flex align-items-center text-success" href="#">
              <i class="fab fa-whatsapp me-2"></i>WhatsApp
            </a>
          </div>
        </div>
      </div>
      </div>
      
      <div class="col-md-6">
        <div class="bg-white p-4 rounded shadow mb-3">
          <h2 class="h5 fw-bold mb-3">Educator Profile</h2>
          <p class="mb-3"><strong>Passionate and improvement-driven Mathematics educator</strong> with over 11 years of expertise, specializing in IB, IGCSE, British and American Curriculum, and CBSE.</p>
          <p class="mb-3">Beyond traditional methods, my role as a <strong>VEDIC MATHS teacher</strong> enhances tutoring, making academic understanding both accessible and engaging.</p>
          <p class="mb-3">Having guided more than 2500 students to conquer challenges in Math's and Science, join me on a transformative learning journey where excellence meets enthusiasm.</p>
          <p>Elevate your academic experience with a dedicated educator who goes beyond the textbook to inspire a lifelong love for learning.</p>
        </div>
        <div class="bg-white p-4 rounded shadow mb-3">
          <h2 class="h5 fw-bold mb-3">Ed. Qualification</h2>
          <ul class="list-unstyled">
            <li>Primary Education: Morden Era English School, Biratnagar, Nepal</li>
            <li>High School: GTB Public School Model Town Delhi, India (IN)</li>
            <li>Graduation (BSc): Delhi University, India (IN)</li>
            <li>Post Graduation (MSc): Delhi University (IN)</li>
          </ul>
        </div>
        
      </div>
    </div>
    <p class="text-center mt-4 text-secondary">
      ©VEMAC INDIA
    </p>
  </div>
</section>

 <!-- Inquiry Section -->
<section id="inquiry" class="py-5 fade-in">
  <div class="container mt-3 mb-3">
    <h2 class="text-center mb-4">Inquiry Form</h2>
    <form id="inquiryForm" class="mx-auto" style="max-width: 500px;" action="submit_inquiry.php" method="POST" enctype="multipart/form-data">
      <!-- Role Selector -->
      <div class="mb-3">
        <select class="form-select text-center" id="roleSelector" name="role" onchange="toggleFields()">
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
      </div>

      <!-- Shared Fields -->
      <div class="form-group mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="name" placeholder="Name...." name="name" required>
        <i class="fas fa-user form-control-icon"></i>
      </div>
      <div class="form-group mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" placeholder="xyz@gmail.com" name="email" required>
        <i class="fas fa-envelope form-control-icon"></i>
      </div>
      <div class="form-group mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="tel" class="form-control" id="phone" pattern="[0-9]{10}" placeholder="9876543210" name="phone" required>
        <i class="fas fa-phone form-control-icon"></i>
      </div>
      <div class="form-group mb-3">
        <label for="subjects" class="form-label">Subjects</label>
        <textarea id="subjects" class="form-control" rows="1" name="subjects" required></textarea>
        <i class="fas fa-pencil-alt form-control-icon"></i>
      </div>

      <!-- Student-Specific Fields -->
      <div id="studentFields" class="">
        <div class="mb-3">
          <label for="grade" class="form-label">Current Grade</label>
          <select class="form-select" id="grade" name="grade">
            <option value="">Select Grade</option>
            <option value="6">Grade 6</option>
            <option value="7">Grade 7</option>
            <option value="8">Grade 8</option>
            <option value="9">Grade 9</option>
            <option value="10">Grade 10</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
          </select>
        </div>
      </div>

      <!-- Teacher-Specific Fields -->
      <div id="teacherFields" class="hide">
        <div class="form-group mb-3">
          <label for="qualification" class="form-label">Highest Qualification</label>
          <input type="text" class="form-control" id="qualification" name="qualification">
          <i class="fas fa-graduation-cap form-control-icon"></i>
        </div>
        <div class="form-group mb-3">
          <i class="fas fa-clock form-control-icon"></i>
          <label for="preferredTime" class="form-label">Preferred Class Time After</label>
          <input type="time" class="form-control" id="preferredTime" name="preferred_time">
        </div>
        <div class="form-group mb-3">
          <label for="message" class="form-label">Description</label>
          <textarea id="message" class="form-control" rows="4" name="message"></textarea>
          <i class="fas fa-pencil-alt form-control-icon"></i>
        </div>
        <div class="mb-3">
          <label for="cv" class="form-label">Attach CV</label>
          <input type="file" class="form-control" id="cv" name="cv" accept=".pdf,.doc,.docx">
          <small class="form-text text-muted">Only PDF, DOC, and DOCX files are allowed.</small>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit"  class="btn btn-primary w-100">Submit</button>
    </form>
  </div>
</section>

  <!-- Contact Section -->
  <!-- Contact Footer -->
<footer id='contact' class="bg-dark text-white py-5">
  <div class="container">
    <div class="row">
      <!-- Left Column: Contact Information -->
      <div class="col-md-6 mb-4">
        <h5>Contact Us</h5>
        <p><i class="fas fa-phone-alt text-primary me-2"></i>+91 9267939622</p>
        <p><i class="fas fa-envelope text-primary me-2"></i>vemacroot@gmail.com</p>
        <p><i class="fas fa-map-marker-alt text-primary me-2"></i>123 Main Street, New York, NY 10001</p>
      </div>

      <!-- Right Column: Social Media Links -->
      <div class="col-md-6 mb-4">
        <h5>Contact </h5>
        <div class=" m-auto ">
          <a href="tel:9267939622" target="_blank" class="text-white me-4"><i class="fas fa-phone-alt fa-2x"></i></a>
          <a href="vemacroot@gmail.com" target="_blank" class="text-white me-4"><i class="fas fa-envelope fa-2x"></i></a>
          <a href="https://www.instagram.com/vemac__" target="_blank" class="text-white me-4"><i class="fab fa-instagram fa-2x"></i></a>
          <a href="https://wa.me/919267939622" target="_blank" class="text-white"><i class="fab fa-whatsapp fa-2x"></i></a>
        </div>
      </div>
    </div>

    <!-- Map Section -->
    <div class="row mt-4">
      <div class="col-12">
        <h5>Our Location</h5>
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3023.7032632740163!2d-74.00594128459363!3d40.71277597933017!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x7a3b9e6c9a4e46c5!2s123%20Main%20Street%2C%20New%20York%2C%20NY%2010001!5e0!3m2!1sen!2sus!4v1618901720407!5m2!1sen!2sus"
          width="100%"
          height="250"
          style="border: 0; border-radius: 10px;"
          allowfullscreen=""
          loading="lazy">
        </iframe>
      </div>
    </div>
  </div>
</footer>


  <!-- Footer -->
  <footer>
    <p>&copy; 2025 Vemac - Vedic Maths Classes</p>
  </footer>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="script.js"></script>
</body>
</html>
