<?php
// Start the session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the functions file
require_once __DIR__ . '/functions.php';

// Debug session state
error_log("Session state in header.php: " . print_r($_SESSION, true));
error_log("Current page: " . (isset($current_page) ? $current_page : 'not set'));

// Prevent any whitespace or output before this point
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Shan-e-Madina Petroleum Services</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    
    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        // Global function to initialize dropdowns
        function initializeDropdowns() {
            console.log('Initializing dropdowns...');
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            console.log('Found dropdowns:', dropdownElementList.length);
            
            dropdownElementList.forEach(function(dropdownToggle) {
                try {
                    // Remove existing dropdown instance if any
                    const existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (existingDropdown) {
                        existingDropdown.dispose();
                    }
                    
                    // Create new dropdown instance
                    const dropdown = new bootstrap.Dropdown(dropdownToggle, {
                        offset: [0, 10],
                        popperConfig: function (defaultBsPopperConfig) {
                            return {
                                ...defaultBsPopperConfig,
                                strategy: 'fixed'
                            }
                        }
                    });
                    console.log('Initialized dropdown for:', dropdownToggle);
                    
                    // Add click event listener
                    dropdownToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdown.toggle();
                    });
                } catch (error) {
                    console.error('Error initializing dropdown:', error);
                }
            });
        }

        // Input validation functions
        function validateTextInput(input) {
            // Remove any non-alphabetic characters (including spaces)
            input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
            
            // Show/hide error message
            const errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                if (!/^[a-zA-Z\s]+$/.test(input.value)) {
                    input.classList.add('is-invalid');
                    errorDiv.textContent = 'Please enter only letters and spaces';
                } else {
                    input.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            }
        }

        function validateNumericInput(input) {
            // Remove any non-numeric characters
            input.value = input.value.replace(/[^0-9.]/g, '');
            
            // Ensure only one decimal point
            const parts = input.value.split('.');
            if (parts.length > 2) {
                input.value = parts[0] + '.' + parts.slice(1).join('');
            }

            // Show/hide error message
            const errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                if (!/^\d*\.?\d*$/.test(input.value)) {
                    input.classList.add('is-invalid');
                    errorDiv.textContent = 'Please enter only numbers';
                } else {
                    input.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            }
        }

        // Initialize input validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dropdowns
            initializeDropdowns();
            
            // Add validation to all text inputs
            document.querySelectorAll('input[type="text"]').forEach(input => {
                if (input.name.toLowerCase().includes('name') || 
                    input.name.toLowerCase().includes('category') || 
                    input.name.toLowerCase().includes('unit')) {
                    // Add error message div if it doesn't exist
                    if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        input.parentNode.insertBefore(errorDiv, input.nextSibling);
                    }
                    input.addEventListener('input', function() {
                        validateTextInput(this);
                    });
                }
            });

            // Add validation to all numeric inputs
            document.querySelectorAll('input[type="number"]').forEach(input => {
                // Add error message div if it doesn't exist
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    input.parentNode.insertBefore(errorDiv, input.nextSibling);
                }
                input.addEventListener('input', function() {
                    validateNumericInput(this);
                });
            });

            // Add validation to all price and quantity inputs
            document.querySelectorAll('input[name*="price"], input[name*="quantity"], input[name*="amount"]').forEach(input => {
                // Add error message div if it doesn't exist
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    input.parentNode.insertBefore(errorDiv, input.nextSibling);
                }
                input.addEventListener('input', function() {
                    validateNumericInput(this);
                });
            });
        });

        // Form submission validation
        function validateForm(form) {
            let isValid = true;
            
            // Validate text inputs
            form.querySelectorAll('input[type="text"]').forEach(input => {
                if (input.name.toLowerCase().includes('name') || 
                    input.name.toLowerCase().includes('category') || 
                    input.name.toLowerCase().includes('unit')) {
                    if (!/^[a-zA-Z\s]+$/.test(input.value)) {
                        input.classList.add('is-invalid');
                        const errorDiv = input.nextElementSibling;
                        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv.textContent = 'Please enter only letters and spaces';
                        }
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                        const errorDiv = input.nextElementSibling;
                        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv.textContent = '';
                        }
                    }
                }
            });

            // Validate numeric inputs
            form.querySelectorAll('input[type="number"], input[name*="price"], input[name*="quantity"], input[name*="amount"]').forEach(input => {
                if (!/^\d*\.?\d*$/.test(input.value)) {
                    input.classList.add('is-invalid');
                    const errorDiv = input.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.textContent = 'Please enter only numbers';
                    }
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    const errorDiv = input.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.textContent = '';
                    }
                }
            });

            return isValid;
        }

        // Add form validation to all forms
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Initialize on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            initializeDropdowns();
            
            // Loading Bar Animation
            const loadingBar = document.getElementById('loadingBar');
            if (loadingBar) {
                loadingBar.style.width = '100%';
                setTimeout(() => {
                    loadingBar.style.opacity = '0';
                }, 1000);
            }
        });

        // Re-initialize on any dynamic content changes
        document.addEventListener('show.bs.dropdown', function (e) {
            console.log('Dropdown show event triggered');
        });

        // Handle any turbolinks or pjax events if present
        window.addEventListener('load', initializeDropdowns);
        document.addEventListener('turbolinks:load', initializeDropdowns);
        document.addEventListener('pjax:complete', initializeDropdowns);

        $(document).ready(function() {
            // Initialize all dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl, {
                    offset: [0, 10],
                    popperConfig: function (defaultBsPopperConfig) {
                        return {
                            ...defaultBsPopperConfig,
                            strategy: 'fixed'
                        }
                    }
                });
            });

            // Add hover effect for desktop
            if (window.matchMedia('(min-width: 992px)').matches) {
                $('.dropdown').hover(
                    function() {
                        const dropdown = bootstrap.Dropdown.getInstance($(this).find('.dropdown-toggle')[0]);
                        if (dropdown) {
                            dropdown.show();
                        }
                    },
                    function() {
                        const dropdown = bootstrap.Dropdown.getInstance($(this).find('.dropdown-toggle')[0]);
                        if (dropdown) {
                            setTimeout(() => {
                                if (!$(this).is(':hover')) {
                                    dropdown.hide();
                                }
                            }, 150);
                        }
                    }
                );
            }

            // Show loading bar when form is submitted
            $('form').on('submit', function() {
                $('#loadingBar').css('width', '90%');
            });

            // Hide loading bar when page is fully loaded
            $(window).on('load', function() {
                $('#loadingBar').css('width', '100%');
                setTimeout(function() {
                    $('#loadingBar').css('width', '0%');
                }, 400);
            });
            
            // Add date range to header for printing
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            $('.card-header').attr('data-date-range', startDate + ' to ' + endDate);

            // Debug dropdown initialization
            console.log('Dropdowns initialized:', dropdownList.length);
            
            // Ensure dropdowns work on mobile
            $('.dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = bootstrap.Dropdown.getInstance(this);
                if (dropdown) {
                    dropdown.toggle();
                }
            });
        });
    </script>
    
    <!-- Custom Styles for Header -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            --secondary-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --card-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            --hover-transform: translateY(-4px);
        }

        body {
            padding-top: 64px;
            background: url('Images/pso.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            color: #1e293b;
        }

        main {
            flex: 1 0 auto;
            padding: 2rem 0;
            position: relative;
        }

        main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            z-index: -1;
        }

        .container {
            max-width: 1800px;
            width: 100%;
            padding: 0 2rem;
        }

        /* Dashboard Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 0 -2rem 3rem -2rem;
            padding: 0 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.7);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card .stat-title i {
            font-size: 1rem;
            color: #3b82f6;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }

        .stat-card .stat-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card .stat-subtitle i {
            color: #10b981;
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 1rem;
            border-radius: 50px;
            font-size: 0.8125rem;
            font-weight: 500;
            gap: 0.375rem;
        }

        .status-good {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .status-operational {
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
        }

        /* Recent Sales Table */
        .recent-sales {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin: 2rem -4rem;
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
        }

        .recent-sales .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.75rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .recent-sales .card-header h5 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-responsive {
            margin: 0;
            padding: 0 1rem;
            overflow-x: auto;
        }

        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 1000px;
        }

        .table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.75px;
            padding: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .table td {
            padding: 1.5rem;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            font-size: 1rem;
        }

        .table th:first-child,
        .table td:first-child {
            padding-left: 2.5rem;
        }

        .table th:last-child,
        .table td:last-child {
            padding-right: 2.5rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            cursor: pointer;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .payment-badge {
            padding: 0.375rem 1.25rem;
            border-radius: 50px;
            font-size: 0.8125rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            min-width: 100px;
            justify-content: center;
        }

        .payment-cash {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .payment-card {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
        }

        /* Loading Bar */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: var(--secondary-gradient);
            transition: width 0.4s ease, opacity 0.3s ease;
            z-index: 9999;
        }

        /* Animations */
        .page-transition {
            animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Responsive Adjustments */
        @media (max-width: 1600px) {
            .container {
                max-width: 1400px;
            }
            
            .recent-sales {
                margin: 2rem -2rem;
            }
        }

        @media (max-width: 991.98px) {
            .container {
                padding: 0 1rem;
            }

            .stats-container {
                margin: 0 -1rem 2rem -1rem;
                padding: 0 1rem;
                gap: 1.25rem;
            }

            .recent-sales {
                margin: 2rem -1rem;
                border-radius: 12px;
            }

            .recent-sales .card-header {
                padding: 1.25rem 1.5rem;
            }

            .table td, 
            .table th {
                padding: 1.25rem;
            }

            .table th:first-child,
            .table td:first-child {
                padding-left: 1.5rem;
            }

            .table th:last-child,
            .table td:last-child {
                padding-right: 1.5rem;
            }
        }

        .app-footer {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: rgba(255, 255, 255, 0.9);
            padding: 2rem 0;
            margin-top: auto;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-brand {
            flex: 1;
            min-width: 280px;
        }

        .footer-brand img {
            height: 50px;
            margin-bottom: 1rem;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .footer-section {
            min-width: 160px;
        }

        .footer-section h5 {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-section h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: #64b5f6;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .footer-section ul a:hover {
            color: white;
            transform: translateX(3px);
        }

        .footer-section ul a i {
            margin-right: 0.5rem;
            font-size: 0.85rem;
        }

        .footer-bottom {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .social-links a:hover {
            color: white;
        }

        @media (max-width: 991.98px) {
            body {
                padding-top: 72px;
            }

            .footer-content {
                flex-direction: column;
                gap: 2rem;
            }

            .footer-brand {
                text-align: center;
                min-width: 100%;
            }

            .footer-links {
                justify-content: center;
                gap: 2rem;
            }

            .footer-section h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-section {
                text-align: center;
            }
        }

        .app-header {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .navbar {
            padding: 0;
            height: 64px;
        }

        .navbar > .container {
            height: 100%;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            padding: 0;
            margin-right: 2rem;
            height: 100%;
        }
        
        .navbar-brand img {
            height: 42px;
            margin-right: 1rem;
        }
        
        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
        }
        
        .navbar-nav {
            height: 100%;
            align-items: center;
            gap: 0.25rem;
        }
        
        .nav-item {
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .nav-link {
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0 1.25rem;
            color: rgba(255, 255, 255, 0.85) !important;
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
            opacity: 0.9;
            transition: transform 0.3s ease;
        }
        
                .nav-link:hover {            color: white !important;            background: rgba(16, 185, 129, 0.2);  /* Changed to a green tint */            transform: translateY(-2px);        }                .nav-link:hover i {            transform: scale(1.1);            color: #10b981;  /* Changed to match hover */        }                .nav-link.active {            color: white !important;            background: rgba(16, 185, 129, 0.25);  /* Slightly darker green for active */        }        .nav-link::before {            content: '';            position: absolute;            bottom: 0;            left: 50%;            width: 0;            height: 3px;            background: #10b981;  /* Changed to green */            transition: all 0.3s ease;            transform: translateX(-50%);        }        .nav-link:hover::before {            width: 100%;        }        .nav-link.active::before {            content: '';            position: absolute;            bottom: 0;            left: 0;            width: 100%;            height: 3px;            background: #10b981;  /* Changed to green */            transform: none;        }
        
        .user-profile {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            margin-left: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .user-profile::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%) scale(0);
            border-radius: 50%;
            transition: transform 0.5s ease;
        }
        
        .user-profile:hover::before {
            transform: translate(-50%, -50%) scale(2);
        }
        
                .user-profile:hover {            background: rgba(16, 185, 129, 0.2);  /* Changed to match nav links */            transform: translateY(-2px);        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 0.8rem;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .user-profile:hover .user-avatar {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar i {
            color: white;
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        .user-profile:hover .user-avatar i {
            transform: scale(1.1);
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            z-index: 1000;
            margin-top: 0.5rem !important;
            padding: 0.5rem 0;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: white;
            min-width: 200px;
            animation: dropdownFade 0.2s ease;
            transform-origin: top right;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            cursor: pointer;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            color: #37474f;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
            color: #546e7a;
            transition: all 0.2s ease;
        }
        
                .dropdown-item:hover {            background: rgba(16, 185, 129, 0.1);  /* Light green background */            color: #10b981;  /* Green text */            transform: translateX(5px);        }                .dropdown-item:hover i {            color: #10b981;  /* Green icon */        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            border-top-color: #e0e0e0;
        }

        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .user-profile.show {
            background: rgba(255, 255, 255, 0.15);
        }
        
        @media (max-width: 991.98px) {
            body {
                padding-top: 72px; /* Adjusted for mobile header height */
            }

            .navbar {
                height: auto;
                padding: 0.75rem 0;
            }

            .nav-item {
                height: auto;
            }

            .nav-link {
                padding: 0.75rem 1rem;
                height: auto;
            }

            .nav-link.active::before {
                display: none;
            }

            .user-profile {
                margin: 1rem 0 0 0;
            }
        }
        
        .nav-tabs .nav-link {
            color: #1a237e !important;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white !important;
        }

        /* Ensure dropdowns are visible */
        .dropdown-menu {
            display: none;
            position: absolute;
            z-index: 1000;
            margin-top: 0.5rem !important;
            padding: 0.5rem 0;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: white;
            min-width: 200px;
            animation: dropdownFade 0.2s ease;
            transform-origin: top right;
        }

        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Add pointer cursor to dropdown toggle */
        .dropdown-toggle {
            cursor: pointer;
        }

        /* Ensure dropdown items are clickable */
        .dropdown-item {
            cursor: pointer;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            color: #37474f;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        /* Ensure dropdowns are visible */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            z-index: 1050 !important;
            display: none;
            float: left;
            min-width: 200px;
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: dropdownFade 0.2s ease;
            transform-origin: top right;
        }

        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .user-profile {
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover,
        .user-profile:focus,
        .user-profile.show {
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            box-shadow: none;
        }

        .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0.5em;
            vertical-align: middle;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }

        /* Add validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff8f8 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="loading-bar" id="loadingBar"></div>
    
    <header class="app-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <img src="Images/PakistanStateOilLogo.png" alt="PSO Logo">
                    <span>Shan-e-Madina Petroleum</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'purchases' ? 'active' : ''; ?>" href="purchases.php">
                                    <i class="fas fa-shopping-bag"></i> Purchases
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'sales' ? 'active' : ''; ?>" href="sales.php">
                                    <i class="fas fa-shopping-cart"></i> Sales
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'inventory' ? 'active' : ''; ?>" href="inventory.php">
                                    <i class="fas fa-boxes"></i> Inventory
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'readings' ? 'active' : ''; ?>" href="daily_reading.php">
                                    <i class="fas fa-tachometer-alt"></i> Daily Readings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'employees' ? 'active' : ''; ?>" href="employees.php">
                                    <i class="fas fa-users"></i> Employees
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>" href="customers.php">
                                    <i class="fas fa-user-friends"></i> Customers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'suppliers' ? 'active' : ''; ?>" href="suppliers.php">
                                    <i class="fas fa-truck"></i> Suppliers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'expenses' ? 'active' : ''; ?>" href="expenses.php">
                                    <i class="fas fa-money-bill-wave"></i> Expenses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="reports.php">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if(isset($_SESSION['username'])): ?>
                        <div class="d-flex align-items-center">
                            <div class="nav-item dropdown">
                                <a class="user-profile dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="logout.php">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="page-transition">
        <div class="container">
            <?php display_alert(); // Display any alerts ?>
            <div class="content-wrapper">
                <!-- Content will be injected here by individual pages -->
            </div>
        </div>
    </main>

    </body></html> 