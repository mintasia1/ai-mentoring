<?php
/**
 * Main Index Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

if (Auth::isLoggedIn()) {
    $role = Auth::getCurrentUserRole();
    header("Location: /pages/$role/dashboard.php");
    //exit();
}

$pageTitle = 'Home - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Welcome to CUHK Law E-Mentoring Platform.</h2>
    <p>Connect with experienced legal professionals and receive guidance for your career journey.</p>
    
    <h3>About the Platform</h3>
    <p>The CUHK Law E-Mentoring Platform facilitates meaningful connections between law students and alumni. Our smart matching system helps you find the perfect mentor based on your interests, career goals, and practice area preferences.</p>
    
    <h3>User Roles</h3>
    <ul>
        <li><strong>Mentees (Students):</strong> Create your profile, browse mentors, and send connection requests.</li>
        <li><strong>Mentors (Alumni):</strong> Share your expertise, guide students, and shape the next generation of legal professionals.</li>
    </ul>
    
    <div style="margin-top: 30px;">
        <a href="/pages/register.php" class="btn">Get Started - Register Now</a>
        <a href="/pages/login.php" class="btn btn-secondary">Login</a>
    </div>
</div>

<div class="card">
    <h3>Key Features</h3>
    <ul>
        <li><strong>Smart Matching:</strong> Our algorithm considers practice areas, programme levels, interests, and more.</li>
        <li><strong>Easy Communication:</strong> Use our workspace to share notes, set goals, and track progress.</li>
        <li><strong>Flexible Capacity:</strong> Mentors can set their availability and maximum mentee count.</li>
        <li><strong>Re-Match Policy:</strong> Get a second chance if a match doesn't work out.</li>
    </ul>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
