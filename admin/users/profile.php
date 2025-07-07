<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Get current admin user details
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $_SESSION['admin_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email is already taken by another user';
    }
    $stmt->close();
    
    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }
    
    if (empty($errors)) {
        // Update profile
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $hashed_password, $_SESSION['admin_id']);
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $full_name, $email, $_SESSION['admin_id']);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Profile updated successfully';
            
            // Update session data
            $_SESSION['admin_name'] = $full_name;
            $_SESSION['admin_email'] = $email;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Error updating profile: ' . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            Profile Information
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" value="<?= ucfirst($user['role']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Last Login</label>
                                    <input type="text" class="form-control" value="<?= $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never' ?>" readonly>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Change Password
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>