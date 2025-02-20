<?php
session_start();
include 'config.php';

// Ensure cart is initialized
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

// Handle cart updates
if (isset($_POST['update_cart'])) {
    $quantities = $_POST['quantity'];
    foreach ($quantities as $product_id => $quantity) {
        $quantity = max(1, (int)$quantity); // Ensure quantity is at least 1
        $key = array_search((int)$product_id, $_SESSION['cart']);
        if ($key !== false) {
            // Update quantity (we'll store as repeated entries for simplicity)
            unset($_SESSION['cart'][$key]);
            for ($i = 0; $i < $quantity; $i++) {
                $_SESSION['cart'][] = (int)$product_id;
            }
        }
    }
    header("Location: cart.php");
    exit();
}

if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['remove_from_cart'];
    $keys = array_keys($_SESSION['cart'], $product_id);
    foreach ($keys as $key) {
        unset($_SESSION['cart'][$key]);
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

// Fetch cart items with quantities
$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    $cart_counts = array_count_values($_SESSION['cart']);
    $cart_ids = implode(',', array_map('intval', array_unique($_SESSION['cart'])));
    $cart_query = "SELECT product_id, name, price, main_image_url FROM products WHERE product_id IN ($cart_ids)";
    $cart_result = mysqli_query($conn, $cart_query);
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $item['quantity'] = $cart_counts[$item['product_id']];
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total_price += $item['subtotal'];
        $cart_items[] = $item;
    }
}

// Handle logout
if (isset($_GET['logout']) && $user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Hiba</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#EC4899'
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
        .cart-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item .details {
            flex: 1;
            margin-left: 16px;
        }
        .cart-item .details p {
            color: #4A4A4A;
        }
        .cart-item .details .price {
            color: #4F46E5;
            font-weight: 600;
        }
        .quantity-input {
            width: 60px;
            padding: 4px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="font-['Pacifico'] text-3xl text-primary">Hiba</a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-600 hover:text-primary">Continue Shopping</a>
                    <?php if ($user_id): ?>
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-600"><?php echo htmlspecialchars($user['fullname']); ?></span>
                            <a href="cart.php?logout=true" class="text-primary hover:underline">Logout</a>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-4">
                            <a href="login.php" class="text-primary hover:underline">Login</a>
                            <a href="register.php" class="bg-primary text-white px-4 py-1 rounded-button hover:bg-primary/90">Signup</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                <p class="text-gray-600 mb-4">Your cart is empty.</p>
                <a href="index.php" class="bg-primary text-white px-6 py-3 rounded-button font-medium hover:bg-primary/90">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm">
                        <form method="POST" action="cart.php">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <a href="view_product.php?product_id=<?php echo $item['product_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($item['main_image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </a>
                                    <div class="details">
                                        <p class="font-medium text-lg"><?php echo htmlspecialchars($item['name']); ?></p>
                                        <p class="price">$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?> = $<?php echo number_format($item['subtotal'], 2); ?></p>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <input type="number" name="quantity[<?php echo $item['product_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                                        <button type="submit" name="remove_from_cart" value="<?php echo $item['product_id']; ?>" class="text-secondary hover:text-secondary/80">
                                            <i class="ri-delete-bin-line text-xl"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="p-4 flex justify-end">
                                <button type="submit" name="update_cart" class="bg-primary text-white px-6 py-2 rounded-button font-medium hover:bg-primary/90">Update Cart</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="text-gray-900">$<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Shipping</span>
                            <span class="text-gray-900">Free</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4 mt-4 flex justify-between">
                            <span class="text-lg font-semibold text-gray-900">Total</span>
                            <span class="text-lg font-bold text-primary">$<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <button class="w-full bg-primary text-white px-6 py-3 rounded-button font-medium hover:bg-primary/90 mt-6">Proceed to Checkout</button>
                        <p class="text-center text-gray-600 mt-4">Checkout will redirect to Amazon</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h3 class="font-['Pacifico'] text-2xl mb-4">Hiba</h3>
                    <p class="text-gray-400 mb-4">Your smart shopping companion.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-facebook-fill text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-twitter-fill text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-instagram-fill text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-pinterest-fill text-xl"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="#" class="text-gray-400 hover:text-white block">About Us</a>
                        <a href="#" class="text-gray-400 hover:text-white block">Contact</a>
                        <a href="#" class="text-gray-400 hover:text-white block">Blog</a>
                        <a href="#" class="text-gray-400 hover:text-white block">FAQ</a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Categories</h3>
                    <div class="space-y-2">
                        <?php 
                        $result_categories = mysqli_query($conn, "SELECT category_id, name FROM categories WHERE parent_id IS NULL LIMIT 4");
                        while ($category = mysqli_fetch_assoc($result_categories)): ?>
                            <a href="index.php?category=<?php echo $category['category_id']; ?>" class="text-gray-400 hover:text-white block"><?php echo htmlspecialchars($category['name']); ?></a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Newsletter</h3>
                    <form method="POST" action="subscribe.php">
                        <input type="email" name="email" placeholder="Your email" class="w-full p-2 mb-2 border border-gray-600 rounded-lg bg-gray-800 text-white" required>
                        <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-button hover:bg-primary/90">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="text-center text-gray-400">
                <p>© 2025 Hiba. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php mysqli_close($conn); ?>
</body>
</html>