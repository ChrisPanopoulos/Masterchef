<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
    <?php if ($role == 'admin'): ?>
        <a href="manage_data.php">Manage Data</a><br>
        <a href="backup.php">Backup Database</a><br>
        <a href="restore.php">Restore Database</a><br>
    <?php elseif ($role == 'cook'): ?>
        <a href="edit_recipes.php">Edit My Recipes</a><br>
        <a href="add_recipe.php">Add New Recipe</a><br>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</body>
</html>

