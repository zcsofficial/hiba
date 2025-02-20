<?php
session_start();
include 'config.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$query_product = "SELECT p.*, c.name AS category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.product_id = $product_id";
$result_product = mysqli_query($conn, $query_product);
$product = mysqli_fetch_assoc($result_product);

if (!$product) {
    header("Location: index.php");
    exit();
}

// Fetch product images
$query_images = "SELECT image_url, is_thumbnail FROM product_images WHERE product_id = $product_id";
$result_images = mysqli_query($conn, $query_images);

// Fetch product colors
$query_colors = "SELECT color_name FROM product_colors WHERE product_id = $product_id";
$result_colors = mysqli_query($conn, $query_colors);

// Fetch reviews
$query_reviews = "SELECT reviewer_name, rating, comment, created_at FROM reviews WHERE product_id = $product_id ORDER BY created_at DESC LIMIT 5";
$result_reviews = mysqli_query($conn, $query_reviews);

// Fetch related products (assuming a basic query; adjust as needed)
$query_related = "SELECT product_id, name, price, main_image_url, rating, review_count 
                  FROM products 
                  WHERE category_id = {$product['category_id']} AND product_id != $product_id 
                  ORDER BY RAND() 
                  LIMIT 4";
$result_related = mysqli_query($conn, $query_related);

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!in_array($product_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $product_id;
    }
    header("Location: view_product.php?product_id=$product_id");
    exit();
}

