<?php
// Assuming you are using PDO for database operations
$dsn = 'mysql:host=your_host;dbname=your_db;charset=utf8';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// Fetch user and mentor profile data
$mentorId = $_SESSION['mentor_id'];
$stmt = $pdo->prepare('SELECT * FROM mentor_profiles WHERE mentor_id = :mentor_id');
$stmt->execute(['mentor_id' => $mentorId]);
$mentorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $alumniId = $_POST['alumni_id'];
    $graduationYear = $_POST['graduation_year'];
    $programmeLevel = $_POST['programme_level'];
    $practiceArea = $_POST['practice_area'];
    $currentPosition = $_POST['current_position'];
    $company = $_POST['company'];
    $bio = $_POST['bio'];
    $expertise = $_POST['expertise'];
    $interests = $_POST['interests'];
    $language = $_POST['language'];
    $location = $_POST['location'];
    $maxMentees = $_POST['max_mentees'];

    // Update the mentor profile
    $stmt = $pdo->prepare('UPDATE mentor_profiles SET alumni_id = :alumni_id, graduation_year = :graduation_year, programme_level = :programme_level, practice_area = :practice_area, current_position = :current_position, company = :company, bio = :bio, expertise = :expertise, interests = :interests, language = :language, location = :location, max_mentees = :max_mentees WHERE mentor_id = :mentor_id');
    $stmt->execute([
        'alumni_id' => $alumniId,
        'graduation_year' => $graduationYear,
        'programme_level' => $programmeLevel,
        'practice_area' => $practiceArea,
        'current_position' => $currentPosition,
        'company' => $company,
        'bio' => $bio,
        'expertise' => $expertise,
        'interests' => $interests,
        'language' => $language,
        'location' => $location,
        'max_mentees' => $maxMentees,
        'mentor_id' => $mentorId
    ]);  
}

// Calculate profile completion percentage
$profileCompletion = calculateProfileCompletion($mentorProfile);

function calculateProfileCompletion($profile) {
    $totalFields = 12; // total number of fields to complete
    $completedFields = 0;
    foreach ($profile as $field) {
        if (!empty($field)) {
            $completedFields++;
        }
    }
    return round(($completedFields / $totalFields) * 100);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile</title>
</head>
<body>
    <h1>Complete Your Profile</h1>
    <form method="POST">
        <label for="alumni_id">Alumni ID:</label><input type="text" name="alumni_id" required><br>
        <label for="graduation_year">Graduation Year:</label><input type="number" name="graduation_year" required><br>
        <label for="programme_level">Programme Level:</label><input type="text" name="programme_level" required><br>
        <label for="practice_area">Practice Area:</label><input type="text" name="practice_area"><br>
        <label for="current_position">Current Position:</label><input type="text" name="current_position" required><br>
        <label for="company">Company:</label><input type="text" name="company"><br>
        <label for="bio">Bio:</label><textarea name="bio"></textarea><br>
        <label for="expertise">Expertise:</label><input type="text" name="expertise"><br>
        <label for="interests">Interests:</label><input type="text" name="interests"><br>
        <label for="language">Language:</label><input type="text" name="language"><br>
        <label for="location">Location:</label><input type="text" name="location"><br>
        <label for="max_mentees">Max Mentees:</label><input type="number" name="max_mentees"><br>
        <button type="submit">Save Profile</button>
    </form>
    <p>Profile Completion: <?php echo $profileCompletion; ?>%</p>
</body>
</html>
