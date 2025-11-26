<?php require_once __DIR__ . '/../../core/CSRF.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo CSRF::metaTag(); ?>
    <title>Visitors Management System</title>
    <link rel="stylesheet" href="../../public/assets/css/visitors.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1>Visitors Management System</h1>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Notification Section -->
        <div id="notification" class="notification"></div>

        <!-- Visitor Registration and Sign In/Out Card -->
        <div class="card">
            <div class="tabs">
                <div class="tab active" data-tab="register">Register</div>
                <div class="tab" data-tab="signin">Sign In</div>
                <div class="tab" data-tab="signout">Sign Out</div>
            </div>

            <!-- Registration Form -->
            <div id="register" class="tab-content active">
                <h2>New Visitor Registration</h2>
                <form id="registration-form">
                    <?php echo CSRF::field(); ?>
                    <div class="form-row">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="name">Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" required>
                                <span class="error-message" id="name-error">Please enter your name</span>
                            </div>
                            <div class="form-group">
                                <label for="surname">Surname <span class="required">*</span></label>
                                <input type="text" id="surname" name="surname" required>
                                <span class="error-message" id="surname-error">Please enter your surname</span>
                            </div>
                            <div class="form-group">
                                <label for="age">Age <span class="required">*</span></label>
                                <input type="number" id="age" name="age" min="5" max="100" required>
                                <span class="error-message" id="age-error">Please enter a valid age (5-100)</span>
                            </div>
                            <div class="form-group">
                                <label for="grade-school">Grade School <span class="required">*</span></label>
                                <input type="text" id="grade-school" name="grade_school" required>
                                <span class="error-message" id="grade-school-error">Please enter your grade school</span>
                            </div>
                            <div class="form-group">
                                <label for="student-number">Student Number (Optional)</label>
                                <input type="text" id="student-number" name="student_number">
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="form-group">
                                <label for="parent-name">Parent Name <span class="required">*</span></label>
                                <input type="text" id="parent-name" name="parent_name" required>
                                <span class="error-message" id="parent-name-error">Please enter parent name</span>
                            </div>
                            <div class="form-group">
                                <label for="parent-surname">Parent Surname <span class="required">*</span></label>
                                <input type="text" id="parent-surname" name="parent_surname" required>
                                <span class="error-message" id="parent-surname-error">Please enter parent surname</span>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required>
                                <span class="error-message" id="email-error">Please enter a valid email address</span>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" required>
                                <span class="error-message" id="phone-error">Please enter a valid phone number</span>
                                <span class="help-text">Format: XXX-XXX-XXXX or XXXXXXXXXX</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn" id="register-btn">
                            <span class="spinner"></span>
                            Register
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sign In Form -->
            <div id="signin" class="tab-content">
                <h2>Visitor Sign In</h2>
                <form id="signin-form">
                    <?php echo CSRF::field(); ?>
                    <div class="form-group">
                        <label for="signin-email">Email Address <span class="required">*</span></label>
                        <input type="email" id="signin-email" name="email" required>
                        <span class="error-message" id="signin-email-error">Please enter a valid email address</span>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn" id="signin-btn">
                            <span class="spinner"></span>
                            Sign In
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sign Out Form -->
            <div id="signout" class="tab-content">
                <h2>Visitor Sign Out</h2>
                <form id="signout-form">
                    <?php echo CSRF::field(); ?>
                    <div class="form-group">
                        <label for="signout-email">Email Address <span class="required">*</span></label>
                        <input type="email" id="signout-email" name="email" required>
                        <span class="error-message" id="signout-email-error">Please enter a valid email address</span>
                    </div>
                    <div class="form-group">
                        <label for="comment">Comments (Optional)</label>
                        <textarea id="comment" name="comment" placeholder="Please share any comments about your visit"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn" id="signout-btn">
                            <span class="spinner"></span>
                            Sign Out
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Visitors List Card -->
        <div class="card">
            <h2>Recent Visitors</h2>
            <div class="search-filter">
                <input type="text" id="search-input" placeholder="Search visitors...">
                <select id="filter-status" class="filter-dropdown">
                    <option value="all">All Visits</option>
                    <option value="active">Active Visits</option>
                    <option value="completed">Completed Visits</option>
                </select>
            </div>
            <div class="table-container">
                <table id="visitors-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Sign-In Time</th>
                            <th>Sign-Out Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="visitors-list">
                        <!-- Visitor records will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div id="pagination" class="pagination">
                <!-- Pagination will be generated dynamically -->
            </div>
        </div>
    </div>

    <script src="../../public/assets/js/visitors-script.js"></script>
</body>
</html>