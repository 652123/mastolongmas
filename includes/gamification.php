<?php
// Gamification Logic

function getRank($points) {
    if ($points >= 1000) return 'Dewa';
    if ($points >= 500) return 'Sultan';
    if ($points >= 200) return 'Juragan';
    if ($points >= 50) return 'Warga Senior';
    return 'Warga Biasa';
}

function getRankBadge($rank) {
    switch ($rank) {
        case 'Dewa': return '<span class="px-3 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold rounded-full shadow-lg border border-purple-400 animate-pulse"><i class="fa-solid fa-crown"></i> DEWA</span>';
        case 'Sultan': return '<span class="px-3 py-1 bg-gradient-to-r from-yellow-500 to-amber-600 text-white text-xs font-bold rounded-full shadow-md border border-yellow-300"><i class="fa-solid fa-chess-king"></i> SULTAN</span>';
        case 'Juragan': return '<span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full shadow-sm"><i class="fa-solid fa-briefcase"></i> JURAGAN</span>';
        case 'Warga Senior': return '<span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow-sm"><i class="fa-solid fa-user-check"></i> WARGA SENIOR</span>';
        default: return '<span class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded-full shadow-sm"><i class="fa-solid fa-user"></i> WARGA BIASA</span>';
    }
}

function addPoints($conn, $user_id, $points_to_add) {
    // 1. Get current points
    $stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_points = $row['points'] + $points_to_add;
        
        // 2. Determine new rank
        $new_rank = getRank($new_points);
        
        // 3. Update User
        $update = $conn->prepare("UPDATE users SET points = ?, rank_tier = ? WHERE id = ?");
        $update->bind_param("isi", $new_points, $new_rank, $user_id);
        $update->execute();
        
        return true;
    }
    return false;
}
?>
