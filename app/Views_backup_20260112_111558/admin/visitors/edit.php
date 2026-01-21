<?php /** Edit Visitor */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Edit Visitor - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
</head>
<body>
    <button id="mobile-nav-toggle" class="mobile-nav-toggle"><i class="fas fa-bars"></i></button>
    <div class="container">
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header"><div class="sidebar-logo"><img src="/public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo"></div><button id="sidebar-toggle" class="toggle-sidebar"><i class="fas fa-chevron-right"></i></button></div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="/dashboard" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-home"></i></div><span class="sidebar-text">Home</span></a></li>
                <li class="sidebar-item"><a href="/visitors" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-user-check"></i></div><span class="sidebar-text">Visitors</span></a></li>
            </ul>
            <div class="sidebar-footer"><a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a></div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="content-header" style="margin-bottom: 2rem;">
                <a href="/visitors/<?php echo $visitor['id']; ?>" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Visitor</a>
                <h1 class="content-title">Edit Visitor</h1>
            </div>

            <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 2rem;">
                <form id="edit-visitor-form" method="post" action="/visitors/<?php echo $visitor['id']; ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="_method" value="PUT">

                    <div class="form-section">
                        <div class="form-section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">First Name *</label>
                                    <input type="text" id="name" name="name" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="surname" class="form-label">Surname *</label>
                                    <input type="text" id="surname" name="surname" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['surname']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" id="phone" name="phone" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['phone']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="purpose" class="form-label">Purpose *</label>
                                    <select id="purpose" name="purpose" class="form-control form-select" required>
                                        <option value="Meeting" <?php echo $visitor['purpose']=='Meeting'?'selected':''; ?>>Meeting</option>
                                        <option value="Workshop" <?php echo $visitor['purpose']=='Workshop'?'selected':''; ?>>Workshop</option>
                                        <option value="Tour" <?php echo $visitor['purpose']=='Tour'?'selected':''; ?>>Tour</option>
                                        <option value="Event" <?php echo $visitor['purpose']=='Event'?'selected':''; ?>>Event</option>
                                        <option value="Other" <?php echo $visitor['purpose']=='Other'?'selected':''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="company" class="form-label">Company</label>
                                    <input type="text" id="company" name="company" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['company'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id-number" class="form-label">ID Number</label>
                                <input type="text" id="id-number" name="id_number" class="form-control input-control" value="<?php echo htmlspecialchars($visitor['id_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Visitor</button>
                        <a href="/visitors/<?php echo $visitor['id']; ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('edit-visitor-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/visitors/<?php echo $visitor['id']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Visitor updated successfully!');
                    window.location.href = '/visitors/<?php echo $visitor['id']; ?>';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the visitor');
            });
        });
    </script>
</body>
</html>
