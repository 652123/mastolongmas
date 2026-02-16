<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php?modal=login");
    exit;
}
include '../includes/config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$error = null;

if (!$id) {
    header("Location: portfolio.php");
    exit;
}

// Fetch Portfolio Data
$stmt = $conn->prepare("SELECT * FROM portfolio WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: portfolio.php?msg=" . urlencode("Data tidak ditemukan"));
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // Check if new image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/portfolio/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = basename($_FILES["image"]["name"]);
        $file_name = preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
        $target_file = $target_dir . time() . "_" . $file_name;
        $db_path = "uploads/portfolio/" . time() . "_" . $file_name;
        
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Validate
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false && in_array($imageFileType, ['jpg','jpeg','png','gif'])) {
             if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image
                if (file_exists("../" . $item['image_path'])) {
                    unlink("../" . $item['image_path']);
                }
                // Update with new image
                $update = $conn->prepare("UPDATE portfolio SET title=?, description=?, image_path=? WHERE id=?");
                $update->bind_param("sssi", $title, $description, $db_path, $id);
             } else {
                 $error = "Gagal upload gambar baru.";
             }
        } else {
            $error = "File harus gambar (JPG/PNG/GIF).";
        }
    } else {
        // Update without changing image
        $update = $conn->prepare("UPDATE portfolio SET title=?, description=? WHERE id=?");
        $update->bind_param("ssi", $title, $description, $id);
    }

    if (!isset($error)) {
        if ($update->execute()) {
            header("Location: portfolio.php?status=success");
            exit;
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

$pageTitle = 'Edit Portofolio';
include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 text-white" data-aos="fade-down">
    <div>
        <h2 class="text-3xl font-extrabold mb-1">✏️ Edit Portofolio</h2>
        <p class="opacity-80">Ubah detail kegiatan</p>
    </div>
    <a href="portfolio.php" class="mt-4 md:mt-0 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-bold backdrop-blur-md transition border border-white/20">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <?php if($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md" role="alert">
            <p class="font-bold">Error!</p>
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8" data-aos="fade-up">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Judul Kegiatan</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 font-bold dark:text-white" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Deskripsi</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm dark:text-white"><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Ganti Foto (Opsional)</label>
                <div class="flex items-center gap-4">
                    <img src="../<?php echo $item['image_path']; ?>" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                    <input type="file" name="image" class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:border-blue-500 text-sm dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <p class="text-xs text-gray-400 mt-1">*Biarkan kosong jika tidak ingin mengubah foto.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform hover:scale-[1.02] active:scale-95 text-lg">
                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
            </button>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

