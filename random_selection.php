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

function getRecentChefs($conn) {
    // Step 1: Fetch the latest 3 episode IDs
    $query = "SELECT episode_id FROM episode ORDER BY season DESC LIMIT 3";
    $result = $conn->query($query);
    $recent_episode_ids = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_episode_ids[] = $row['episode_id'];
        }
    }

    // Step 2: Fetch distinct chef IDs from the recent episodes
    if (!empty($recent_episode_ids)) {
        $placeholders = implode(',', array_fill(0, count($recent_episode_ids), '?'));
        $query = "SELECT DISTINCT cook_id FROM episode_participants WHERE episode_id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $types = str_repeat('i', count($recent_episode_ids));
        $stmt->bind_param($types, ...$recent_episode_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $recent_chefs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_chefs[] = $row['cook_id'];
            }
        }
        return $recent_chefs;
    }
    return [];
}

function getRandomParticipants($conn, $episode_id) {
    // Fetch recent chefs
    $recent_chefs = getRecentChefs($conn);

    // Fetch all distinct national cuisines
    $query = "SELECT DISTINCT cuisine_id FROM cook_cuisine LIMIT 10";
    $result = $conn->query($query);
    $national_cuisines = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $national_cuisines[] = $row['cuisine_id'];
        }
    }

    // Initialize arrays to store selected chef IDs and judge IDs
    $selected_chef_ids = [];
    $selected_judge_ids = [];
    $selected_chefs = 0;

    // Select chefs and recipes
    foreach ($national_cuisines as $cuisine_id) {
        if ($selected_chefs >= 10) break;

        // Get all eligible chefs for the cuisine
        $query = "SELECT c.cook_id 
                  FROM cook c 
                  JOIN cook_cuisine cc ON c.cook_id = cc.cook_id 
                  WHERE cc.cuisine_id = ?";

        // Add condition for recent chefs if there are any
        if (!empty($recent_chefs)) {
            $placeholders = implode(',', array_fill(0, count($recent_chefs), '?'));
            $query .= " AND c.cook_id NOT IN ($placeholders)";
        }

        // Add condition for already selected chefs
        if (!empty($selected_chef_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_chef_ids), '?'));
            $query .= " AND c.cook_id NOT IN ($placeholders)";
        }

        // Add condition for already selected judges
        if (!empty($selected_judge_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_judge_ids), '?'));
            $query .= " AND c.cook_id NOT IN ($placeholders)";
        }

        $stmt = $conn->prepare($query);

        // Bind parameters dynamically
        $types = 'i'; // 'i' for $cuisine_id
        $params = [$cuisine_id];
        if (!empty($recent_chefs)) {
            $types .= str_repeat('i', count($recent_chefs));
            $params = array_merge($params, $recent_chefs);
        }
        if (!empty($selected_chef_ids)) {
            $types .= str_repeat('i', count($selected_chef_ids));
            $params = array_merge($params, $selected_chef_ids);
        }
        if (!empty($selected_judge_ids)) {
            $types .= str_repeat('i', count($selected_judge_ids));
            $params = array_merge($params, $selected_judge_ids);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $chef_result = $stmt->get_result();

        // Collect eligible chefs
        $eligible_chefs = [];
        if ($chef_result) {
            while ($row = $chef_result->fetch_assoc()) {
                $eligible_chefs[] = $row['cook_id'];
            }
        }

        // Randomly select a chef from eligible chefs
        if (!empty($eligible_chefs)) {
            $random_index = array_rand($eligible_chefs);
            $chef_id = $eligible_chefs[$random_index];
            $selected_chef_ids[] = $chef_id;
            $selected_chefs++;

            // Select a random recipe from the same cuisine
            $query = "SELECT recipe_id FROM recipe WHERE cuisine_id = ? ORDER BY RAND() LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $cuisine_id);
            $stmt->execute();
            $recipe_result = $stmt->get_result();
            $recipe_id = $recipe_result->fetch_assoc()['recipe_id'];

            // Insert selected chef and recipe into database
            $query = "INSERT INTO episode_participants (episode_id, cook_id, recipe_id, role, cuisine_id) VALUES (?, ?, ?, 'Cook', ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiii", $episode_id, $chef_id, $recipe_id, $cuisine_id);
            $stmt->execute();
        }
    }

    // Select 3 random judges
    $query = "SELECT cook_id FROM cook WHERE professional_training IN ('chef', 'sous chef')";

    // Add condition to exclude already selected chefs and judges
    if (!empty($selected_chef_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_chef_ids), '?'));
        $query .= " AND cook_id NOT IN ($placeholders)";
    }

    if (!empty($selected_judge_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_judge_ids), '?'));
        $query .= " AND cook_id NOT IN ($placeholders)";
    }

    $query .= " ORDER BY RAND() LIMIT 3";

    $stmt = $conn->prepare($query);

    // Bind parameters dynamically
    $params = array_merge($selected_chef_ids, $selected_judge_ids);
    $types = str_repeat('i', count($selected_chef_ids) + count($selected_judge_ids));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $judges_result = $stmt->get_result();

    // Collect selected judge IDs
    $selected_judge_ids = [];
    if ($judges_result) {
        while ($row = $judges_result->fetch_assoc()) {
            $selected_judge_ids[] = $row['cook_id'];
        }
    }

    // Insert judges into database
    foreach ($selected_judge_ids as $judge_id) {
        $query = "INSERT INTO episode_participants (episode_id, cook_id, role) VALUES (?, ?, 'Judge')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $episode_id, $judge_id);
        $stmt->execute();
    }
}

$episode_id = 20; // For example, change as needed
getRandomParticipants($conn, $episode_id);

$conn->close();
?>

