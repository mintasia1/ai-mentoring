<?php
/**
 * Mentor Profile Completion Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/User.php';

Auth::requireRole('mentor');

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
        'current_position' => trim($_POST['current_position'] ??  ''),
        'company' => trim($_POST['company'] ??  ''),
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
        if (! empty($mentor_data[$field])) {
            $completedFields++;
        }
    }
}

$completionPercentage = $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
?>
<! DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Complete Profile - Mentor</title>
  <link rel="stylesheet" href="../../assets/css/mentor. css" />
  <style>
    .profile-form-container {
      background: var(--card-bg, #fff);
      padding: 30px;
      border-radius: 15px;
      margin-top: 20px;
      box-shadow:  0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    . form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary-color, #333);
      font-weight: 600;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 2px solid var(--card-border, #ddd);
      border-radius: 8px;
      background: var(--bg-color, #fff);
      color: var(--text-color, #333);
      font-family: 'Segoe UI', sans-serif;
      transition: border-color 0.3s;
    }
    
    . form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary-color, #4CAF50);
    }
    
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    . submit-btn {
      background: var(--primary-color, #4CAF50);
      color: #fff;
      padding: 14px 30px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 10px;
    }
    
    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .back-btn {
      background: #666;
      color: #fff;
      padding: 14px 30px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      margin-right: 10px;
      transition: all 0.3s;
    }
    
    .back-btn:hover {
      background: #555;
    }
    
    .message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    
    .message.success {
      background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
      color: #fff;
    }
    
    .message.error {
      background: linear-gradient(135deg, #ff6b6b 0%, #ff9a56 100%);
      color: #fff;
    }
    
    .required {
      color: #ff6b6b;
    }
    
    .info-text {
      font-size: 0.9rem;
      color: #999;
      margin-top: 5px;
    }
    
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
  </style>
</head>
<body>
  <div class="mentor-container">
    <aside class="sidebar">
      <img src="../../assets/images/logo.png" class="logo" alt="Logo" />
      <h2>Mentor Panel</h2>
      <nav>
        <a href="/pages/mentor/dashboard. php">Dashboard</a>
        <a href="/pages/mentor/complete_profile.php" class="active">Complete Profile</a>
        <a href="/pages/logout.php">Logout</a>
      </nav>
    </aside>
    
    <main class="main-content">
      <header>
        <h1>Complete Your Mentor Profile</h1>
        <p>Fill in your profile information to start accepting mentees. </p>
      </header>
      
      <div style="background: #f0f0f0; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <strong>Profile Completion:  <?=$completionPercentage ?>%</strong>
        <div class="completion-bar">
          <div class="completion-fill" style="width: <?= $completionPercentage ?>%;"></div>
        </div>
      </div>
      
      <?php if (! empty($pesan)): ?>
      <div class="message <?= $pesan_type ?>">
        <?= htmlspecialchars($pesan) ?>
      </div>
      <?php endif; ?>
      
      <div class="profile-form-container">
        <form method="POST" id="profileForm">
          <h3>Basic Information</h3>
          
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" value="<?= htmlspecialchars($user_data['email']) ?>" disabled>
            <p class="info-text">Email cannot be changed</p>
          </div>
          
          <div class="form-group">
            <label for="alumni_id">Alumni ID <span class="required">*</span></label>
            <input 
              type="text" 
              id="alumni_id" 
              name="alumni_id" 
              value="<?= htmlspecialchars($mentor_data['alumni_id'] ?? '') ?>" 
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
              max="<?=date('Y') ?>" 
              value="<?=$mentor_data['graduation_year'] ??  '' ?>" 
              required
              placeholder="<?= date('Y') ?>">
          </div>
          
          <div class="form-group">
            <label for="programme_level">Programme Level <span class="required">*</span></label>
            <select id="programme_level" name="programme_level" required>
              <option value="">Select... </option>
              <?php foreach (PROGRAMME_LEVELS as $key => $label): ?>
                <option value="<?=$key ?>" <?=($mentor_data['programme_level'] ??  '') === $key ? 'selected' : '' ?>>
                  <?= $label ?>
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
                <option value="<?= $area ?>" <?= ($mentor_data['practice_area'] ?? '') === $area ? 'selected' : '' ?>>
                  <?= $area ?>
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
              value="<?= htmlspecialchars($mentor_data['current_position'] ?? '') ?>" 
              required
              placeholder="e.g., Senior Associate, Partner">
          </div>
          
          <div class="form-group">
            <label for="company">Company/Organization <span class="required">*</span></label>
            <input 
              type="text" 
              id="company" 
              name="company" 
              value="<?= htmlspecialchars($mentor_data['company'] ?? '') ?>" 
              required
              placeholder="e.g., ABC Law Firm">
          </div>
          
          <div class="form-group">
            <label for="bio">Bio <span class="required">*</span></label>
            <textarea 
              id="bio" 
              name="bio" 
              required
              placeholder="Tell mentees about yourself, your experience, and what you can offer as a mentor"><?= htmlspecialchars($mentor_data['bio'] ?? '') ?></textarea>
            <p class="info-text">This will be visible to mentees when they browse mentors.</p>
          </div>
          
          <h3>Additional Information</h3>
          
          <div class="form-group">
            <label for="expertise">Areas of Expertise</label>
            <textarea 
              id="expertise" 
              name="expertise" 
              placeholder="List your key areas of expertise (e.g., M&A, Corporate Restructuring, Litigation)"><?= htmlspecialchars($mentor_data['expertise'] ?? '') ?></textarea>
          </div>
          
          <div class="form-group">
            <label for="interests">Professional Interests</label>
            <textarea 
              id="interests" 
              name="interests"
              placeholder="What are your professional interests?"><?= htmlspecialchars($mentor_data['interests'] ?? '') ?></textarea>
          </div>
          
          <div class="form-group">
            <label for="language">Languages</label>
            <input 
              type="text" 
              id="language" 
              name="language" 
              value="<?= htmlspecialchars($mentor_data['language'] ?? '') ?>"
              placeholder="e.g., English, Cantonese, Mandarin">
          </div>
          
          <div class="form-group">
            <label for="location">Location</label>
            <input 
              type="text" 
              id="location" 
              name="location" 
              value="<?= htmlspecialchars($mentor_data['location'] ?? '') ?>"
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
              value="<?= $mentor_data['max_mentees'] ?? MAX_MENTEES_PER_MENTOR ?>">
            <p class="info-text">How many mentees can you mentor at one time?  (Default: <?= MAX_MENTEES_PER_MENTOR ?>)</p>
          </div>
          
          <div style="margin-top: 30px;">
            <a href="/pages/mentor/dashboard.php" class="back-btn">Back</a>
            <button type="submit" name="update_profile" class="submit-btn">💾 Save Profile</button>
          </div>
        </form>
      </div>
    </main>
  </div>
  
  <script src="../../assets/js/script.js"></script>
  <script>
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
      const required = ['alumni_id', 'graduation_year', 'programme_level', 'practice_area', 'current_position', 'company', 'bio'];
      
      for (const field of required) {
        const value = document.getElementById(field).value.trim();
        if (!value) {
          e.preventDefault();
          alert(`Please fill in all required fields (marked with *)`);
          return false;
        }
      }
      
      return true;
    });
  </script>
</body>
</html>