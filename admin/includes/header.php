<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Pro'; ?> - MasTolongMas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#FF8C00', // Dark Orange
                            blue: '#1E3A8A',   // Dark Blue
                            yellow: '#FFD700', // Gold
                            dark: '#0F172A',   // Slate 900
                            darker: '#020617', // Slate 950
                        }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'blob': 'blob 7s infinite',
                        'pulse-glow': 'pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <script>
        // Force Dark Mode
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
        
        // Chart.js Global Defaults for Dark Mode
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = '#94a3b8'; // text-gray-400
            Chart.defaults.borderColor = '#334155'; // border-gray-700
            Chart.defaults.scale.grid.color = '#334155';
        }

        // Init AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                once: true,
                duration: 800,
                offset: 50
            });
        });
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .sidebar-active {
            background: linear-gradient(90deg, #FF8C00 0%, #FF6B00 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
        }
        /* Gen Z Mesh Gradient Background */
        .mesh-bg {
            background-color: #020617;
            background-image: 
                radial-gradient(at 0% 0%, hsla(213, 85%, 21%, 1) 0px, transparent 50%),
                radial-gradient(at 50% 0%, hsla(20, 100%, 50%, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, hsla(213, 85%, 21%, 1) 0px, transparent 50%);
        }
    </style>
    <?php if(isset($extraHead)) echo $extraHead; ?>
</head>
<body class="bg-gray-100 dark:bg-brand-darker text-gray-800 dark:text-gray-100 overflow-x-hidden mesh-bg transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <!-- Mobile Header -->
            <header class="md:hidden h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 z-20">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-person-running text-orange-500 text-2xl"></i>
                    <span class="font-bold text-lg text-blue-900 dark:text-white">Admin Pro</span>
                </div>
                <button onclick="$('aside').toggleClass('hidden flex fixed inset-y-0 left-0 z-50 w-64 shadow-2xl')" class="text-gray-500 dark:text-gray-300"><i class="fa-solid fa-bars text-xl"></i></button>
            </header>

            <!-- Gradient Background (Removed old flat gradient) -->
            <!-- <div class="absolute top-0 left-0 w-full h-64 bg-gradient-to-r from-blue-900 to-blue-800 z-0"></div> -->

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 dark:bg-transparent z-10 p-6 relative animate__animated animate__fadeIn">
