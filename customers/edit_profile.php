<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../customer_login.php");
    exit();
}

require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Initialize variables
$success = '';
$errors = [];
$active_tab = $_GET['tab'] ?? 'profile'; // Default to profile tab

// Fetch current customer data
try {
    $customer_id = $_SESSION['customer_id'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $error = "Customer not found";
        header("Location: customer_account.php?error=" . urlencode($error));
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    header("Location: customer_account.php?error=" . urlencode($error));
    exit();
}

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Sanitize inputs
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $state = sanitize_input($_POST['state'] ?? '');
    $zip_code = sanitize_input($_POST['zip_code'] ?? '');
    $country = sanitize_input($_POST['country'] ?? '');

    // Validate inputs
    if (empty($first_name)) $errors['profile'][] = "First name is required";
    if (empty($last_name)) $errors['profile'][] = "Last name is required";
    if (!empty($phone) && !preg_match('/^[\d\s\-()+]{10,20}$/', $phone)) {
        $errors['profile'][] = "Invalid phone number format";
    }

    // If no errors, update database
    if (empty($errors['profile'])) {
        try {
            $stmt = $pdo->prepare("UPDATE customers SET
                                  first_name = :first_name,
                                  last_name = :last_name,
                                  phone = :phone,
                                  address = :address,
                                  city = :city,
                                  state = :state,
                                  zip_code = :zip_code,
                                  country = :country
                                  WHERE customer_id = :customer_id");

            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':phone' => $phone,
                ':address' => $address,
                ':city' => $city,
                ':state' => $state,
                ':zip_code' => $zip_code,
                ':country' => $country,
                ':customer_id' => $customer_id
            ]);

            if ($stmt->rowCount() > 0) {
                $success['profile'] = "Profile updated successfully!";
                // Refresh customer data
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id");
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                $active_tab = 'profile';
            } else {
                $errors['profile'][] = "No changes were made to your profile";
            }
        } catch (PDOException $e) {
            $errors['profile'][] = "Database error: " . $e->getMessage();
        }
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($current_password)) {
        $errors['password'][] = "Current password is required";
    }
    if (empty($new_password)) {
        $errors['password'][] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors['password'][] = "New password must be at least 8 characters long";
    }
    if ($new_password !== $confirm_password) {
        $errors['password'][] = "New passwords do not match";
    }

    // If no errors, verify current password and update
    if (empty($errors['password'])) {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM customers WHERE customer_id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $customer_data['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password
                $stmt = $pdo->prepare("UPDATE customers SET password = :password WHERE customer_id = :customer_id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $success['password'] = "Password changed successfully!";
                    $active_tab = 'password';
                } else {
                    $errors['password'][] = "Error updating password";
                }
            } else {
                $errors['password'][] = "Current password is incorrect";
            }
        } catch (PDOException $e) {
            $errors['password'][] = "Database error: " . $e->getMessage();
        }
    }
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - SunStore Industries</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color:#134327;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .account-settings-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .account-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .account-header h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: var(--dark-gray);
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab:hover {
            color: var(--primary-color);
        }

        .tab.active {
            color: var(--secondary-color);
            border-bottom-color: var(--secondary-color);
        }

        .tab-content {
            display: none;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            flex: 1;
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        input[type="text"],
        input[type="tel"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: var(--danger-color);
            background-color: #fadbd8;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--danger-color);
        }

        .success {
            color: var(--success-color);
            background-color: #d5f5e3;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success-color);
        }

        .password-strength {
            height: 5px;
            background-color: var(--light-gray);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .back-link {
            display: inline-block;
            margin-left: 1rem;
            color: var(--dark-gray);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .tabs {
                flex-direction: column;
                border-bottom: none;
            }

            .tab {
                border-bottom: 1px solid var(--border-color);
                border-left: 3px solid transparent;
            }

            .tab.active {
                border-left-color: var(--secondary-color);
                border-bottom-color: var(--border-color);
            }
        }
    </style>
</head>
<body>
    <div class="account-settings-container">
        <div class="account-header">
            <h1>Account Settings</h1>
            <p>Manage your profile information and security settings</p>
        </div>

        <div class="tabs">
            <div class="tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"
                 onclick="switchTab('profile')">Profile Information</div>
            <div class="tab <?php echo $active_tab === 'password' ? 'active' : ''; ?>"
                 onclick="switchTab('password')">Change Password</div>
        </div>

        <!-- Profile Information Tab -->
        <div id="profile-tab" class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
            <?php if (!empty($errors['profile'])): ?>
                <div class="error">
                    <?php foreach ($errors['profile'] as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success['profile'])): ?>
                <div class="success"><?php echo htmlspecialchars($success['profile']); ?></div>
            <?php endif; ?>

            <form method="post" action="?tab=profile">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name*</label>
                        <input type="text" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($customer['phone']); ?>"
                           placeholder="e.g., 123-456-7890">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($yourVariable ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               value="<?php echo htmlspecialchars($variable ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state"
                               value="<?php echo htmlspecialchars($variable ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="zip_code">ZIP/Postal Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                         value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                         placeholder="e.g., 123-456-7890">
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country"
                               value="<?php echo htmlspecialchars($variable ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                    <a href="customer_account.php" class="back-link">Back to Account</a>
                </div>
            </form>
        </div>

        <!-- Change Password Tab -->
        <div id="password-tab" class="tab-content <?php echo $active_tab === 'password' ? 'active' : ''; ?>">
            <?php if (!empty($errors['password'])): ?>
                <div class="error">
                    <?php foreach ($errors['password'] as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success['password'])): ?>
                <div class="success"><?php echo htmlspecialchars($success['password']); ?></div>
            <?php endif; ?>

            <form method="post" action="?tab=password">
                <div class="form-group">
                    <label for="current_password">Current Password*</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password*</label>
                    <input type="password" id="new_password" name="new_password" required
                           oninput="checkPasswordStrength(this.value)">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <small>Password must be at least 8 characters long</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password*</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                    <a href="customer_account.php" class="back-link">Back to Account</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Update URL without reloading
            history.pushState(null, null, `?tab=${tabName}`);

            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(`${tabName}-tab`).classList.add('active');

            // Update active tab styling
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;

            // Length check
            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 20;

            // Complexity checks
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 10;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 10;

            // Update strength bar
            strengthBar.style.width = `${strength}%`;

            // Update color
            if (strength < 40) {
                strengthBar.style.background = 'var(--danger-color)';
            } else if (strength < 70) {
                strengthBar.style.background = 'orange';
            } else {
                strengthBar.style.background = 'var(--success-color)';
            }
        }
    </script>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>
