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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'cook';  // The role is set to 'cook'

    // User information
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth']);
    $age = intval($_POST['age']);
    $experience = intval($_POST['experience']);
    $professional_training = $conn->real_escape_string($_POST['professional_training']);
    $specializations = $_POST['specializations'];  // Array of cuisine_ids

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Insert into chefs table
        $stmt = $conn->prepare("INSERT INTO cook (first_name, last_name, phone, age, experience, date_of_birth, professional_training, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiissi", $first_name, $last_name, $phone, $age, $experience, $date_of_birth, $professional_training, $user_id);
        $stmt->execute();
        $chef_id = $stmt->insert_id;
        $stmt->close();

        // Insert into cook_cuisine table
        $stmt = $conn->prepare("INSERT INTO cook_cuisine (cook_id, cuisine_id) VALUES (?, ?)");
        foreach ($specializations as $cuisine_id) {
            $stmt->bind_param("ii", $chef_id, $cuisine_id);
            $stmt->execute();
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        echo "Cook user and chef information added successfully!";
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo "Failed to add cook user and chef information: " . $e->getMessage();
    }
}

// Fetch cuisines from the database
$cuisines_result = $conn->query("SELECT cuisine_id, region FROM cuisine");

if (!$cuisines_result) {
    die("Query failed: " . $conn->error);
}

$cuisines = [];
if ($cuisines_result->num_rows > 0) {
    while ($row = $cuisines_result->fetch_assoc()) {
        $cuisines[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Cook User</title>
</head>
<body>
    <h1>Create Cook User</h1>
    <form method="post" action="create_cook.php">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <br>
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required>
        <br>
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required>
        <br>
        <label for="phone">Phone:</label>
        <input type="text" name="phone" required>
        <br>
        <label for="date_of_birth">Date of Birth:</label>
        <input type="date" name="date_of_birth" required>
        <br>
        <label for="age">Age:</label>
        <input type="number" name="age" required>
        <br>
        <label for="experience">Years of Experience:</label>
        <input type="number" name="experience" required>
        <br>
        <label for="professional_training">Professional Training:</label>
        <select name="professional_training" required>
            <option value="C">C</option>
            <option value="B">B</option>
            <option value="A">A</option>
            <option value="sous chef">Sous Chef</option>
            <option value="chef">Chef</option>
        </select>
        <br>
        <?php foreach ($cuisines as $cuisine) : ?>
   		<label>
        		<input type="checkbox" name="cuisines[]" value="<?= 		$cuisine['cuisine_id'] ?>">
        <?= htmlspecialchars($cuisine['region']) ?>
	    </label><br>
	<?php endforeach; ?>

        <br>
        <button type="submit">Create Cook User</button>
    </form>
</body>
</html>

