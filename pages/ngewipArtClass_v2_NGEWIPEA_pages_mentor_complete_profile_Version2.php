<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../../guest/login.php");
    exit();
}

include '../../database/db.php';
$id_mentor_login = $_SESSION['user_id'];
$pesan = '';
$pesan_type = '';

// Fetch current mentor data
$query_mentor = "SELECT * FROM mentor WHERE id_mentor = ? ";
$stmt_mentor = mysqli_prepare($conn, $query_mentor);
mysqli_stmt_bind_param($stmt_mentor, "s", $id_mentor_login);
mysqli_stmt_execute($stmt_mentor);
$result_mentor = mysqli_stmt_get_result($stmt_mentor);
$mentor_data = mysqli_fetch_assoc($result_mentor);
mysqli_stmt_close($stmt_mentor);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $no_telepon = mysqli_real_escape_string($conn, trim($_POST['no_telepon']));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $foto_profil_path = $mentor_data['foto_profil'] ?? '';

    // Handle photo upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['foto_profil']['type'], $allowed_types) && $_FILES['foto_profil']['size'] <= $max_size) {
            $upload_dir = "../../assets/mentor_profiles/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $new_filename = 'mentor_' . $id_mentor_login . '_' .  time() . '.' . $file_extension;
            $upload_path = $upload_dir .  $new_filename;

            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
                // Delete old photo if exists
                if (! empty($mentor_data['foto_profil']) && file_exists($mentor_data['foto_profil'])) {
                    unlink($mentor_data['foto_profil']);
                }
                $foto_profil_path = $upload_path;
            } else {
                $pesan = "Gagal mengupload foto profil. ";
                $pesan_type = "error";
            }
        } else {
            $pesan = "File tidak valid.  Harus berupa gambar (JPG, PNG, GIF) dan maksimal 5MB.";
            $pesan_type = "error";
        }
    }

    // Update profile in database
    if (empty($pesan)) {
        $query_update = "UPDATE mentor SET 
                        nama_lengkap = ?, 
                        no_telepon = ?, 
                        alamat = ?, 
                        bio = ?, 
                        foto_profil = ?  
                        WHERE id_mentor = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ssssss", $nama_lengkap, $no_telepon, $alamat, $bio, $foto_profil_path, $id_mentor_login);

        if (mysqli_stmt_execute($stmt_update)) {
            $pesan = "Profil berhasil diperbarui!";
            $pesan_type = "success";
            
            // Refresh mentor data
            $stmt_mentor = mysqli_prepare($conn, $query_mentor);
            mysqli_stmt_bind_param($stmt_mentor, "s", $id_mentor_login);
            mysqli_stmt_execute($stmt_mentor);
            $result_mentor = mysqli_stmt_get_result($stmt_mentor);
            $mentor_data = mysqli_fetch_assoc($result_mentor);
            mysqli_stmt_close($stmt_mentor);
        } else {
            $pesan = "Gagal memperbarui profil:  " . mysqli_error($conn);
            $pesan_type = "error";
        }
        mysqli_stmt_close($stmt_update);
    }
}
?>
<! DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lengkapi Profil - Mentor ngeWIP ArtClass</title>
  <script>
  if (localStorage.getItem("theme") === "light") {
    document.documentElement.classList.add("light-mode");
  }
  </script>
  <link rel="stylesheet" href="../../assets/css/mentor.css" />
  <style>
    .profile-form-container {
      background: var(--card-bg);
      padding: 30px;
      border-radius:  15px;
      margin-top: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    . form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary-color);
      font-weight: 600;
    }
    
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid var(--card-border);
      border-radius: 8px;
      background: var(--bg-color);
      color: var(--text-color);
      font-family: 'Segoe UI', sans-serif;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--primary-color);
    }
    
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    . form-group input[type="file"] {
      padding: 8px;
    }
    
    .profile-photo-preview {
      margin-top: 10px;
      text-align: center;
    }
    
    .profile-photo-preview img {
      max-width: 200px;
      max-height:  200px;
      border-radius: 10px;
      border: 3px solid var(--primary-color);
    }
    
    .submit-btn {
      background: var(--primary-color);
      color: var(--bg-color);
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
      box-shadow: 0 4px 15px rgba(0, 255, 204, 0.3);
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
      margin-bottom:  20px;
      font-weight: 600;
    }
    
    .message. success {
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
  </style>
</head>
<body>
  <div class="mentor-container">
    <aside class="sidebar">
      <img src="../../assets/images/logo.png" class="logo" alt="Logo" />
      <h2>Mentor Panel</h2>
      <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="live-class.php">Live Class</a>
        <a href="lihat_murid.php">Lihat Murid</a>
        <a href="kelola_materi_saya.php">Kelola Materi Saya</a>
        <a href="../../pages/galery_karya.php">Galery Karya</a>
        <a href="../../guest/index.php">Kembali ke Beranda</a>
        <a href="../../guest/logout.php">Logout</a>
      </nav>
    </aside>
    
    <main class="main-content">
      <header>
        <button id="modeToggle" style="position: fixed; top: 10px; right: 10px; z-index: 200;">☀️/🌙</button>
        <h1>Lengkapi Profil Mentor</h1>
        <p>Isi data profil Anda untuk meningkatkan kredibilitas dan kepercayaan siswa.</p>
      </header>
      
      <? php if (!empty($pesan)): ?>
      <div class="message <?= $pesan_type ?>">
        <? = $pesan ?>
      </div>
      <?php endif; ?>
      
      <div class="profile-form-container">
        <form method="POST" enctype="multipart/form-data" id="profileForm">
          <div class="form-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" value="<?= htmlspecialchars($mentor_data['username']) ?>" disabled>
            <p class="info-text">Username tidak dapat diubah</p>
          </div>
          
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" value="<?= htmlspecialchars($mentor_data['email']) ?>" disabled>
            <p class="info-text">Email tidak dapat diubah</p>
          </div>
          
          <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
            <input 
              type="text" 
              id="nama_lengkap" 
              name="nama_lengkap" 
              value="<?= htmlspecialchars($mentor_data['nama_lengkap'] ?? '') ?>" 
              required
              placeholder="Masukkan nama lengkap Anda">
          </div>
          
          <div class="form-group">
            <label for="no_telepon">Nomor Telepon <span class="required">*</span></label>
            <input 
              type="tel" 
              id="no_telepon" 
              name="no_telepon" 
              value="<? = htmlspecialchars($mentor_data['no_telepon'] ?? '') ?>" 
              required
              placeholder="Contoh: 081234567890">
          </div>
          
          <div class="form-group">
            <label for="alamat">Alamat <span class="required">*</span></label>
            <textarea 
              id="alamat" 
              name="alamat" 
              required
              placeholder="Masukkan alamat lengkap Anda"><? = htmlspecialchars($mentor_data['alamat'] ?? '') ?></textarea>
          </div>
          
          <div class="form-group">
            <label for="bio">Bio/Deskripsi Diri <span class="required">*</span></label>
            <textarea 
              id="bio" 
              name="bio" 
              required
              placeholder="Ceritakan tentang diri Anda, pengalaman mengajar, keahlian, dll."><?= htmlspecialchars($mentor_data['bio'] ??  '') ?></textarea>
            <p class="info-text">Bio akan ditampilkan kepada siswa untuk mengetahui latar belakang Anda</p>
          </div>
          
          <div class="form-group">
            <label for="foto_profil">Foto Profil</label>
            <input 
              type="file" 
              id="foto_profil" 
              name="foto_profil" 
              accept="image/jpeg,image/png,image/jpg,image/gif"
              onchange="previewImage(this)">
            <p class="info-text">Format:  JPG, PNG, GIF.  Maksimal 5MB</p>
            
            <div class="profile-photo-preview" id="photoPreview">
              <? php if (! empty($mentor_data['foto_profil']) && file_exists($mentor_data['foto_profil'])): ?>
                <img src="<? = htmlspecialchars($mentor_data['foto_profil']) ?>" alt="Foto Profil">
              <?php endif; ?>
            </div>
          </div>
          
          <div style="margin-top: 30px;">
            <a href="dashboard.php" class="back-btn">Kembali</a>
            <button type="submit" name="update_profile" class="submit-btn">💾 Simpan Profil</button>
          </div>
        </form>
      </div>
    </main>
  </div>
  
  <script src="../assets/js/script.js"></script>
  <script>
    function previewImage(input) {
      const preview = document.getElementById('photoPreview');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview Foto Profil">';
        }
        
        reader.readAsDataURL(input.files[0]);
      }
    }
    
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
      const nama = document.getElementById('nama_lengkap').value.trim();
      const telepon = document.getElementById('no_telepon').value.trim();
      const alamat = document.getElementById('alamat').value.trim();
      const bio = document.getElementById('bio').value.trim();
      
      if (! nama || !telepon || !alamat || !bio) {
        e.preventDefault();
        alert('Semua field yang bertanda * wajib diisi! ');
        return false;
      }
      
      // Validate phone number format
      const phoneRegex = /^[0-9]{10,15}$/;
      if (!phoneRegex.test(telepon)) {
        e.preventDefault();
        alert('Nomor telepon tidak valid!  Masukkan 10-15 digit angka.');
        return false;
      }
      
      return true;
    });
  </script>
</body>
</html>