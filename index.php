<?php
// Start session
session_start();

// Include database configuration
include 'config.php';

// Initialize cart in session if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch user details if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user = null;
if ($user_id) {
    $query_user = "SELECT fullname FROM users WHERE user_id = '$user_id'";
    $result_user = mysqli_query($conn, $query_user);
    $user = mysqli_fetch_assoc($result_user);
}

// Fetch categories for navigation and filters
$query_categories = "SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$result_categories = mysqli_query($conn, $query_categories);

// Handle filter and sort (only when form is submitted)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at_desc';
$limit = 12; // Initial products to show
$offset = 0;

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

// Fetch products
$query_products = "SELECT product_id, name, description, price, main_image_url, amazon_affiliate_link, rating, review_count 
                  FROM products 
                  $where 
                  $sort_sql 
                  LIMIT $limit OFFSET $offset";
$result_products = mysqli_query($conn, $query_products);

$total_products_query = "SELECT COUNT(*) as total FROM products $where";
$total_result = mysqli_query($conn, $total_products_query);
$total_products = mysqli_fetch_assoc($total_result)['total'];

// Fetch featured products
$query_featured = "SELECT product_id, name, description, price, main_image_url, amazon_affiliate_link, rating, review_count 
                   FROM products 
                   WHERE is_featured = TRUE 
                   ORDER BY created_at DESC 
                   LIMIT 4";
$result_featured = mysqli_query($conn, $query_featured);

// Fetch special offers
$query_offers = "SELECT offer_id, title, description, image_url, discount_percentage, start_time, end_time 
                 FROM special_offers 
                 ORDER BY start_time DESC 
                 LIMIT 3";
$result_offers = mysqli_query($conn, $query_offers);

// Fetch blog posts
$query_blogs = "SELECT post_id, title, description, image_url 
                FROM blog_posts 
                ORDER BY created_at DESC 
                LIMIT 3";
$result_blogs = mysqli_query($conn, $query_blogs);

// Fetch reviews
$query_reviews = "SELECT reviewer_name, rating, comment 
                  FROM reviews 
                  WHERE rating >= 4 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$result_reviews = mysqli_query($conn, $query_reviews);

// Handle wishlist and cart operations
if (isset($_POST['toggle_wishlist']) && $user_id) {
    $product_id = (int)$_POST['product_id'];
    $check_wishlist = "SELECT wishlist_id FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $result_check = mysqli_query($conn, $check_wishlist);
    if (mysqli_num_rows($result_check) > 0) {
        $delete_wishlist = "DELETE FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
        mysqli_query($conn, $delete_wishlist);
    } else {
        $insert_wishlist = "INSERT INTO wishlist (user_id, product_id, added_at) VALUES ('$user_id', '$product_id', NOW())";
        mysqli_query($conn, $insert_wishlist);
    }
    header("Location: index.php?" . http_build_query($_GET));
    exit();
}

if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    if (!in_array($product_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $product_id;
    }
    header("Location: index.php?" . http_build_query($_GET));
    exit();
}

if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['remove_from_cart'];
    $key = array_search($product_id, $_SESSION['cart']);
    if ($key !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header("Location: index.php?" . http_build_query($_GET));
    exit();
}

