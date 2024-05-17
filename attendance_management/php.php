<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_system";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Initialize session variables if not set
if (!isset($_SESSION['students'])) {
    $_SESSION['students'] = [];
}

$showAttendanceCode = false;
$error = '';
$message = '';

// Generate code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_code'])) {
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);
    if ($email == 'prof@example.com' && $password == 'password') {
        $code = rand(1000, 9999);
        $_SESSION['attendanceCode'] = $code;
        $showAttendanceCode = true; // Set to true after successful login
    } else {
        $error = "Incorrect email or password.";
    }
}

// Check code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_code'])) {
    $enteredCode = htmlspecialchars($_POST['code']);
    if ($enteredCode == $_SESSION['attendanceCode']) {
        $_SESSION['attendanceFormVisible'] = true;
    } else {
        $error = "Incorrect code.";
    }
}

// Submit attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $name = htmlspecialchars($_POST['name']);
    $surname = htmlspecialchars($_POST['surname']);
    $students = $_SESSION['students'];
    $students[] = [
        'name' => $name,
        'surname' => $surname,
        'status' => 'Present',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $_SESSION['students'] = $students;

    // Check and delete from absents table
    $stmt = $conn->prepare("DELETE FROM absents WHERE name=? AND surname=?");
    $stmt->bind_param("ss", $name, $surname);
    $stmt->execute();
    $stmt->close();
    unset($_SESSION['attendanceFormVisible']); // Unset after submitting attendance
}

// Save data to database
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_data'])) {
    $students = $_SESSION['students'];
    foreach ($students as $student) {
        $name = $student['name'];
        $surname = $student['surname'];

        $stmt = $conn->prepare("SELECT * FROM registered_students WHERE name=? AND surname=?");
        $stmt->bind_param("ss", $name, $surname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt_insert = $conn->prepare("INSERT INTO students (name, surname) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $name, $surname);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt->close();
    }
    $message = "Data saved successfully!";
}
?>