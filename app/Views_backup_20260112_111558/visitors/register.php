<?php
/**
 * Public Visitor Registration
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Visitor Registration - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .container { background: white; max-width: 600px; width: 100%; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: #1c1e21; color: white; padding: 2rem; text-align: center; }
        .header h1 { font-size: 1.75rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .form-content { padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1c1e21; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #e4e6eb; border-radius: 6px; font-size: 0.95rem; transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: #667eea; }
        .required { color: #dc3545; }
        .btn-primary { width: 100%; padding: 1rem; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: #5568d3; }
        .error-message { color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono" style="max-width: 150px; margin-bottom: 1rem;">
            <h1>Welcome to Sci-Bono Clubhouse</h1>
            <p>Please register your visit</p>
        </div>

        <div class="form-content">
            <form id="visitor-registration-form" method="post" action="/visitor/register">
                <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div class="form-group">
                    <label for="name" class="form-label">First Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="surname" class="form-label">Surname <span class="required">*</span></label>
                    <input type="text" id="surname" name="surname" class="form-control" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+27 123 456 7890" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="id-number" class="form-label">ID Number</label>
                    <input type="text" id="id-number" name="id_number" class="form-control" placeholder="Optional">
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="company" class="form-label">Company/Organization</label>
                    <input type="text" id="company" name="company" class="form-control" placeholder="Optional">
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="purpose" class="form-label">Purpose of Visit <span class="required">*</span></label>
                    <select id="purpose" name="purpose" class="form-control" required>
                        <option value="">Select purpose...</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Tour">Facility Tour</option>
                        <option value="Event">Event/Program</option>
                        <option value="Delivery">Delivery/Collection</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="error-message"></div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Complete Registration
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('visitor-registration-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/visitor/register', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/visitor/success';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during registration');
            });
        });
    </script>
</body>
</html>
