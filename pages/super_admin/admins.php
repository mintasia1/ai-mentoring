<?php
/**
 * Super Admin - Manage Admins
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole('super_admin');

$pageTitle = 'Manage Admins';
$userClass = new User();
$currentUserId = Auth::getCurrentUserId();

$message = '';
$messageType = '';

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Validation
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address';
        $messageType = 'error';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $message = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        $messageType = 'error';
    } elseif (!in_array($role, ['admin', 'super_admin'])) {
        $message = 'Invalid role selected';
        $messageType = 'error';
    } else {
        $userId = $userClass->createUser($email, $password, $role, $firstName, $lastName);
        if ($userId) {
            $message = "Successfully created new {$role}: {$email}";
            $messageType = 'success';
            AuditLog::log($currentUserId, 'admin_created', 'users', $userId, "Created new {$role}: {$email}");
        } else {
            $message = 'Failed to create admin. Email may already exist.';
            $messageType = 'error';
        }
    }
}

$admins = $userClass->getAllUsers('admin', 100, 0);
$superAdmins = $userClass->getAllUsers('super_admin', 100, 0);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Administrators</h2>

<div class="card">
    <a href="/pages/super_admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($message):
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message);
    </div>
<?php endif;

<div class="card">
    <h3>Create New Administrator</h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="create_admin">
        
        <div class="form-group">
            <label for="email">Email: *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password: *</label>
            <input type="password" id="password" name="password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
            <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name: *</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name: *</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="role">Role: *</label>
            <select id="role" name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
            </select>
        </div>
        
        <button type="submit" class="btn">Create Administrator</button>
    </form>
</div>

<div class="card">
    <h3>Super Administrators</h3>
    <?php if (empty($superAdmins)):
        <p>No super administrators found.</p>
    <?php else:
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($superAdmins as $admin):
                <tr>
                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                        <?php if ($admin['status'] === 'active'):
                            <span class="badge badge-success">Active</span>
                        <?php else:
                            <span class="badge badge-warning"><?php echo htmlspecialchars($admin['status']); ?></span>
                        <?php endif;
                    </td>
                    <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                </tr>
                <?php endforeach;
            </tbody>
        </table>
    <?php endif;
</div>

<div class="card">
    <h3>Administrators</h3>
    <?php if (empty($admins)):
        <p>No administrators found.</p>
    <?php else:
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin):
                <tr>
                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                        <?php if ($admin['status'] === 'active'):
                            <span class="badge badge-success">Active</span>
                        <?php else:
                            <span class="badge badge-warning"><?php echo htmlspecialchars($admin['status']); ?></span>
                        <?php endif;
                    </td>
                    <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                </tr>
                <?php endforeach;
            </tbody>
        </table>
    <?php endif;
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
