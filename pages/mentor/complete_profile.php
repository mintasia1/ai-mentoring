<?php
/**
 * Mentor Profile Completion Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/User.php';

Auth::requireRole(['mentor', 'admin', 'super_admin']);

$pageTitle = 'Complete Profile - Mentor';
$id_mentor_login = Auth::getCurrentUserId();
$pesan = '';
$pesan_type = '';

$userClass = new User();
$mentorClass = new Mentor();

// Fetch user data from users table
$user_data = $userClass->getUserById($id_mentor_login);

// Fetch mentor profile from mentor_profiles table
$mentor_data = $mentorClass->getProfile($id_mentor_login);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'alumni_id' => trim($_POST['alumni_id'] ?? ''),
        'graduation_year' => intval($_POST['graduation_year'] ?? 0),
        'programme_level' => $_POST['programme_level'] ?? '',
        'practice_area' => $_POST['practice_area'] ?? '',
        'current_position' => trim($_POST['current_position'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'expertise' => trim($_POST['expertise'] ?? ''),
        'interests' => trim($_POST['interests'] ?? ''),
        'language' => trim($_POST['language'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'bio' => trim($_POST['bio'] ?? ''),
        'max_mentees' => intval($_POST['max_mentees'] ?? MAX_MENTEES_PER_MENTOR)
    ];
    
    if (empty($data['programme_level'])) {
        $pesan = 'Programme level is required';
        $pesan_type = 'error';
    } elseif (empty($data['practice_area'])) {
        $pesan = 'Practice area is required';
        $pesan_type = 'error';
    } else {
        if ($mentorClass->saveProfile($id_mentor_login, $data)) {
            $pesan = 'Profile successfully updated!';
            $pesan_type = 'success';
            $mentor_data = $mentorClass->getProfile($id_mentor_login);
        } else {
            $pesan = 'Failed to update profile';
            $pesan_type = 'error';
        }
    }
}

// Calculate profile completion
$requiredFields = ['alumni_id', 'graduation_year', 'practice_area', 'current_position', 'company', 'bio'];
$completedFields = 0;
$totalFields = count($requiredFields);

if ($mentor_data) {
    foreach ($requiredFields as $field) {
        if (!empty($mentor_data[$field])) {
            $completedFields++;
        }
    }
}

$completionPercentage = $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;

include __DIR__ . '/../../includes/header.php';
?>

<style>
    .completion-bar {
        height: 10px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
    }
    
    .completion-fill {
        height: 100%;
        background: #4CAF50;
        transition: width 0.3s;
    }
    
    .info-text {
        font-size: 0.9rem;
        color: #666;
        margin-top: 5px;
    }
    
    .required {
        color: #e74c3c;
    }
</style>

<h2>Complete Your Mentor Profile</h2>

<div class="card" style="background: #f0f0f0;">
    <strong>Profile Completion: <?php echo $completionPercentage; ?>%</strong>
    <div class="completion-bar">
        <div class="completion-fill" style="width: <?php echo $completionPercentage; ?>%;"></div>
    </div>
</div>

<?php if (!empty($pesan)): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>">
        <?php echo htmlspecialchars($pesan); ?>
    </div>
<?php endif; ?>
<div class="card">
    <form method="POST" id="profileForm">
        <h3>Basic Information</h3>
        
        <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" disabled>
            <p class="info-text">Email cannot be changed</p>
        </div>
        
        <div class="form-group">
            <label for="alumni_id">Alumni ID <span class="required">*</span></label>
            <input 
                type="text" 
                id="alumni_id" 
                name="alumni_id" 
                value="<?php echo htmlspecialchars($mentor_data['alumni_id'] ?? ''); ?>" 
                required
                placeholder="e.g., A123456">
        </div>
        
        <div class="form-group">
            <label for="graduation_year">Graduation Year <span class="required">*</span></label>
            <input 
                type="number" 
                id="graduation_year" 
                name="graduation_year" 
                min="1950" 
                max="<?php echo date('Y'); ?>" 
                value="<?php echo $mentor_data['graduation_year'] ?? ''; ?>" 
                required
                placeholder="<?php echo date('Y'); ?>">
        </div>
        
        <div class="form-group">
            <label for="programme_level">Programme Level <span class="required">*</span></label>
            <select id="programme_level" name="programme_level" required>
                <option value="">Select...</option>
                <?php foreach (PROGRAMME_LEVELS as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($mentor_data['programme_level'] ?? '') === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <h3>Professional Information</h3>
        
        <div class="form-group">
            <label for="practice_area">Practice Area <span class="required">*</span></label>
            <select id="practice_area" name="practice_area" required>
                <option value="">Select...</option>
                <?php foreach (PRACTICE_AREAS as $area): ?>
                    <option value="<?php echo $area; ?>" <?php echo ($mentor_data['practice_area'] ?? '') === $area ? 'selected' : ''; ?>>
                        <?php echo $area; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="current_position">Current Position <span class="required">*</span></label>
            <input 
                type="text" 
                id="current_position" 
                name="current_position" 
                value="<?php echo htmlspecialchars($mentor_data['current_position'] ?? ''); ?>" 
                required
                placeholder="e.g., Senior Associate, Partner">
        </div>
        
        <div class="form-group">
            <label for="company">Company/Organization <span class="required">*</span></label>
            <input 
                type="text" 
                id="company" 
                name="company" 
                value="<?php echo htmlspecialchars($mentor_data['company'] ?? ''); ?>" 
                required
                placeholder="e.g., ABC Law Firm">
        </div>
        
        <div class="form-group">
            <label for="bio">Bio <span class="required">*</span></label>
            <textarea 
                id="bio" 
                name="bio" 
                required
                placeholder="Tell mentees about yourself, your experience, and what you can offer as a mentor"><?php echo htmlspecialchars($mentor_data['bio'] ?? ''); ?></textarea>
            <p class="info-text">This will be visible to mentees when they browse mentors.</p>
        </div>
        
        <h3>Additional Information</h3>
        
        <div class="form-group">
            <label for="expertise">Areas of Expertise</label>
            <textarea 
                id="expertise" 
                name="expertise" 
                placeholder="List your key areas of expertise (e.g., M&A, Corporate Restructuring, Litigation)"><?php echo htmlspecialchars($mentor_data['expertise'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="interests">Professional Interests</label>
            <textarea 
                id="interests" 
                name="interests"
                placeholder="What are your professional interests?"><?php echo htmlspecialchars($mentor_data['interests'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="language">Languages</label>
            <input 
                type="text" 
                id="language" 
                name="language" 
                value="<?php echo htmlspecialchars($mentor_data['language'] ?? ''); ?>"
                placeholder="e.g., English, Cantonese, Mandarin">
        </div>
        
        <div class="form-group">
            <label for="location">Location</label>
            <input 
                type="text" 
                id="location" 
                name="location" 
                value="<?php echo htmlspecialchars($mentor_data['location'] ?? ''); ?>"
                placeholder="e.g., Hong Kong, Beijing">
        </div>
        
        <div class="form-group">
            <label for="max_mentees">Maximum Number of Mentees</label>
            <input 
                type="number" 
                id="max_mentees" 
                name="max_mentees" 
                min="1" 
                max="10" 
                value="<?php echo $mentor_data['max_mentees'] ?? MAX_MENTEES_PER_MENTOR; ?>">
            <p class="info-text">How many mentees can you mentor at one time? (Default: <?php echo MAX_MENTEES_PER_MENTOR; ?>)</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/pages/mentor/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button type="submit" name="update_profile" class="btn btn-success">💾 Save Profile</button>
        </div>
    </form>
</div>

<script>
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const required = ['alumni_id', 'graduation_year', 'programme_level', 'practice_area', 'current_position', 'company', 'bio'];
        
        for (const field of required) {
            const element = document.getElementById(field);
            if (!element) continue;
            
            const value = element.value.trim();
            if (!value) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *)');
                element.focus();
                return false;
            }
        }
        
        return true;
    });
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 