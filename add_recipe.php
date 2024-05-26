<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cook') {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "masterchef";
$user_id = $_SESSION['user_id'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipe_name = $_POST['recipe_name'];
    $description = $_POST['description'];
    $difficulty = $_POST['difficulty'];
    $prep_time = $_POST['prep_time'];
    $cook_time = $_POST['cook_time'];
    $cuisine_id = $_POST['cuisine_id']; 
    $basicIngredient_id = $_POST['basicIngredient_id'];
    // Add other fields as necessary

    $stmt = $conn->prepare("INSERT INTO recipe (name, description, difficulty, prep_time, cook_time, cuisine_id, basicIngredient_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiiiii", $recipe_name, $description, $difficulty, $prep_time, $cook_time, $cuisine_id, $basicIngredient_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Recipe added successfully!";
    } else {
        echo "Failed to add recipe.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Recipe</title>
</head>
<body>
    <h1>Add New Recipe</h1>
    <form method="post" action="add_recipe.php">
        <label for="recipe_name">Recipe Name:</label>
        <input type="text" name="recipe_name" required>
        <br>
        <label for="description">Description:</label>
        <textarea name="description" required></textarea>
        <br>
        <label for="difficulty">Difficulty:</label>
        <textarea name="difficulty" required></textarea>
        <br>
        <label for="prep_time">Preparation Time:</label>
        <textarea name="prep_time" required></textarea>
        <br>
        <label for="cook_time">Cook Time:</label>
        <textarea name="cook_time" required></textarea>
        <br>
        <label for="cuisine_id">cuisine_id:</label>
        <textarea name="cuisine_id" required></textarea>
        <br>
        <label for="basicIngredient_id">Basic Ingredient:</label>
        <textarea name="basicIngredient_id" required></textarea>
        <button type="submit">Add Recipe</button>
    </form>
</body>
</html>