if (isset($_GET['logout']) && $user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$cart_items = [];
if (!empty($_SESSION['cart'])) {
    $cart_ids = implode(',', array_map('intval', $_SESSION['cart']));
    $cart_query = "SELECT product_id, name, price, main_image_url FROM products WHERE product_id IN ($cart_ids)";
    $cart_result = mysqli_query($conn, $cart_query);
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $cart_items[] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiba - Your Smart Shopping Companion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5', // Indigo
                        secondary: '#F59E0B', // Amber
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <style>
        .search-results {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 50;
        }
        .search-container:hover .search-results {
            display: block;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 16px;
            width: 90%;
            max-width: 640px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .product-card img {
            transition: transform 0.3s ease;
        }
        .product-card:hover img {
            transform: scale(1.05);
        }
        @media (max-width: 640px) {
            .search-container {
                width: 100%;
                margin: 0 8px;
            }
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="font-['Pacifico'] text-2xl text-primary">Hiba</a>
                <div class="flex-1 max-w-xl mx-6 search-container">
                    <form method="GET" action="index.php" class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm" 
                               placeholder="Search for products...">
                        <i class="ri-search-line text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                        <div class="search-results">
                            <?php
                            $search_query = "SELECT product_id, name, price, main_image_url FROM products " . ($search ? "WHERE name LIKE '%$search%' OR description LIKE '%$search%'" : "") . " LIMIT 5";
                            $search_results = mysqli_query($conn, $search_query);
                            while ($item = mysqli_fetch_assoc($search_results)) {
                                echo '<div class="flex items-center gap-3 p-3 hover:bg-gray-50">';
                                echo '<img src="' . htmlspecialchars($item['main_image_url']) . '" class="w-12 h-12 object-cover rounded" alt="' . htmlspecialchars($item['name']) . '">';
                                echo '<div>';
                                echo '<p class="font-medium text-gray-900">' . htmlspecialchars($item['name']) . '</p>';
                                echo '<p class="text-sm text-gray-500">$' . number_format($item['price'], 2) . '</p>';
                                echo '</div></div>';
                            }
                            ?>
                        </div>
                    </form>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($user_id): ?>
                        <span class="text-gray-700 font-medium hidden sm:inline"><?php echo htmlspecialchars($user['fullname']); ?></span>
                        <a href="index.php?logout=true" class="text-primary hover:text-primary/80"><i class="ri-logout-box-r-line text-xl"></i></a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-primary"><i class="ri-user-line text-xl"></i></a>
                    <?php endif; ?>
                    <a href="cart.php" class="relative text-gray-600 hover:text-primary">
                        <i class="ri-shopping-cart-line text-xl"></i>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center"><?php echo count($_SESSION['cart']); ?></span>
                    </a>
                </div>
            </div>
            <nav class="py-3 border-t border-gray-100 nav-links">
                <ul class="flex flex-wrap gap-6 justify-center">
                    <?php while ($category = mysqli_fetch_assoc($result_categories)): ?>
                        <li><a href="?category=<?php echo $category['category_id']; ?>" class="text-gray-600 hover:text-primary text-sm font-medium"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php endwhile; mysqli_data_seek($result_categories, 0); ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Cart Modal -->
    <div id="cart-modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-primary">Your Cart</h2>
                <button onclick="document.getElementById('cart-modal').style.display = 'none';" class="text-gray-500 hover:text-gray-700"><i class="ri-close-line text-xl"></i></button>
            </div>
            <?php if (empty($cart_items)): ?>
                <p class="text-gray-600 text-center py-6">Your cart is empty.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="flex items-center gap-4 p-3 border-b border-gray-200">
                            <img src="<?php echo htmlspecialchars($item['main_image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 object-cover rounded">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p class="text-sm text-gray-600">$<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <form method="POST" action="index.php?<?php echo http_build_query($_GET); ?>">
                                <input type="hidden" name="remove_from_cart" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700"><i class="ri-delete-bin-line text-xl"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <a href="cart.php" class="block w-full mt-6 bg-primary text-white px-4 py-2.5 rounded-button text-center font-medium hover:bg-primary/90">View Cart</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <!-- Hero Banner -->
        <section class="pt-20 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">Discover Amazing Deals with Hiba</h1>
                        <p class="text-lg text-gray-600 mt-4 mb-6">Your smart shopping companion for curated selections and unbeatable prices.</p>
                        <div class="flex gap-4">
                            <button class="bg-primary text-white px-6 py-3 rounded-button font-medium hover:bg-primary/90">Shop Now</button>
                            <button class="border border-primary text-primary px-6 py-3 rounded-button font-medium hover:bg-primary/10">View Deals</button>
                        </div>
                    </div>
                    <div>
                        <img src="https://public.readdy.ai/ai/img_res/68c130f097c3ab2f1ee227dfd1178329.jpg" alt="Featured Products" class="rounded-xl shadow-lg w-full object-cover">
                    </div>
                </div>
            </div>
        </section>

        <!-- Filter Section -->
        <section class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 bg-white p-6 rounded-lg shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Categories</h3>
                        <?php while ($category = mysqli_fetch_assoc($result_categories)): ?>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="categories[]" value="<?php echo $category['category_id']; ?>" id="cat-<?php echo $category['category_id']; ?>" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                <label for="cat-<?php echo $category['category_id']; ?>" class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($category['name']); ?></label>
                            </div>
                        <?php endwhile; mysqli_data_seek($result_categories, 0); ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Sort By</h3>
                        <select name="sort" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="created_at_desc" <?php echo $sort == 'created_at_desc' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="reviews_desc" <?php echo $sort == 'reviews_desc' ? 'selected' : ''; ?>>Most Reviewed</option>
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z-A</option>
                        </select>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Price Range</h3>
                        <div class="flex gap-2">
                            <input type="number" name="price_min" placeholder="Min" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" min="0" step="0.01">
                            <input type="number" name="price_max" placeholder="Max" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" min="0" step="0.01">
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Minimum Rating</h3>
                        <select name="rating" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="0">Any</option>
                            <option value="3">3+ Stars</option>
                            <option value="4">4+ Stars</option>
                            <option value="4.5">4.5+ Stars</option>
                        </select>
                        <button type="submit" name="apply_filters" class="w-full mt-4 bg-primary text-white px-4 py-2.5 rounded-button hover:bg-primary/90 font-medium">Apply Filters</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- All Products Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">All Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="products-container">
                    <?php while ($product = mysqli_fetch_assoc($result_products)): ?>
                        <div class="bg-white rounded-lg shadow-sm product-card overflow-hidden">
                            <a href="view_product.php?product_id=<?php echo $product['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['main_image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover">
                            </a>
                            <div class="p-4">
                                <h3 class="text-base font-semibold text-gray-900 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="flex text-yellow-400">
                                        <?php
                                        $rating = $product['rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($rating >= $i) {
                                                echo '<i class="ri-star-fill text-sm"></i>';
                                            } elseif ($rating >= $i - 0.5) {
                                                echo '<i class="ri-star-half-fill text-sm"></i>';
                                            } else {
                                                echo '<i class="ri-star-line text-sm"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-xs text-gray-500">(<?php echo $product['review_count']; ?>)</span>
                                </div>
                                <p class="text-lg font-bold text-gray-900 mt-2">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="mt-4 flex gap-2">
                                    <a href="<?php echo htmlspecialchars($product['amazon_affiliate_link']); ?>" target="_blank" class="flex-1 bg-primary text-white px-3 py-2 rounded-button text-sm font-medium hover:bg-primary/90 text-center">Shop Now</a>
                                    <form method="POST" action="index.php?<?php echo http_build_query($_GET); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <?php if ($user_id): ?>
                                            <button type="submit" name="toggle_wishlist" class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-button hover:bg-gray-50">
                                                <?php
                                                $in_wishlist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '$user_id' AND product_id = '{$product['product_id']}'")) > 0;
                                                echo $in_wishlist ? '<i class="ri-heart-fill text-primary"></i>' : '<i class="ri-heart-line text-gray-500"></i>';
                                                ?>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="add_to_cart" class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-button hover:bg-gray-50">
                                            <i class="ri-shopping-cart-line text-gray-500"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php if ($total_products > $limit): ?>
                    <div class="mt-10 text-center">
                        <button id="view-more" class="bg-primary text-white px-6 py-3 rounded-button font-medium hover:bg-primary/90">Load More</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Featured Products Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Featured Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php while ($product = mysqli_fetch_assoc($result_featured)): ?>
                        <div class="bg-white rounded-lg shadow-sm product-card overflow-hidden">
                            <a href="view_product.php?product_id=<?php echo $product['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['main_image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover">
                            </a>
                            <div class="p-4">
                                <h3 class="text-base font-semibold text-gray-900 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="flex text-yellow-400">
                                        <?php
                                        $rating = $product['rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($rating >= $i) {
                                                echo '<i class="ri-star-fill text-sm"></i>';
                                            } elseif ($rating >= $i - 0.5) {
                                                echo '<i class="ri-star-half-fill text-sm"></i>';
                                            } else {
                                                echo '<i class="ri-star-line text-sm"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-xs text-gray-500">(<?php echo $product['review_count']; ?>)</span>
                                </div>
                                <p class="text-lg font-bold text-gray-900 mt-2">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="mt-4 flex gap-2">
                                    <a href="<?php echo htmlspecialchars($product['amazon_affiliate_link']); ?>" target="_blank" class="flex-1 bg-primary text-white px-3 py-2 rounded-button text-sm font-medium hover:bg-primary/90 text-center">Shop Now</a>
                                    <form method="POST" action="index.php?<?php echo http_build_query($_GET); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <?php if ($user_id): ?>
                                            <button type="submit" name="toggle_wishlist" class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-button hover:bg-gray-50">
                                                <?php
                                                $in_wishlist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '$user_id' AND product_id = '{$product['product_id']}'")) > 0;
                                                echo $in_wishlist ? '<i class="ri-heart-fill text-primary"></i>' : '<i class="ri-heart-line text-gray-500"></i>';
                                                ?>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="add_to_cart" class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-button hover:bg-gray-50">
                                            <i class="ri-shopping-cart-line text-gray-500"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <!-- Special Offers Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Special Offers</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php while ($offer = mysqli_fetch_assoc($result_offers)): ?>
                        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($offer['title']); ?></h3>
                                <div class="text-red-500 text-sm font-medium">
                                    <?php
                                    $end_time = strtotime($offer['end_time']);
                                    $now = time();
                                    $diff = $end_time - $now;
                                    if ($diff > 0) {
                                        $days = floor($diff / (3600 * 24));
                                        $hours = floor(($diff % (3600 * 24)) / 3600);
                                        echo "Ends in $days days, $hours hrs";
                                    } else {
                                        echo "Expired";
                                    }
                                    ?>
                                </div>
                            </div>
                            <img src="<?php echo htmlspecialchars($offer['image_url']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($offer['description']); ?></p>
                            <button class="w-full bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-primary/90">Shop Offer</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <!-- Popular Categories Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Popular Categories</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-6">
                    <?php while ($category = mysqli_fetch_assoc($result_categories)): ?>
                        <a href="?category=<?php echo $category['category_id']; ?>" class="group">
                            <div class="bg-gray-100 rounded-lg overflow-hidden">
                                <?php
                                $cat_img_query = "SELECT main_image_url FROM products WHERE category_id = {$category['category_id']} LIMIT 1";
                                $cat_img_result = mysqli_query($conn, $cat_img_query);
                                $cat_img = mysqli_fetch_assoc($cat_img_result)['main_image_url'] ?? 'https://via.placeholder.com/150';
                                ?>
                                <img src="<?php echo htmlspecialchars($cat_img); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <h3 class="mt-2 text-center text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <!-- Blog Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Latest from Our Blog</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php while ($blog = mysqli_fetch_assoc($result_blogs)): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="w-full h-48 object-cover">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($blog['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($blog['description']); ?></p>
                                <a href="#" class="text-primary font-medium hover:text-primary/80">Read More →</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">What Our Customers Say</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php while ($review = mysqli_fetch_assoc($result_reviews)): ?>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center mb-4">
                                <img src="https://public.readdy.ai/ai/img_res/5899cf2c82f37e75a61a3f01bf25960d.jpg" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" class="w-12 h-12 rounded-full">
                                <div class="ml-4">
                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                    <div class="flex text-yellow-400">
                                        <?php
                                        $rating = $review['rating'];
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
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="bg-primary py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-white mb-4">Subscribe to Our Newsletter</h2>
                    <p class="text-gray-200 mb-6">Get the latest deals and updates delivered to your inbox</p>
                    <form method="POST" action="subscribe.php" class="max-w-md mx-auto">
                        <div class="flex">
                            <input type="email" name="email" class="flex-1 px-4 py-2 rounded-l-lg focus:outline-none border border-gray-300" placeholder="Enter your email" required>
                            <button type="submit" class="bg-secondary text-gray-900 px-6 py-2 rounded-r-lg font-medium hover:bg-secondary/90">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="font-['Pacifico'] text-2xl text-white mb-4">Hiba</h3>
                    <p class="text-sm">Your smart shopping companion for the best deals on Amazon.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">About Us</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Blog</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Categories</h3>
                    <ul class="space-y-2 text-sm">
                        <?php 
                        mysqli_data_seek($result_categories, 0);
                        $i = 0;
                        while ($category = mysqli_fetch_assoc($result_categories) && $i < 4): ?>
                            <li><a href="?category=<?php echo $category['category_id']; ?>" class="hover:text-white"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php $i++; ?>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Connect With Us</h3>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-facebook-fill text-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-twitter-fill text-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-instagram-fill text-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-pinterest-fill text-lg"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>As an Amazon Associate, we earn from qualifying purchases.</p>
                <p class="mt-1">© 2025 Hiba. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        let offset = <?php echo $limit; ?>;
        const limit = <?php echo $limit; ?>;
        const totalProducts = <?php echo $total_products; ?>;
        
        document.getElementById('view-more')?.addEventListener('click', function() {
            fetch(`load_more.php?offset=${offset}&limit=${limit}&<?php echo http_build_query($_GET); ?>`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('products-container').insertAdjacentHTML('beforeend', data);
                    offset += limit;
                    if (offset >= totalProducts) {
                        document.getElementById('view-more').style.display = 'none';
                    }
                });
        });
    </script>
    <?php mysqli_close($conn); ?>
</body>
</html>