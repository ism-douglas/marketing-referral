<?php
// USSD Handler
header('Content-type: text/plain');

// Get inputs from USSD gateway
$sesssionid = $_POST['sessionid']; // Get the session unique id
$serviceCode = $_POST['serviceCode']; // Get the service code from provider
$phoneNumber = ltrim($_POST['phoneNumber']); // Get the phone number
$text = $_POST['text']; // Default text variable is usually empty

// Database connection using PDO
try {
    $dsn = "mysql:host=localhost;dbname=database_name;charset=utf8";
    $pdo = new PDO($dsn, "username", "password", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Parse user input
$textArray = explode("*", $text);
$userResponse = trim(end($textArray));

// USSD logic
if ($text == "") {
    // Initial menu
    $response  = "CON Welcome to our USSD App\n";
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

    // Validate referral code using prepared statements
    $stmt = $pdo->prepare("SELECT phone_number FROM users WHERE referral_code = ?");
    $stmt->execute([$referralCode]);
    $result = $stmt->fetch();

    if ($result) {
        $response = "END Referral code accepted. Thank you!";
        // Update referrer balance here if needed
    } else {
        $response = "END Invalid referral code.";
    }
} elseif ($text == "2") {
    // Register for Referral

    // Check if user is already registered
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // User is already registered
        $response = "END You are already registered.";
    } else {
        // Register new user
        $stmt = $pdo->prepare("INSERT INTO users (phone_number) VALUES (?)");
        if ($stmt->execute([$phoneNumber])) {
            // Retrieve the last inserted ID
            $lastInsertedId = $pdo->lastInsertId();

            // Generate a unique referral code using the last inserted ID
            $referralCode = "A" . str_pad($lastInsertedId, 3, "0", STR_PAD_LEFT); // Example: A001

            // Update the user record with the generated referral code
            $updateStmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
            $updateStmt->execute([$referralCode, $lastInsertedId]);

            // Send success response
            $response = "END Registration successful! Your referral code is $referralCode.";
        } else {
            $response = "END Registration failed. Please try again.";
        }
    }
} elseif ($text == "3") {
    // Check Account Balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    $result = $stmt->fetch();

    if ($result) {
        $balance = $result["balance"];
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

    // Check current balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    $result = $stmt->fetch();

    if ($result) {
        $balance = $result["balance"];

        if ($balance >= $amount) {
            // Deduct balance
            $newBalance = $balance - $amount;
            $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE phone_number = ?");
            $stmt->execute([$newBalance, $phoneNumber]);

            // Integrate MPESA withdrawal logic here
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
?>