// Handle wishlist
if (isset($_POST['toggle_wishlist']) && $user_id) {
    $check_wishlist = "SELECT wishlist_id FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $result_check = mysqli_query($conn, $check_wishlist);
    if (mysqli_num_rows($result_check) > 0) {
        $delete_wishlist = "DELETE FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'";
        mysqli_query($conn, $delete_wishlist);
    } else {
        $insert_wishlist = "INSERT INTO wishlist (user_id, product_id, added_at) VALUES ('$user_id', '$product_id', NOW())";
        mysqli_query($conn, $insert_wishlist);
    }
    header("Location: view_product.php?product_id=$product_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Hiba</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5', // Indigo
                        secondary: '#F59E0B' // Amber
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .gallery-thumb.active { border-color: #4F46E5; }
        .review-stars { color: #F59E0B; }
        .product-zoom { transform: scale(1.5); }
        @media (max-width: 768px) {
            .grid-cols-12 { grid-template-columns: 1fr; }
            .col-span-7, .col-span-5 { grid-column: span 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-['Pacifico'] text-primary">Hiba</a>
                </div>
                <div class="flex items-center">
                    <div class="relative">
                        <form method="GET" action="index.php">
                            <input type="text" name="search" placeholder="Search products..." class="w-64 pl-10 pr-4 py-2 rounded-full bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center">
                                <i class="ri-search-line text-gray-400"></i>
                            </div>
                        </form>
                    </div>
                    <div class="ml-4 flex items-center space-x-4">
                        <?php if ($user_id): ?>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <button type="submit" name="toggle_wishlist" class="w-10 h-10 flex items-center justify-center text-gray-600 hover:text-primary">
                                    <?php
                                    $in_wishlist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'")) > 0;
                                    echo $in_wishlist ? '<i class="ri-heart-fill text-primary text-xl"></i>' : '<i class="ri-heart-line text-xl"></i>';
                                    ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="w-10 h-10 flex items-center justify-center text-gray-600 hover:text-primary"><i class="ri-heart-line text-xl"></i></a>
                        <?php endif; ?>
                        <a href="index.php" class="w-10 h-10 flex items-center justify-center text-gray-600 hover:text-primary">
                            <i class="ri-shopping-cart-line text-xl"></i>
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if ($user_id): ?>
                            <a href="index.php?logout=true" class="text-gray-600 hover:text-primary">Logout</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm mb-8">
            <a href="index.php" class="text-gray-500 hover:text-primary">Home</a>
            <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
            <a href="?category=<?php echo $product['category_id']; ?>" class="text-gray-500 hover:text-primary"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
            <span class="text-gray-900"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Product Details -->
        <div class="grid grid-cols-12 gap-8">
            <!-- Image Gallery -->
            <div class="col-span-7">
                <div class="bg-white rounded-lg p-6">
                    <?php 
                    $main_image = $product['main_image_url'];
                    while ($image = mysqli_fetch_assoc($result_images)) {
                        if ($image['is_thumbnail']) {
                            $main_image = $image['image_url'];
                            break;
                        }
                    }
                    mysqli_data_seek($result_images, 0);
                    ?>
                    <div class="relative aspect-square mb-4">
                        <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover rounded-lg" id="mainImage">
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <?php $first = true; while ($image = mysqli_fetch_assoc($result_images)): ?>
                            <button class="gallery-thumb <?php echo $first ? 'active' : ''; ?> aspect-square rounded-lg border-2 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product Image" class="w-full h-full object-cover">
                            </button>
                            <?php $first = false; endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-span-5">
                <div class="bg-white rounded-lg p-6">
                    <h1 class="text-2xl font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="flex items-center mb-4">
                        <div class="flex items-center review-stars">
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
                        <span class="ml-2 text-sm text-gray-600"><?php echo number_format($product['rating'], 1); ?> (<?php echo $product['review_count']; ?> reviews)</span>
                    </div>

                    <div class="mb-6">
                        <div class="text-3xl font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></div>
                        <?php if ($product['original_price']): ?>
                            <div class="flex items-center mt-1">
                                <span class="text-lg text-gray-500 line-through">$<?php echo number_format($product['original_price'], 2); ?></span>
                                <span class="ml-2 text-sm font-medium text-green-600">Save <?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>%</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="flex items-center">
                            <i class="ri-truck-line w-5 h-5 text-gray-400"></i>
                            <span class="ml-2 text-sm text-gray-600">Free shipping, arrives by Feb 23 - 25</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-shield-check-line w-5 h-5 text-gray-400"></i>
                            <span class="ml-2 text-sm text-gray-600">2-year warranty included</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-arrow-go-back-line w-5 h-5 text-gray-400"></i>
                            <span class="ml-2 text-sm text-gray-600">30-day return policy</span>
                        </div>
                    </div>

                    <?php if (mysqli_num_rows($result_colors) > 0): ?>
                        <div class="mb-6">
                            <h3 class="font-medium text-gray-900 mb-2">Color</h3>
                            <div class="flex space-x-2">
                                <?php while ($color = mysqli_fetch_assoc($result_colors)): ?>
                                    <button class="w-8 h-8 rounded-full border-2 border-white" style="background-color: <?php echo htmlspecialchars($color['color_name']); ?>;"></button>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex space-x-4 mb-6">
                        <a href="<?php echo htmlspecialchars($product['amazon_affiliate_link']); ?>" target="_blank" class="flex-1 bg-primary text-white py-3 !rounded-button font-medium hover:bg-primary/90 whitespace-nowrap">Buy Now on Amazon</a>
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" name="add_to_cart" class="flex-1 bg-secondary text-white py-3 !rounded-button font-medium hover:bg-secondary/90 whitespace-nowrap">Add to Cart</button>
                        </form>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="font-medium text-gray-900 mb-4">Key Features</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="ri-checkbox-circle-line w-5 h-5 text-primary mt-0.5"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($product['description']); ?></span>
                            </li>
                            <!-- Add more features dynamically if available in your database -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Reviews -->
        <div class="mt-12">
            <h2 class="text-2xl font-semibold mb-6">Customer Reviews</h2>
            <?php if (mysqli_num_rows($result_reviews) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php while ($review = mysqli_fetch_assoc($result_reviews)): ?>
                        <div class="bg-white rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <img src="https://public.readdy.ai/ai/img_res/5379424af949112bf3c1347b1651e032.jpg" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" class="w-10 h-10 rounded-full">
                                <div class="ml-3">
                                    <div class="font-medium"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                    <div class="text-sm text-gray-500">Verified Purchase</div>
                                </div>
                            </div>
                            <div class="flex items-center mb-2 review-stars">
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
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <div class="text-sm text-gray-500"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No reviews yet.</p>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <div class="mt-12">
            <h2 class="text-2xl font-semibold mb-6">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <?php while ($related = mysqli_fetch_assoc($result_related)): ?>
                    <div class="bg-white rounded-lg p-4">
                        <a href="view_product.php?product_id=<?php echo $related['product_id']; ?>">
                            <img src="<?php echo htmlspecialchars($related['main_image_url']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="w-full aspect-square object-cover rounded-lg mb-4">
                        </a>
                        <h3 class="font-medium mb-2"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <div class="flex items-center mb-2 review-stars text-sm">
                            <?php
                            $rating = $related['rating'];
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
                            <span class="ml-1 text-gray-600">(<?php echo $related['review_count']; ?>)</span>
                        </div>
                        <div class="font-bold mb-3">$<?php echo number_format($related['price'], 2); ?></div>
                        <a href="view_product.php?product_id=<?php echo $related['product_id']; ?>" class="w-full block text-center bg-primary/10 text-primary py-2 !rounded-button font-medium hover:bg-primary/20 whitespace-nowrap">View Details</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 text-center">
        <p>© 2025 Hiba. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('mainImage');
            const galleryThumbs = document.querySelectorAll('.gallery-thumb');

            galleryThumbs.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    const newSrc = this.querySelector('img').src;
                    mainImage.src = newSrc;
                    
                    galleryThumbs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
    <?php mysqli_close($conn); ?>
</body>
</html>