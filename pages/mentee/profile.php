<?php
/**
 * Mentee Profile Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentee.php';

Auth::requireRole('mentee');

$pageTitle = 'My Profile';
$userId = Auth::getCurrentUserId();
$error = '';
$success = '';

$menteeClass = new Mentee();
$profile = $menteeClass->getProfile($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'student_id' => trim($_POST['student_id'] ?? ''),
        'programme_level' => $_POST['programme_level'] ?? '',
        'year_of_study' => intval($_POST['year_of_study'] ?? 0),
        'interests' => trim($_POST['interests'] ?? ''),
        'goals' => trim($_POST['goals'] ?? ''),
        'practice_area_preference' => $_POST['practice_area_preference'] ?? '',
        'language_preference' => trim($_POST['language_preference'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    if (empty($data['programme_level'])) {
        $error = 'Programme level is required';
    } else {
        if ($menteeClass->saveProfile($userId, $data)) {
            $success = 'Profile saved successfully!';
            $profile = $menteeClass->getProfile($userId);
        } else {
            $error = 'Failed to save profile';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h2>My Profile</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<div class="card">
    <form method="POST" action="">
        <div class="form-group">
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($profile['student_id'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="programme_level">Programme Level: *</label>
            <select id="programme_level" name="programme_level" required>
                <option value="">Select...</option>
                <?php foreach (PROGRAMME_LEVELS as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($profile['programme_level'] ?? '') === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="year_of_study">Year of Study:</label>
            <input type="number" id="year_of_study" name="year_of_study" min="1" max="5" value="<?php echo $profile['year_of_study'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="practice_area_preference">Practice Area Preference:</label>
            <select id="practice_area_preference" name="practice_area_preference">
                <option value="">Select...</option>
                <?php foreach (PRACTICE_AREAS as $area): ?>
                    <option value="<?php echo $area; ?>" <?php echo ($profile['practice_area_preference'] ?? '') === $area ? 'selected' : ''; ?>>
                        <?php echo $area; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="interests">Interests:</label>
            <textarea id="interests" name="interests"><?php echo htmlspecialchars($profile['interests'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="goals">Career Goals:</label>
            <textarea id="goals" name="goals"><?php echo htmlspecialchars($profile['goals'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="language_preference">Language Preference:</label>
            <input type="text" id="language_preference" name="language_preference" value="<?php echo htmlspecialchars($profile['language_preference'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn">Save Profile</button>
        <a href="/pages/mentee/dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
