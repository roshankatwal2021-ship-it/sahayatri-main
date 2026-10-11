<?php
// system/setup.php
// Automatic Database Setup & Schema Installer Utility

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

if (isset($_POST['install_db'])) {
    try {
        $pdo = getDBConnection();
        $schemaFile = __DIR__ . '/schema.sql';
        
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            $message = "Database 'sahayatri_db' installed and seeded successfully!";
        } else {
            $error = "schema.sql file not found!";
        }
    } catch (Exception $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SahaYatri System Installer</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .setup-card { background: white; padding: 40px; border-radius: 20px; box-shadow: var(--shadow-lg); max-width: 500px; width: 90%; text-align: center; }
    </style>
</head>
<body>
    <div class="setup-card">
        <a href="index.php" style="display: inline-block; margin-bottom: 16px;">
            <img src="assets/img/Logoo.png" alt="SahaYatri Logo" style="height: 70px;">
        </a>
        <h2>SahaYatri Database Setup</h2>
        <p style="color: #64748B; margin: 12px 0 24px 0;">Click below to automatically create and seed the MySQL database on your XAMPP server.</p>

        <?php if ($message): ?>
            <div style="background: #DCFCE7; color: #15803D; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                <?php echo $message; ?>
            </div>
            <a href="index.php" class="btn btn-primary" style="width: 100%;">Go to SahaYatri Portal</a>
        <?php elseif ($error): ?>
            <div style="background: #FEE2E2; color: #B91C1C; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <form method="POST">
                <button type="submit" name="install_db" class="btn btn-primary" style="width: 100%; padding: 14px;">Install & Seed Database</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
