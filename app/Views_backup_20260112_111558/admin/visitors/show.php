<?php /** Visitor Details */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Details - Sci-Bono Clubhouse</title>
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
            <div class="content-header" style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
                <div><a href="/visitors" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Visitors</a><h1 class="content-title">Visitor Details</h1></div>
                <div style="display: flex; gap: 1rem;">
                    <a href="/visitors/<?php echo $visitor['id']; ?>/edit" class="btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                    <?php if ($user['user_type'] === 'admin'): ?>
                    <button onclick="deleteVisitor(<?php echo $visitor['id']; ?>)" class="btn-danger" style="background: #dc3545;"><i class="fas fa-trash"></i> Delete</button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 2rem;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-bottom: 2rem;">
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Full Name</div><div style="font-size: 1.25rem; font-weight: 600;"><?php echo htmlspecialchars($visitor['name'] . ' ' . $visitor['surname']); ?></div></div>
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Email</div><div style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($visitor['email']); ?></div></div>
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Phone</div><div style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($visitor['phone']); ?></div></div>
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Purpose</div><div style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($visitor['purpose']); ?></div></div>
                    <?php if (!empty($visitor['company'])): ?>
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Company</div><div style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($visitor['company']); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($visitor['id_number'])): ?>
                    <div><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">ID Number</div><div style="font-size: 1rem; font-weight: 500;"><?php echo htmlspecialchars($visitor['id_number']); ?></div></div>
                    <?php endif; ?>
                </div>

                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 8px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                    <div style="text-align: center;"><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Visit Date</div><div style="font-size: 1rem; font-weight: 600;"><?php echo date('M d, Y', strtotime($visitor['visit_date'])); ?></div></div>
                    <div style="text-align: center;"><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Check In</div><div style="font-size: 1rem; font-weight: 600; color: #28a745;"><?php echo $visitor['check_in_time'] ? date('H:i', strtotime($visitor['check_in_time'])) : '-'; ?></div></div>
                    <div style="text-align: center;"><div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Check Out</div><div style="font-size: 1rem; font-weight: 600; color: #dc3545;"><?php echo $visitor['check_out_time'] ? date('H:i', strtotime($visitor['check_out_time'])) : '-'; ?></div></div>
                </div>
            </div>
        </main>
    </div>
    <script>function deleteVisitor(id){if(!confirm('Delete this visitor?'))return;fetch(`/visitors/${id}`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token=<?php echo $_SESSION['csrf_token']??''; ?>&_method=DELETE'}).then(r=>r.json()).then(data=>{if(data.success){alert('Deleted');location.href='/visitors';}else{alert('Error: '+data.message);}});}</script>
</body>
</html>
