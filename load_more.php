<?php
include 'config.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at_desc';

$where_clauses = [];
$sort_sql = "ORDER BY created_at DESC";
if (isset($_GET['apply_filters'])) {
    $category_filters = isset($_GET['categories']) ? array_map('intval', $_GET['categories']) : [];
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 999999;
    $rating_filter = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;

    if ($search) {
        $where_clauses[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
    }
    if (!empty($category_filters)) {
        $where_clauses[] = "category_id IN (" . implode(',', $category_filters) . ")";
    }
    if ($price_min > 0) {
        $where_clauses[] = "price >= $price_min";
    }
    if ($price_max < 999999) {
        $where_clauses[] = "price <= $price_max";
    }
    if ($rating_filter > 0) {
        $where_clauses[] = "rating >= $rating_filter";
    }
    $sort_sql = match ($sort) {
        'price_asc' => "ORDER BY price ASC",
        'price_desc' => "ORDER BY price DESC",
        'rating_desc' => "ORDER BY rating DESC",
        'reviews_desc' => "ORDER BY review_count DESC",
        'name_asc' => "ORDER BY name ASC",
        'name_desc' => "ORDER BY name DESC",
        default => "ORDER BY created_at DESC"
    };
}
$where = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "SELECT product_id, name, description, price, main_image_url, amazon_affiliate_link, rating, review_count 
          FROM products 
          $where 
          $sort_sql 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

while ($product = mysqli_fetch_assoc($result)): ?>
    <div class="product-card bg-white rounded-lg shadow-sm">
        <div class="aspect-w-1 aspect-h-1 rounded-lg overflow-hidden">
            <a href="view_product.php?product_id=<?php echo $product['product_id']; ?>">
                <img src="<?php echo htmlspecialchars($product['main_image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full">
            </a>
        </div>
        <div class="mt-4 px-4 flex flex-col flex-1">
            <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h3>
            <p class="text-sm text-gray-500 mb-2 flex-1"><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="flex items-center space-x-2 mb-2">
                <div class="flex text-yellow-400">
                    <?php
                    $rating = $product['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        if ($rating >= $i) {
                            echo '<i class="ri-star-fill"></i>';
                        } elseif ($rating >= $i - 0.5) {
                            echo '<i class="ri-star-half-fill"></i>';
                        } else {
                            echo '<i class="ri-star-line"></i>';
                        }
                    }
                    ?>
                </div>
                <span class="text-sm text-gray-500">(<?php echo $product['review_count']; ?>)</span>
            </div>
            <p class="text-lg font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></p>
            <div class="mt-4 flex space-x-2">
                <a href="<?php echo htmlspecialchars($product['amazon_affiliate_link']); ?>" target="_blank" class="flex-1 bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-primary/90 text-center">Shop Now</a>
                <form method="POST" action="index.php?<?php echo http_build_query($_GET); ?>">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="submit" name="toggle_wishlist" class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-button hover:bg-gray-50">
                            <?php
                            $in_wishlist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '{$_SESSION['user_id']}' AND product_id = '{$product['product_id']}'")) > 0;
                            echo $in_wishlist ? '<i class="ri-heart-fill text-primary"></i>' : '<i class="ri-heart-line"></i>';
                            ?>
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="add_to_cart" class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-button hover:bg-gray-50">
                        <i class="ri-shopping-cart-line"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endwhile;

mysqli_close($conn);
?>