<?php
/**
 * Mentee Profile Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentee.php';
require_once __DIR__ . '/../../classes/CSRFToken.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'My Profile';
$userId = Auth::getCurrentUserId();
$error = '';
$success = '';

$menteeClass = new Mentee();
$profile = $menteeClass->getProfile($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFToken::validate($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $programme_level = $_POST['programme_level'] ?? '';
        $practice_area_preference = $_POST['practice_area_preference'] ?? '';
    
    // Handle "Other" option for programme level
    if ($programme_level === 'Other') {
        $programme_level_other = trim($_POST['programme_level_other'] ?? '');
        if (!empty($programme_level_other)) {
            // Validate: no double quotes
            if (strpos($programme_level_other, '"') !== false) {
                $error = 'Double quotes are not allowed in programme level';
            } else {
                $programme_level = $programme_level_other;
            }
        }
    }
    
    // Handle "Other" option for practice area
    if ($practice_area_preference === 'Other') {
        $practice_area_other = trim($_POST['practice_area_other'] ?? '');
        if (!empty($practice_area_other)) {
            // Validate: no double quotes
            if (strpos($practice_area_other, '"') !== false) {
                $error = 'Double quotes are not allowed in practice area';
            } else {
                $practice_area_preference = $practice_area_other;
            }
        }
    }
    
    if (!isset($error)) {
        $data = [
            'student_id' => trim($_POST['student_id'] ?? ''),
            'programme_level' => $programme_level,
            'year_of_study' => intval($_POST['year_of_study'] ?? 0),
            'interests' => trim($_POST['interests'] ?? ''),
            'goals' => trim($_POST['goals'] ?? ''),
            'practice_area_preference' => $practice_area_preference,
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
        <?php echo CSRFToken::getField(); ?>
        <div class="form-group">
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" maxlength="200" value="<?php echo htmlspecialchars($profile['student_id'] ?? ''); ?>">
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
        
        <div class="form-group" id="programme_level_other_div" style="display: none;">
            <label for="programme_level_other">Please specify Programme Level: *</label>
            <input 
                type="text" 
                id="programme_level_other" 
                name="programme_level_other" 
                maxlength="200"
                placeholder="Enter your programme level"
                pattern="<?php echo OTHER_INPUT_PATTERN; ?>"
                title="<?php echo OTHER_INPUT_DESCRIPTION; ?>">
            <p style="font-size: 0.9rem; color: #666;">Maximum 200 characters. <?php echo OTHER_INPUT_DESCRIPTION; ?></p>
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
        
        <div class="form-group" id="practice_area_other_div" style="display: none;">
            <label for="practice_area_other">Please specify Practice Area:</label>
            <input 
                type="text" 
                id="practice_area_other" 
                name="practice_area_other" 
                maxlength="200"
                placeholder="Enter your practice area preference"
                pattern="<?php echo OTHER_INPUT_PATTERN; ?>"
                title="<?php echo OTHER_INPUT_DESCRIPTION; ?>">
            <p style="font-size: 0.9rem; color: #666;">Maximum 200 characters. <?php echo OTHER_INPUT_DESCRIPTION; ?></p>
        </div>
        
        <div class="form-group">
            <label for="interests">Interests:</label>
            <textarea id="interests" name="interests" maxlength="500"><?php echo htmlspecialchars($profile['interests'] ?? ''); ?></textarea>
            <p style="font-size: 0.9rem; color: #666;">Maximum 500 characters</p>
        </div>
        
        <div class="form-group">
            <label for="goals">Career Goals:</label>
            <textarea id="goals" name="goals" maxlength="500"><?php echo htmlspecialchars($profile['goals'] ?? ''); ?></textarea>
            <p style="font-size: 0.9rem; color: #666;">Maximum 500 characters</p>
        </div>
        
        <div class="form-group">
            <label for="language_preference">Language Preference:</label>
            <input type="text" id="language_preference" name="language_preference" maxlength="200" value="<?php echo htmlspecialchars($profile['language_preference'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" maxlength="200" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio" maxlength="500"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
            <p style="font-size: 0.9rem; color: #666;">Maximum 500 characters</p>
        </div>
        
        <button type="submit" class="btn" style="display: inline-block; padding: 10px 20px; font-size: 1rem; text-decoration: none;">Save Profile</button>
        <a href="/pages/mentee/dashboard.php" class="btn btn-secondary" style="display: inline-block; padding: 10px 20px; font-size: 1rem; text-decoration: none;">Cancel</a>
    </form>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
