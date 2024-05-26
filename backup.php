<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$backupFile = 'backup_' . date("Y-m-d-H-i-s") . '.sql';
$command = "mysqldump --user=root --password= --host=localhost cooking_competition > $backupFile";

system($command, $output);

if ($output == 0) {
    echo "Backup successful! File: $backupFile";
} else {
    echo "Backup failed!";
}
?>

