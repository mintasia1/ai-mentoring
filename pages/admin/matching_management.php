<?php
/**
 * Admin: Matching Management
 * View all match scores, trigger re-scoring, review AI explanations, manual overrides.
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Matching.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/CSRFToken.php';
require_once __DIR__ . '/../../classes/Logger.php';

Auth::requirePageAccess('admin_pages');

$pageTitle = 'Matching Management';
$bodyClass = 'admin-matching';
$db        = Database::getInstance()->getConnection();

$message     = '';
$messageType = '';

// ── Actions ───────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFToken::validate($_POST['csrf_token'] ?? '')) {
        $message     = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {

        $action = $_POST['action'] ?? '';

        // Rebuild all scores
        if ($action === 'rebuild_all') {
            $matchingClass = new Matching();
            $count         = $matchingClass->rebuildAllScores();
            $message       = "Re-scored $count mentor/mentee pairs successfully.";
            $messageType   = 'success';
            Logger::info("Admin triggered rebuild_all_scores", ['admin_id' => Auth::getCurrentUserId(), 'pairs' => $count]);

        // Reset rematch count for a specific mentee
        } elseif ($action === 'reset_rematch') {
            $menteeUserId = intval($_POST['mentee_user_id'] ?? 0);
            if ($menteeUserId > 0) {
                $stmt = $db->prepare("UPDATE mentee_profiles SET rematch_count = 0 WHERE user_id = ?");
                $stmt->execute([$menteeUserId]);
                $message     = "Re-match count reset for mentee #$menteeUserId.";
                $messageType = 'success';
                Logger::info("Admin reset rematch count", ['admin_id' => Auth::getCurrentUserId(), 'mentee_user_id' => $menteeUserId]);
            }

        // Generate AI explanation for a specific pair
        } elseif ($action === 'generate_explanation') {
            $menteeId = intval($_POST['mentee_id'] ?? 0);
            $mentorId = intval($_POST['mentor_id'] ?? 0);
            if ($menteeId > 0 && $mentorId > 0) {
                $matchingClass = new Matching();
                $explanation   = $matchingClass->getAIExplanation($menteeId, $mentorId);
                $message       = $explanation !== ''
                    ? "Explanation generated for pair ($menteeId, $mentorId)."
                    : 'Failed to generate explanation (check OpenAI API key).';
                $messageType   = $explanation !== '' ? 'success' : 'error';
            }
        }
    }
}

// ── Data queries ──────────────────────────────────────────────────────────────

// All match scores with user names
$scores = $db->query(
    "SELECT ms.*,
            mentee_u.first_name AS mentee_first, mentee_u.last_name AS mentee_last,
            mentor_u.first_name AS mentor_first, mentor_u.last_name AS mentor_last,
            mp.rematch_count
     FROM matching_scores ms
     JOIN users mentee_u ON mentee_u.id = ms.mentee_id
     JOIN users mentor_u ON mentor_u.id = ms.mentor_id
     LEFT JOIN mentee_profiles mp ON mp.user_id = ms.mentee_id
     ORDER BY ms.total_score DESC
     LIMIT 200"
)->fetchAll();

// Mentees with their rematch count
$mentees = $db->query(
    "SELECT u.id, u.first_name, u.last_name, u.email, mp.rematch_count
     FROM users u
     JOIN mentee_profiles mp ON mp.user_id = u.id
     ORDER BY u.first_name, u.last_name"
)->fetchAll();

// Stat: average score
$avgScore = $db->query("SELECT AVG(total_score) as avg FROM matching_scores")->fetch()['avg'] ?? 0;
$totalPairs = count($scores);

include __DIR__ . '/../../includes/header.php';
?>

<h2>🔗 Matching Management</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Stats -->
<div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
    <div class="card" style="flex:1;min-width:160px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;"><?php echo $totalPairs; ?></div>
        <div>Scored Pairs</div>
    </div>
    <div class="card" style="flex:1;min-width:160px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;"><?php echo round($avgScore, 1); ?>%</div>
        <div>Average Score</div>
    </div>
    <div class="card" style="flex:1;min-width:160px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;">
            <?php echo (defined('AI_MATCHING_ENABLED') && AI_MATCHING_ENABLED) ? '✅' : '❌'; ?>
        </div>
        <div>AI Matching</div>
    </div>
</div>

<!-- Actions -->
<div class="card" style="margin-bottom:20px;">
    <h3>Admin Actions</h3>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Re-score all pairs? This may take a while and consume OpenAI API tokens.');">
        <?php echo CSRFToken::getField(); ?>
        <input type="hidden" name="action" value="rebuild_all">
        <button type="submit" class="btn">🔄 Re-score All Pairs</button>
    </form>
    <p style="margin-top:8px;font-size:0.85rem;color:#666;">
        Re-runs the matching algorithm for every mentee/mentor combination. Embedding cache is reused where fresh.
    </p>
</div>

<!-- All Match Scores Table -->
<div class="card" style="margin-bottom:20px;">
    <h3>Match Score Overview (top 200)</h3>
    <?php if (empty($scores)): ?>
        <p>No scores found. Run "Re-score All Pairs" to populate.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Mentee</th>
                <th>Mentor</th>
                <th>Score</th>
                <th>Practice Area</th>
                <th>Programme</th>
                <th>Location</th>
                <th>Language</th>
                <th>Style</th>
                <th>Algorithm</th>
                <th>AI Explanation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($scores as $row):
            $score = round($row['total_score'], 1);
            if ($score >= 80) { $color = '#27ae60'; }
            elseif ($score >= 60) { $color = '#f39c12'; }
            else { $color = '#95a5a6'; }
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['mentee_first'] . ' ' . $row['mentee_last']); ?> <small>(#<?php echo $row['mentee_id']; ?>)</small></td>
                <td><?php echo htmlspecialchars($row['mentor_first'] . ' ' . $row['mentor_last']); ?> <small>(#<?php echo $row['mentor_id']; ?>)</small></td>
                <td><span style="font-weight:700;color:<?php echo $color; ?>"><?php echo $score; ?>%</span></td>
                <td><?php echo $row['practice_area_match'] ? '✅' : '❌'; ?></td>
                <td><?php echo $row['programme_match'] ? '✅' : '❌'; ?></td>
                <td><?php echo $row['location_match'] ? '✅' : '❌'; ?></td>
                <td><?php echo $row['language_match'] ? '✅' : '❌'; ?></td>
                <td><?php echo isset($row['mentoring_style_match']) && $row['mentoring_style_match'] ? '✅' : '—'; ?></td>
                <td><small><?php echo htmlspecialchars($row['algorithm_version'] ?? 'v1'); ?></small></td>
                <td style="max-width:260px;font-size:0.8rem;">
                    <?php if (!empty($row['ai_explanation'])): ?>
                        <details><summary style="cursor:pointer;color:#3498db;">View</summary><?php echo htmlspecialchars($row['ai_explanation']); ?></details>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <?php echo CSRFToken::getField(); ?>
                            <input type="hidden" name="action" value="generate_explanation">
                            <input type="hidden" name="mentee_id" value="<?php echo $row['mentee_id']; ?>">
                            <input type="hidden" name="mentor_id" value="<?php echo $row['mentor_id']; ?>">
                            <button type="submit" class="btn btn-secondary" style="padding:2px 8px;font-size:0.75rem;">Generate</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <!-- placeholder for future manual override action -->
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Re-match Reset -->
<div class="card">
    <h3>Re-match Quota Management</h3>
    <p style="font-size:0.9rem;color:#666;">Each mentee is allowed <strong><?php echo REMATCH_LIMIT; ?></strong> re-match(es) per the programme rules. Reset here if needed.</p>
    <table>
        <thead>
            <tr><th>Mentee</th><th>Email</th><th>Re-matches Used</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($mentees as $mentee): ?>
            <tr>
                <td><?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?></td>
                <td><?php echo htmlspecialchars($mentee['email']); ?></td>
                <td><?php echo $mentee['rematch_count']; ?></td>
                <td>
                    <?php if ($mentee['rematch_count'] >= REMATCH_LIMIT): ?>
                        <span style="color:#e74c3c;font-weight:600;">Exhausted</span>
                    <?php else: ?>
                        <span style="color:#27ae60;">Available (<?php echo REMATCH_LIMIT - $mentee['rematch_count']; ?> left)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($mentee['rematch_count'] > 0): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset re-match count for this mentee?');">
                        <?php echo CSRFToken::getField(); ?>
                        <input type="hidden" name="action" value="reset_rematch">
                        <input type="hidden" name="mentee_user_id" value="<?php echo $mentee['id']; ?>">
                        <button type="submit" class="btn btn-secondary" style="padding:3px 10px;font-size:0.8rem;">Reset</button>
                    </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
