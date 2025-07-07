<?php
// This file contains all CSS includes and custom styles
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Custom CSS -->
<style>
    :root {
        --primary-green: #4CAF50;
        --primary-green-dark: #388E3C;
        --primary-orange: #FF9800;
        --primary-orange-dark: #F57C00;
    }
    
    /* Auth Pages */
    .auth-page {
        height: 100vh;
        display: flex;
        align-items: center;
        background-color: #f5f5f5;
    }
    
    .auth-container {
        width: 100%;
        max-width: 400px;
        padding: 15px;
        margin: auto;
    }
    
    .auth-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    
    .auth-logo {
        text-align: center;
        margin-bottom: 2rem;
        color: var(--primary-green);
    }
    
    .auth-logo i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .auth-logo h2 {
        font-size: 1.5rem;
        margin: 0;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 1.5rem;
        color: #666;
    }
    
    /* Buttons */
    .btn-primary {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-green-dark);
        border-color: var(--primary-green-dark);
    }
    
    .btn-warning {
        background-color: var(--primary-orange);
        border-color: var(--primary-orange);
        color: white;
    }
    
    .btn-warning:hover {
        background-color: var(--primary-orange-dark);
        border-color: var(--primary-orange-dark);
        color: white;
    }
    
    /* Badges */
    .bg-primary {
        background-color: var(--primary-green) !important;
    }
    
    .bg-warning {
        background-color: var(--primary-orange) !important;
    }
    
    /* Navbar */
    .navbar-dark.bg-dark {
        background-color: var(--primary-green) !important;
    }
    
    /* Sidebar */
    .sidebar .nav-link.active {
        color: var(--primary-green);
    }
    
    .sidebar .nav-link:hover {
        color: var(--primary-green);
    }
    
    /* Cards */
    .card-header {
        background-color: rgba(76, 175, 80, 0.1);
        border-bottom: 1px solid rgba(76, 175, 80, 0.2);
    }
    
    /* Tables */
    .table thead th {
        background-color: rgba(76, 175, 80, 0.1);
    }
    
    /* Alerts */
    .alert-primary {
        background-color: rgba(76, 175, 80, 0.1);
        border-color: rgba(76, 175, 80, 0.2);
        color: var(--primary-green-dark);
    }
    
    /* Form Controls */
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 152, 0, 0.25);
    }
    
    /* Custom utility classes */
    .bg-green-light {
        background-color: rgba(76, 175, 80, 0.1);
    }
    
    .text-green {
        color: var(--primary-green);
    }
    
    .text-orange {
        color: var(--primary-orange);
    }
</style>