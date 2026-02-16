<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // File Upload Handling
    $target_dir = "../uploads/portfolio/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($_FILES["image"]["name"]);
    // Sanitize filename
    $file_name = preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
    $target_file = $target_dir . time() . "_" . $file_name;
    $db_path = "uploads/portfolio/" . time() . "_" . $file_name;
    
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check validity
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check === false) {
        header("Location: portfolio.php?status=error&msg=" . urlencode("File bukan gambar."));
        exit;
    }

    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        header("Location: portfolio.php?status=error&msg=" . urlencode("Hanya file JPG, JPEG, PNG & GIF."));
        exit;
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO portfolio (title, description, image_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $description, $db_path);
        
        if ($stmt->execute()) {
            header("Location: portfolio.php?status=success");
            exit;
        } else {
            header("Location: portfolio.php?status=error&msg=" . urlencode("Database Error: " . $stmt->error));
            exit;
        }
    } else {
        header("Location: portfolio.php?status=error&msg=" . urlencode("Gagal upload gambar."));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kegiatan - Admin MasTolongMas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Tambah Kegiatan Baru</h2>
            <a href="portfolio.php" class="text-gray-600 hover:text-blue-600">Kembali</a>
        </div>

        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Judul Kegiatan</label>
                <input type="text" name="title" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Contoh: Antar Katering">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Singkat</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Ceritakan sedikit tentang kegiatan ini..."></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Upload Foto Bukti</label>
                <input type="file" name="image" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required accept="image/*">
                <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG. Maks 2MB.</p>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                Simpan Kegiatan
            </button>
        </form>
    </div>

</body>
</html>

