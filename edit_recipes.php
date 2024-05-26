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
    $recipe_id = $_POST['recipe_id'];
    $recipe_name = $_POST['recipe_name'];
    $description = $_POST['description'];
    // Add other fields as necessary

    $stmt = $conn->prepare("UPDATE recipe SET name = ?, description = ? WHERE recipe_id = ? AND created_by = ?");
    $stmt->bind_param("ssii", $recipe_name, $description, $recipe_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Recipe updated successfully!";
    } else {
        echo "Failed to update recipe.";
    }
}

$query = "SELECT r.recipe_id, r.name, r.description FROM recipe r inner join episode_participants ep on r.recipe_id = ep.episode_participants WHERE created_by = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Recipes</title>
</head>
<body>
    <h1>Edit My Recipes</h1>
    <form method="post" action="edit_recipes.php">
        <select name="recipe_id">
            <?php while ($row = $result->fetch_assoc()): ?>
                <option value="<?php echo $row['recipe_id']; ?>"><?php echo $row['name']; ?></option>
            <?php endwhile; ?>
        </select>
        <br>
        <label for="name">Recipe Name:</label>
        <input type="text" name="name" required>
        <br>
        <label for="description">Description:</label>
        <textarea name="description" required></textarea>
        <br>
        <button type="submit">Update Recipe</button>
    </form>
</body>
</html>
<?php
$conn->close();
?>

