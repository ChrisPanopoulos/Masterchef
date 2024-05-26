<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "masterchef";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to calculate calories per serving
function calculateCaloriesPerServing($recipe_id, $conn) {
    // Get the ingredients and their quantities for the recipe
    $query = "SELECT ri.quantity_id, i.calories_per_100,q.quantity 
              FROM recipe_ingredient ri
              JOIN ingredient i ON ri.ingredient_id = i.ingredient_id
              JOIN quantity q ON ri.quantity_id = q.quantity_id
              WHERE ri.recipe_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $stmt->bind_result($quantity_id, $calories_per_100,$quantity);

    $total_calories = 0;

    // Calculate total calories
    while ($stmt->fetch()) {
        $calories = ($quantity / 100) * $calories_per_100;
        $total_calories += $calories;
    }

    $stmt->close();
    
    $query = "UPDATE nutritional_info SET total_calories_per_portion = ? WHERE recipe_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii",$total_calories,$recipe_id);
    $stmt->execute();
    $stmt->close();
}

// Example usage
$recipe_id = 2; // Replace with the actual recipe ID
$calories_per_serving = calculateCaloriesPerServing($recipe_id, $conn);

echo "Calories per serving: " . $calories_per_serving;

$conn->close();
?>
