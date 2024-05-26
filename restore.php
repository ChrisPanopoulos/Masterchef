<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $restoreFile = $_FILES['restore_file']['tmp_name'];
    $command = "mysql --user=root --password= --host=localhost masterchef < $restoreFile";

    system($command, $output);

    if ($output == 0) {
        echo "Restore successful!";
    } else {
        echo "Restore failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Restore Database</title>
</head>
<body>
    <h1>Restore Database</h1>
    <form action="restore.php" method="post" enctype="multipart/form-data">
        <input type="file" name="restore_file" required>
        <button type="submit">Restore</button>
    </form>
</body>
</html>

