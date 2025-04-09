<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Prepare and bind
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Generate reset token
            $token = bin2hex(random_bytes(50));
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token expires in 1 hour
            
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $token, $expiry, $email);
            $updateStmt->execute();
            
            // Send email (this part needs actual email configuration)
            // Example using PHPMailer (you need to install PHPMailer via Composer)
            /*
            require 'vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com';
            $mail->Password = 'your_password';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom('no-reply@example.com', 'Your Website');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the link to reset your password: <a href='http://example.com/reset_password.php?token=$token'>Reset Password</a>";
            
            if (!$mail->send()) {
                echo "<script>document.getElementById('message').innerHTML = 'Mailer Error: " . $mail->ErrorInfo . "';</script>";
            } else {
                echo "<script>document.getElementById('message').innerHTML = 'A reset link has been sent to your email.';</script>";
            }
            */
            
            echo "<script>document.getElementById('message').innerHTML = 'To reset your Password , Contact to Your Office Incharge.';</script>";
        } else {
            echo "<script>document.getElementById('message').innerHTML = 'Email not found.';</script>";
        }
        
        $stmt->close();
        $updateStmt->close();
    } else {
        echo "<script>document.getElementById('message').innerHTML = 'Invalid email address.';</script>";
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('./images/s-bg.png');
            background-size: cover;
            background-position: center;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <p>Enter your email to receive a password reset link.</p>
        <form action="forgot_password.php" method="POST">
            <input type="email" name="email" id="email" placeholder="Enter your email" required>
            <button type="submit">Reset Password</button>
        </form>
        <p id="message"></p>
    </div>
</body>
</html>