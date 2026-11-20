<?php
require 'db.php';

if (isset($_POST['query'])) {
    $search = $conn->real_escape_string($_POST['query']);
    
    // Bulletproof SQL Query
    $sql = "SELECT * FROM product WHERE name LIKE '%$search%' ORDER BY prodID DESC LIMIT 5";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            // Smart Image Logic
            $imgRaw = !empty($row['image']) ? $row['image'] : 'images/item-1.jpg';
            
            // Clean HTML with CSS Classes instead of inline styles
            echo '<a href="product-detail.php?id=' . $row['prodID'] . '" class="search-dropdown-item">';
            echo '<img src="' . htmlspecialchars($imgRaw) . '" class="search-dropdown-img">';
            echo '<span class="search-dropdown-text">' . htmlspecialchars($row['name']) . '<br>';
            echo '<small class="search-dropdown-price">RM ' . number_format($row['price'], 2) . '</small></span>';
            echo '</a>';
        }
    } else {
        echo '<div class="search-dropdown-empty">No gear found matching \'' . htmlspecialchars($search) . '\'</div>';
    }
    
    // Modern Advanced Search Link
    echo '<a href="advanced_search.php?q=' . urlencode($search) . '" class="search-dropdown-viewall">';
    echo '<span style="display:flex; align-items:center; gap:8px;">';
    echo '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
    echo 'View All Results</span>';
    echo '<span style="font-size:16px;">&rarr;</span>';
    echo '</a>';
}
?>