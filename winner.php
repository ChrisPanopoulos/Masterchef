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

// Function to rate chefs
function rateChefs($conn, $episode_id) {
    // Fetch chefs and judges for the episode
    $query = "SELECT ep.cook_id, ep.recipe_id, ep.cuisine_id, ep.role, c.professional_training
              FROM episode_participants ep
              JOIN cook c ON ep.cook_id = c.cook_id
              WHERE ep.episode_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $episode_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $participants = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $participants[] = $row;
        }
    }

    // Separate chefs and judges
    $chefs = [];
    $judges = [];
    foreach ($participants as $participant) {
        if ($participant['role'] === 'Cook') {
            $chefs[] = $participant;
        } elseif ($participant['role'] === 'Judge') {
            $judges[] = $participant;
        }
    }

    // Assign random ratings (for simulation purposes)
    foreach ($chefs as $chef) {
        foreach ($judges as $judge) {
            $rating = rand(1, 5); // Random rating between 1 and 5
            $query = "INSERT INTO review (episode_id, cook_id, judge_id, score) VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE score = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiii", $episode_id, $chef['cook_id'], $judge['cook_id'], $rating, $rating);
            $stmt->execute();
        }
    }
}

// Function to determine the winner
function determineWinner($conn, $episode_id) {
    // Calculate total ratings for each chef in the episode
    $query = "SELECT ep.cook_id, SUM(r.score) AS total_score, c.professional_training
              FROM episode_participants ep
              JOIN review r ON ep.cook_id = r.cook_id AND ep.episode_id = r.episode_id
              JOIN cook c ON ep.cook_id = c.cook_id
              WHERE ep.episode_id = ? AND ep.role = 'Cook'
              GROUP BY ep.cook_id
              ORDER BY total_score DESC, c.professional_training DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $episode_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $ranked_chefs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ranked_chefs[] = $row;
        }
    }

    // Determine the winner
    if (count($ranked_chefs) > 0) {
        $winner = $ranked_chefs[0];
        if (count($ranked_chefs) > 1 && $ranked_chefs[0]['total_score'] === $ranked_chefs[1]['total_score']) {
            // Tie-breaking by professional training
            if ($ranked_chefs[0]['professional_training'] === $ranked_chefs[1]['professional_training']) {
                // Further tie-breaking randomly if necessary
                $winner = $ranked_chefs[array_rand([$ranked_chefs[0], $ranked_chefs[1]])];
            }
        }

        // Output the winner
        echo "Winner of episode $episode_id is Chef ID: " . $winner['cook_id'] . " with a total score of " . $winner['total_score'] . " and professional training: " . $winner['professional_training'];
    } else {
        echo "No cooks found for episode $episode_id.";
    }
}

// Example usage
$episode_id = 20; // Example episode ID

rateChefs($conn, $episode_id);
determineWinner($conn, $episode_id);

$conn->close();
?>

