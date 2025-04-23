<?php
// USSD Handler
header('Content-type: text/plain');

// Get inputs from USSD gateway
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];

// Connect to database
$conn = new mysqli("localhost", "username", "password", "database_name");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Parse user input
$textArray = explode("*", $text);
$userResponse = trim(end($textArray));

// USSD logic
if ($text == "") {
    // Initial menu
    $response  = "CON Welcome to Your USSD App\n";
    $response .= "1. Input Referral Code\n";
    $response .= "2. Register for Referral\n";
    $response .= "3. Check Account Balance\n";
    $response .= "4. Withdraw to MPESA";
} elseif ($text == "1") {
    // Input Referral Code
    $response = "CON Enter the referral code:";
} elseif (strpos($text, "1*") === 0) {
    // Process referral code
    $referralCode = $userResponse;
    $sql = "SELECT * FROM users WHERE referral_code = '$referralCode'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = "END Referral code accepted. Thank you!";
        // Update referrer balance here if needed
    } else {
        $response = "END Invalid referral code.";
    }
} elseif ($text == "2") {
    // Register for Referral
    // Generate a unique referral code
    $referralCode = substr(md5(uniqid(mt_rand(), true)), 0, 8);

    $sql = "INSERT INTO users (phone_number, referral_code) VALUES ('$phoneNumber', '$referralCode')";
    if ($conn->query($sql) === TRUE) {
        $response = "END Registration successful! Your referral code is $referralCode.";
    } else {
        $response = "END Registration failed. Please try again.";
    }
} elseif ($text == "3") {
    // Check Account Balance
    $sql = "SELECT balance FROM users WHERE phone_number = '$phoneNumber'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $balance = $row["balance"];
        $response = "END Your account balance is KES $balance.";
    } else {
        $response = "END Account not found.";
    }
} elseif ($text == "4") {
    // Withdraw to MPESA
    $response = "CON Enter the amount to withdraw:";
} elseif (strpos($text, "4*") === 0) {
    // Process withdrawal
    $amount = (float)$userResponse;

    $sql = "SELECT balance FROM users WHERE phone_number = '$phoneNumber'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $balance = $row["balance"];

        if ($balance >= $amount) {
            // Deduct balance
            $newBalance = $balance - $amount;
            $sql = "UPDATE users SET balance = $newBalance WHERE phone_number = '$phoneNumber'";
            $conn->query($sql);

            // Integrate MPESA withdrawal here
            $response = "END Withdrawal of KES $amount to MPESA is being processed.";
        } else {
            $response = "END Insufficient balance.";
        }
    } else {
        $response = "END Account not found.";
    }
} else {
    // Invalid input
    $response = "END Invalid option. Please try again.";
}

// Send response to USSD gateway
echo $response;

// Close connection
$conn->close();
?>