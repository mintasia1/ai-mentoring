<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../../guest/login.php");
    exit();
}

include '../../database/db.php';
$id_mentor_login = $_SESSION['user_id'];

// Check mentor profile completion
$query_profile = "SELECT username, email, nama_lengkap, no_telepon, alamat, bio, foto_profil FROM mentor WHERE id_mentor = ? ";
$stmt_profile = mysqli_prepare($conn, $query_profile);
mysqli_stmt_bind_param($stmt_profile, "s", $id_mentor_login);
mysqli_stmt_execute($stmt_profile);
$result_profile = mysqli_stmt_get_result($stmt_profile);
$mentor_data = mysqli_fetch_assoc($result_profile);
mysqli_stmt_close($stmt_profile);

// Calculate profile completion percentage
$profile_fields = ['nama_lengkap', 'no_telepon', 'alamat', 'bio', 'foto_profil'];
$filled_fields = 0;
$total_fields = count($profile_fields) + 2; // +2 for username and email (always filled)

foreach ($profile_fields as $field) {
    if (!empty($mentor_data[$field])) {
        $filled_fields++;
    }
}
$filled_fields += 2; // username and email are always filled
$profile_completion = ($filled_fields / $total_fields) * 100;
$is_profile_complete = $profile_completion >= 100;
?>
<! DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Mentor - ngeWIP ArtClass</title>
  <script>
  if (localStorage.getItem("theme") === "light") {
    document.documentElement.classList.add("light-mode");
  }
</script>
  <link rel="stylesheet" href="../../assets/css/mentor. css" />
  <style>
    .profile-alert {
      background:  linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }
    
    .profile-alert.complete {
      background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
      box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
    }
    
    .profile-alert-content {
      flex: 1;
    }
    
    .profile-alert h3 {
      margin: 0 0 10px 0;
      color: #fff;
      font-size: 1.2rem;
    }
    
    .profile-alert p {
      margin: 0;
      color: #fff;
      opacity: 0.9;
    }
    
    .profile-completion-bar {
      width: 100%;
      height: 8px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      margin-top: 10px;
      overflow: hidden;
    }
    
    .profile-completion-fill {
      height: 100%;
      background: #fff;
      border-radius: 10px;
      transition: width 0.5s ease;
    }
    
    .complete-profile-btn {
      background: #fff;
      color: #ff6b6b;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s;
      margin-left: 20px;
    }
    
    .complete-profile-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .profile-alert. complete . complete-profile-btn {
      color: #56ab2f;
    }
  </style>
</head>
<body>
  <div class="mentor-container">
    <aside class="sidebar">
      <img src="../../assets/images/logo.png" class="logo" alt="Logo" />
      <h2>Mentor Panel</h2>
      <nav>
  <a href="dashboard.php" class="<? = (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
  <a href="live-class.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'live-class.php') ? 'active' : ''; ?>">Live Class</a>
  <a href="lihat_murid.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'lihat_murid.php') ? 'active' : ''; ?>">Lihat Murid</a>
  <a href="kelola_materi_saya.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'kelola_materi_saya.php') ? 'active' : ''; ?>">Kelola Materi Saya</a>
  <a href="../../pages/galery_karya.php">Galery Karya</a>
  <a href="../../guest/index.php">Kembali ke Beranda</a>
  <a href="../../guest/logout.php">Logout</a>
</nav>
    </aside>
    <main class="main-content">
      <header>
        <button id="modeToggle" style="position: fixed; top: 10px; right: 10px; z-index: 200;">☀️/🌙</button>
        <h1>Selamat Datang, Mentor <? = htmlspecialchars($_SESSION['username'] ?? ''); ?>!</h1>
        <p>Berikut ringkasan aktivitas dan kelas kamu. </p>
      </header>
      
      <!-- Profile Completion Alert -->
      <? php if (! $is_profile_complete): ?>
      <div class="profile-alert">
        <div class="profile-alert-content">
          <h3>⚠️ Profil Belum Lengkap</h3>
          <p>Lengkapi profil Anda untuk meningkatkan kredibilitas dan kepercayaan siswa.</p>
          <div class="profile-completion-bar">
            <div class="profile-completion-fill" style="width:  <? = round($profile_completion) ?>%;"></div>
          </div>
          <p style="margin-top: 5px; font-size: 0.9rem;"><?= round($profile_completion) ?>% Lengkap</p>
        </div>
        <a href="complete_profile.php" class="complete-profile-btn">Lengkapi Profil</a>
      </div>
      <?php else: ?>
      <div class="profile-alert complete">
        <div class="profile-alert-content">
          <h3>✓ Profil Lengkap</h3>
          <p>Profil Anda sudah lengkap!  Anda dapat mengupdate profil kapan saja.</p>
          <div class="profile-completion-bar">
            <div class="profile-completion-fill" style="width: 100%;"></div>
          </div>
        </div>
        <a href="complete_profile.php" class="complete-profile-btn">Edit Profil</a>
      </div>
      <?php endif; ?>

      <section class="mentor-stats">
        <? php
        // Jumlah kelas yang diampu
        $query_kelas_ampu = "SELECT COUNT(*) AS total_kelas_ampu FROM kelas_seni WHERE id_mentor = ?";
        $stmt_kelas_ampu = mysqli_prepare($conn, $query_kelas_ampu);
        mysqli_stmt_bind_param($stmt_kelas_ampu, "s", $id_mentor_login);
        mysqli_stmt_execute($stmt_kelas_ampu);
        $result_kelas_ampu = mysqli_stmt_get_result($stmt_kelas_ampu);
        $total_kelas_ampu = ($result_kelas_ampu) ? mysqli_fetch_assoc($result_kelas_ampu)['total_kelas_ampu'] : 0;
        mysqli_stmt_close($stmt_kelas_ampu);

        // Jumlah siswa aktif di kelas yang diampu mentor
        $query_siswa_aktif = "SELECT COUNT(DISTINCT pk. id_member) AS total_siswa_aktif 
                             FROM pendaftaran_kursus pk
                             JOIN kelas_seni ks ON pk.id_kelas = ks.id_kelas
                             WHERE ks.id_mentor = ? AND pk.status_pendaftaran = 'Aktif'";
        $stmt_siswa_aktif = mysqli_prepare($conn, $query_siswa_aktif);
        mysqli_stmt_bind_param($stmt_siswa_aktif, "s", $id_mentor_login);
        mysqli_stmt_execute($stmt_siswa_aktif);
        $result_siswa_aktif = mysqli_stmt_get_result($stmt_siswa_aktif);
        $total_siswa_aktif = ($result_siswa_aktif) ? mysqli_fetch_assoc($result_siswa_aktif)['total_siswa_aktif'] : 0;
        mysqli_stmt_close($stmt_siswa_aktif);
        ?>
        <div class="stat-card">
          <h3><? = $total_kelas_ampu ? ></h3>
          <p>Kelas yang Diampu</p>
        </div>
        <div class="stat-card">
          <h3><?= $total_siswa_aktif ?></h3>
          <p>Siswa Aktif</p>
        </div>
        <div class="stat-card">
          <h3>0</h3>
          <p>Live Class Hari Ini</p>
        </div>
      </section>
    </main>
  </div>
<script src="../assets/js/script.js"></script>
</body>
</html>