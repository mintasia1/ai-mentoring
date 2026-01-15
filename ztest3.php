<?php
// Page Data Configuration
$pageTitle = "AppHQ8L35AHQ3 - Poe";
$botName = "AppHQ8L35AHQ3";
$creatorName = "@mintasiaAI";
$followerCount = "1 follower";
$botUrl = "https://poe.com/AppHQ8L35AHQ3";
$isNew = true; // Based on the "NEW" tag in the content

// Navigation Links
$navLinks = [
    "Explore" => "#",
    "Leaderboard" => "#",
    "Send feedback" => "#"
];

// Footer Links
$footerLinks = [
    "About Poe", "Company", "Blog", "Careers", "Help center", "Privacy policy", "Terms of service"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        /* Basic Reset and Typography */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #282c34;
            display: flex;
            height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: #f7f7f8;
            border-right: 1px solid #ebecf0;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 20px;
            color: #3c4257;
            display: flex;
            align-items: center;
        }
        
        .logo span { color: #4b32c3; }

        .nav-item {
            display: block;
            padding: 10px;
            color: #525866;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .nav-item:hover {
            background-color: #ebecf0;
        }

        .new-chat-btn {
            background-color: #4b32c3;
            color: white;
            padding: 10px;
            border-radius: 20px;
            text-align: center;
            margin-top: 10px;
            cursor: pointer;
            font-weight: 500;
        }

        .sidebar-footer {
            font-size: 12px;
            color: #8a94a6;
        }
        
        .sidebar-footer a {
            color: #8a94a6;
            text-decoration: none;
            margin-right: 5px;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .bot-profile {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .bot-avatar {
            width: 100px;
            height: 100px;
            background-color: #5cdb95; /* Placeholder color */
            border-radius: 24px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            font-weight: bold;
        }

        .bot-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .new-badge {
            background-color: #e3f2fd;
            color: #2196f3;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            vertical-align: middle;
        }

        .creator-info {
            color: #525866;
            font-size: 15px;
            margin-bottom: 24px;
        }

        .creator-link {
            color: #525866;
            font-weight: 600;
            text-decoration: none;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            color: #8a94a6;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        .btn-primary {
            background-color: #4b32c3;
            color: white;
            border: none;
        }

        .btn-secondary {
            background-color: #f0f2f5;
            color: #111;
            border: none;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div>
            <div class="logo">Poe</div>
            <div class="new-chat-btn">+ New chat</div>
            <br>
            <?php foreach ($navLinks as $name => $link): ?>
                <a href="<?php echo $link; ?>" class="nav-item"><?php echo $name; ?></a>
            <?php endforeach; ?>
        </div>
        
        <div class="sidebar-footer">
            <p>Download iOS app<br>Download Android app</p>
            <div style="margin-top: 10px;">
                <?php foreach ($footerLinks as $link): ?>
                    <a href="#"><?php echo $link; ?></a> · 
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="bot-profile">
            <!-- Avatar -->
            <div class="bot-avatar">
                <?php echo substr($botName, 0, 1); ?>
            </div>

            <!-- Title -->
            <h1 class="bot-title">
                <?php echo htmlspecialchars($botName); ?>
                <?php if($isNew): ?>
                    <span class="new-badge">NEW</span>
                <?php endif; ?>
            </h1>

            <!-- Creator Info -->
            <div class="creator-info">
                By <a href="#" class="creator-link"><?php echo htmlspecialchars($creatorName); ?></a>
                <br>
                <?php echo htmlspecialchars($followerCount); ?>
            </div>

            <!-- Description / Prompt (Placeholder based on typical layout) -->
            <p style="color: #666; margin-bottom: 30px;">
                Poe - Fast AI Chat. Chat with the best AI, privately or in a group chat.
            </p>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="<?php echo $botUrl; ?>" class="btn btn-primary">Go to <?php echo htmlspecialchars($botName); ?></a>
                <button class="btn btn-secondary">Share</button>
            </div>
            
            <div style="margin-top: 20px; font-size: 14px; color: #888;">
                <a href="#" style="color: inherit; margin: 0 10px;">History</a>
                <a href="#" style="color: inherit; margin: 0 10px;">Rates</a>
                <a href="#" style="color: inherit; margin: 0 10px;">API</a>
            </div>
        </div>
    </div>

</body>
</html>