<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | MasTolongMas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="text-center max-w-lg">
        <div class="relative inline-block mb-8">
            <i class="fa-solid fa-person-running text-9xl text-gray-200 animate-pulse"></i>
            <i class="fa-solid fa-triangle-exclamation text-4xl text-brand-orange absolute -top-2 -right-2"></i>
        </div>
        
        <h1 class="text-6xl font-bold text-gray-800 mb-2">404</h1>
        <h2 class="text-2xl font-bold text-gray-600 mb-4">Waduh, Halaman Nyasar Bos!</h2>
        
        <p class="text-gray-500 mb-8">
            Halaman yang kamu cari mungkin lagi otw, atau emang gak ada. 
            Mending balik ke jalan yang benar aja yuk.
        </p>

        <a href="index.php" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:scale-105 inline-flex items-center gap-2">
            <i class="fa-solid fa-house"></i> Balik ke Beranda
        </a>
    </div>

</body>
</html>
