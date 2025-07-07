<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Contact Sunstore Industries";
$success_message = $error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_connect();
        
        // Validate and sanitize inputs
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        $interest = isset($_POST['interest']) ? implode(', ', (array)$_POST['interest']) : '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($message)) {
            throw new Exception("Please fill in all required fields");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO contact_inquiries 
                               (name, email, phone, subject, message, interests, ip_address, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $name, $email, $phone, $subject, $message, $interest, $_SERVER['REMOTE_ADDR']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving your message. Please try again.");
        }
        
        // Send email notification
        $to = "info@sunstoreindustries.com";
        $email_subject = "New Contact Form Submission: $subject";
        $email_body = "You have received a new message from $name ($email).\n\n".
                      "Phone: $phone\n".
                      "Interests: $interest\n\n".
                      "Message:\n$message";
        $headers = "From: $email";
        
        if (!mail($to, $email_subject, $email_body, $headers)) {
            error_log("Failed to send contact form email for $email");
        }
        
        $success_message = "Thank you for contacting us! We'll get back to you within 24 hours.";
        
        // Clear form on success
        $_POST = [];
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="contact-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h1 class="display-5 fw-bold text-primary mb-4">Contact Sunstore Industries</h1>
                <p class="lead mb-4">Have questions about solar solutions? Get in touch with our team.</p>
                
                <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php elseif ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            <div class="invalid-feedback">Please provide your name.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject">
                                <option value="">Select a subject</option>
                                <option value="Residential Solar" <?= selected($_POST['subject'] ?? '', 'Residential Solar') ?>>Residential Solar</option>
                                <option value="Commercial Solar" <?= selected($_POST['subject'] ?? '', 'Commercial Solar') ?>>Commercial Solar</option>
                                <option value="Product Inquiry" <?= selected($_POST['subject'] ?? '', 'Product Inquiry') ?>>Product Inquiry</option>
                                <option value="Support" <?= selected($_POST['subject'] ?? '', 'Support') ?>>Support</option>
                                <option value="Other" <?= selected($_POST['subject'] ?? '', 'Other') ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">I'm interested in (select all that apply)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="solar-panels" 
                                               name="interest[]" value="Solar Panels" <?= checked($_POST['interest'] ?? [], 'Solar Panels') ?>>
                                        <label class="form-check-label" for="solar-panels">Solar Panels</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="inverters" 
                                               name="interest[]" value="Inverters" <?= checked($_POST['interest'] ?? [], 'Inverters') ?>>
                                        <label class="form-check-label" for="inverters">Inverters</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="batteries" 
                                               name="interest[]" value="Batteries" <?= checked($_POST['interest'] ?? [], 'Batteries') ?>>
                                        <label class="form-check-label" for="batteries">Batteries</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="installation" 
                                               name="interest[]" value="Installation" <?= checked($_POST['interest'] ?? [], 'Installation') ?>>
                                        <label class="form-check-label" for="installation">Installation</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="maintenance" 
                                               name="interest[]" value="Maintenance" <?= checked($_POST['interest'] ?? [], 'Maintenance') ?>>
                                        <label class="form-check-label" for="maintenance">Maintenance</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="consultation" 
                                               name="interest[]" value="Consultation" <?= checked($_POST['interest'] ?? [], 'Consultation') ?>>
                                        <label class="form-check-label" for="consultation">Consultation</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="message" class="form-label">Your Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-4">Our Contact Information</h3>
                        
                        <div class="contact-info mb-4">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-map-marker-alt fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold">Headquarters</h5>
                                    <p>Solar Plaza, 3rd Floor<br>Ngong Road, Nairobi, Kenya</p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-phone-alt fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold">Call Us</h5>
                                    <p>
                                        <a href="tel:+254700123456">+254 700 123 456</a><br>
                                        <a href="tel:+254711123456">+254 711 123 456</a>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-envelope fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold">Email Us</h5>
                                    <p>
                                        <a href="mailto:info@sunstoreindustries.com">info@sunstoreindustries.com</a><br>
                                        <a href="mailto:support@sunstoreindustries.com">support@sunstoreindustries.com</a>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold">Working Hours</h5>
                                    <p>
                                        Monday - Friday: 8:00 AM - 5:00 PM<br>
                                        Saturday: 9:00 AM - 2:00 PM<br>
                                        Sunday: Closed
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="map-container mb-4">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.808477395885!2d36.80821541532862!3d-1.292359535980772!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d664d22a2b%3A0x9f7a5a8f1619b0d5!2sSolar%20Plaza!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" 
                                    width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                        
                        <div class="social-links">
                            <h5 class="fw-bold mb-3">Connect With Us</h5>
                            <a href="#" class="btn btn-outline-primary btn-sm me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="btn btn-outline-primary btn-sm me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="btn btn-outline-primary btn-sm me-2"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="btn btn-outline-primary btn-sm me-2"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="btn btn-outline-primary btn-sm"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to your functions.php -->
<?php
// Helper function for select options
function selected($saved_val, $current_val) {
    return $saved_val == $current_val ? 'selected' : '';
}

// Helper function for checkboxes
function checked($saved_vals, $current_val) {
    if (is_array($saved_vals)) {
        return in_array($current_val, $saved_vals) ? 'checked' : '';
    }
    return '';
}
?>

<style>
    .contact-page {
        background-color: #f8f9fa;
    }
    .contact-info i {
        width: 40px;
        text-align: center;
    }
    .form-control, .form-select {
        padding: 10px;
        border-radius: 8px;
    }
    .btn-primary {
        padding: 10px 30px;
        border-radius: 8px;
    }
    .map-container {
        border-radius: 8px;
        overflow: hidden;
    }
    .social-links .btn {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
</style>

<script>
// Client-side form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php
require_once 'includes/footer.php';
?>