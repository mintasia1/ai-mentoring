<?php
// CUHK Law E-Mentoring - Full Suite
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CUHK Law E-Mentoring - Full Suite</title>
    <style>
        :root {
            --cuhk-gold: #D4AF37;
            --cuhk-purple: #4E2A84;
            --bg-light: #f4f6f9;
            --text-dark: #2c3e50;
            --white: #ffffff;
            --success: #27ae60;
            --danger: #c0392b;
            --warning: #f39c12;
            --gray: #95a5a6;
            --border: #e2e8f0;
        }
        
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 0; background-color: var(--bg-light); color: var(--text-dark); }
        
        /* Navigation Bar */
        .mockup-nav {
            background: #2c3e50;
            padding: 10px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .mockup-nav button {
            background: #465c71;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .mockup-nav button:hover { background: #576f89; }
        .mockup-nav button.active { background: var(--cuhk-gold); color: #2c3e50; font-weight: bold; }

        /* Layout Basics */
        .screen { display: none; max-width: 1200px; margin: 20px auto; background: white; min-height: 80vh; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        .screen.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .app-header {
            background: var(--cuhk-purple);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container { padding: 30px; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        /* Typography & Components */
        h1, h2, h3 { margin-top: 0; color: var(--cuhk-purple); }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .badge-purple { background: #ede9f2; color: var(--cuhk-purple); }
        .badge-gold { background: #fcf6e0; color: #8a7018; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
        .btn-primary { background: var(--cuhk-purple); color: white; }
        .btn-outline { border: 2px solid var(--cuhk-purple); color: var(--cuhk-purple); background: transparent; }
        .btn-danger-outline { border: 1px solid var(--danger); color: var(--danger); background: transparent; }
        .card { border: 1px solid var(--border); border-radius: 8px; padding: 20px; background: white; transition: transform 0.2s; }
        .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* --- DASHBOARD SPECIFIC --- */
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border); }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: var(--cuhk-purple); }
        .task-list { list-style: none; padding: 0; }
        .task-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #eee; }

        /* --- FIND MENTOR SPECIFIC --- */
        .filter-bar { background: #f1f5f9; padding: 15px; border-radius: 8px; display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .mentor-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .mentor-card-img { width: 60px; height: 60px; border-radius: 50%; background: #ddd; object-fit: cover; margin-bottom: 10px; }

        /* --- ANALYTICS SPECIFIC --- */
        .chart-placeholder { height: 200px; background: #f9f9f9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #888; border-radius: 8px; margin-top: 10px; }

        /* --- PROFILE DETAIL --- */
        .profile-header { display: flex; gap: 20px; border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 20px; }
        .profile-avatar { width: 120px; height: 120px; background: #ddd; border-radius: 50%; object-fit: cover; }
        .profile-info { flex: 1; }
        .match-indicator { background: #e8f5e9; color: var(--success); padding: 10px; border-radius: 8px; text-align: center; border: 1px solid #c8e6c9; }
        .section-title { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gray); margin-bottom: 10px; margin-top: 20px; }
        .skill-tag { background: var(--bg-light); border: 1px solid var(--border); padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; margin-right: 5px; display: inline-block; margin-bottom: 5px; }

        /* --- GOAL TRACKER --- */
        .timeline-item { display: flex; gap: 15px; margin-bottom: 20px; opacity: 0.6; }
        .timeline-item.active { opacity: 1; }
        .timeline-check { width: 24px; height: 24px; border: 2px solid var(--cuhk-purple); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .timeline-check.checked { background: var(--cuhk-purple); color: white; }
        .resource-link { display: block; padding: 10px; background: #f8f9fa; border-left: 3px solid var(--cuhk-gold); margin-top: 5px; text-decoration: none; color: #333; }

        /* --- ADMIN RESOLUTION --- */
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .admin-table th { text-align: left; padding: 12px; background: #f1f5f9; border-bottom: 2px solid var(--border); }
        .admin-table td { padding: 12px; border-bottom: 1px solid var(--border); }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .bg-red { background: var(--danger); }
        .bg-orange { background: var(--warning); }

        /* --- PRIVACY SETTINGS --- */
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border); }
        .toggle-switch { width: 50px; height: 26px; background: #ccc; border-radius: 13px; position: relative; cursor: pointer; }
        .toggle-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle-switch.on { background: var(--success); }
        .toggle-switch.on::after { left: 27px; }

        /* --- ALUMNI VERIFY --- */
        .verify-box { max-width: 600px; margin: 40px auto; text-align: center; }
        .upload-zone { border: 2px dashed var(--gray); padding: 40px; border-radius: 8px; margin: 20px 0; color: var(--gray); cursor: pointer; }
        .upload-zone:hover { border-color: var(--cuhk-purple); background: #fbfbfc; }

        /* --- CONNECTION REQUEST --- */
        .modal-sim { max-width: 600px; margin: 0 auto; border: 1px solid var(--border); padding: 30px; border-radius: 8px; background: #fff; }
        textarea { width: 100%; height: 120px; padding: 10px; border: 1px solid var(--border); border-radius: 4px; margin-top: 10px; }

        /* Responsive */
        @media (max-width: 768px) {
            .grid-2, .grid-3, .grid-equal, .mentor-card-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-avatar { margin: 0 auto; }
        }
    </style>
</head>
<body>

    <div class="mockup-nav">
        <button onclick="showScreen('dashboard')" class="active" id="btn-dashboard">1. Mentee Dashboard</button>
        <button onclick="showScreen('findmentor')" id="btn-findmentor">2. Find Mentor</button>
        <button onclick="showScreen('profile')" id="btn-profile">3. Profile Detail</button>
        <button onclick="showScreen('request')" id="btn-request">4. Connection Request</button>
        <button onclick="showScreen('goals')" id="btn-goals">5. Goal Tracker</button>
        <button onclick="showScreen('admin-analytics')" id="btn-admin-analytics">6. Admin Analytics</button>
        <button onclick="showScreen('admin-res')" id="btn-admin-res">7. Match Resolution</button>
        <button onclick="showScreen('privacy')" id="btn-privacy">8. Privacy</button>
        <button onclick="showScreen('verify')" id="btn-verify">9. Verification</button>
    </div>

    <!-- 1. MENTEE DASHBOARD -->
    <div id="dashboard" class="screen active">
        <header class="app-header">
            <div><strong>CUHK</strong> LAW | Student Portal</div>
            <div>Welcome, Alex</div>
        </header>
        <div class="container">
            <div class="grid-2">
                <div>
                    <h2>My Current Status</h2>
                    <div class="card" style="border-left: 5px solid var(--warning); margin-bottom: 20px;">
                        <h3 style="margin-bottom: 5px; color: var(--warning);">Pending Request</h3>
                        <p style="margin: 0;">You have sent a request to <strong>Sarah Wong</strong>. Waiting for approval (expires in 48h).</p>
                    </div>

                    <h3>Upcoming Tasks</h3>
                    <div class="card">
                        <ul class="task-list">
                            <li class="task-item">
                                <span style="color: var(--success);">✔</span>
                                <span>Complete Profile Bio</span>
                            </li>
                            <li class="task-item">
                                <span style="color: var(--gray);">○</span>
                                <span>Upload CV for Mentor Review</span>
                            </li>
                            <li class="task-item">
                                <span style="color: var(--gray);">○</span>
                                <span>Schedule First Meeting</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div>
                    <h3>Quick Stats</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div class="stat-box">
                            <div class="stat-number">1</div>
                            <div>Requests Used</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number">2</div>
                            <div>Events Attended</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional screens abbreviated for brevity -->
    <script>
        function showScreen(screenId) {
            document.querySelectorAll('.screen').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.mockup-nav button').forEach(el => el.classList.remove('active'));
            document.getElementById(screenId).classList.add('active');
            document.getElementById('btn-' + screenId).classList.add('active');
        }
    </script>
</body>
</html>
