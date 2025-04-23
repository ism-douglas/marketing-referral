<?php
// USSD Handler
header('Content-type: text/plain');

// Get inputs from USSD gateway
$sesssionid = $_POST['sessionid'];//Get the session unique id
$serviceCode = $_POST['serviceCode'];//Get the service code from provider
$phoneNumber = ltrim($_POST['phoneNumber']);//Get the phone number
$text = $_POST['text'];//Default text variable is usually empty


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
    // Insert user into the database without a referral code first
    $stmt = $pdo->prepare("INSERT INTO users (phone_number) VALUES (?)");
    if ($stmt->execute([$phoneNumber])) {
        // Retrieve the last inserted ID
        $lastInsertedId = $pdo->lastInsertId();

        // Generate a unique referral code using the last inserted ID
        $referralCode = "A" . str_pad($lastInsertedId, 5, "0", STR_PAD_LEFT); // Example: REF00001

        // Update the user record with the generated referral code
        $updateStmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
        $updateStmt->execute([$referralCode, $lastInsertedId]);

        // Send success response
        $response = "END Registration successful! Your referral code is $referralCode.";
    } else {
        $response = "END Registration failed. Please try again.";
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

//Database configuration file
try{

$dsn = 'mysql:host=localhost;dbname=marketing_referral';
$username = 'root';
$password = '';

$conn = new PDO($dsn, $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

// Connect to database
// $conn = new mysqli("localhost", "username", "password", "database_name");
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

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
    $sql = "SELECT id FROM users WHERE referral_code = '$referralCode'";
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