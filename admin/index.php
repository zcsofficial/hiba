<?php
// Start session
session_start();

// Include database configuration
include 'config.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch user details including role
$query_user = "SELECT u.fullname, ur.role_name 
               FROM users u 
               JOIN user_roles ur ON u.role_id = ur.role_id 
               WHERE u.user_id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Check if user is an admin
$is_admin = ($user && $user['role_name'] === 'admin');
if (!$is_admin) {
    header("Location: index.php");
    exit();
}

// Initialize variables for feedback
$errors = [];
$success = '';

// Fetch categories for product form
$query_categories = "SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$result_categories = mysqli_query($conn, $query_categories);

// Fetch products for review form dropdown
$query_products = "SELECT product_id, name FROM products ORDER BY name";
$result_products = mysqli_query($conn, $query_products);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Product form submission
        $category_id = (int)$_POST['category_id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = (float)$_POST['price'];
        $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $amazon_affiliate_link = mysqli_real_escape_string($conn, trim($_POST['amazon_affiliate_link']));
        $main_image_url = mysqli_real_escape_string($conn, trim($_POST['main_image_url']));
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($name)) $errors[] = "Product name is required.";
        if ($category_id <= 0) $errors[] = "Category is required.";
        if ($price <= 0) $errors[] = "Price must be greater than 0.";
        if (empty($amazon_affiliate_link)) $errors[] = "Amazon affiliate link is required.";

        if (empty($errors)) {
            $query = "INSERT INTO products (category_id, name, description, price, original_price, amazon_affiliate_link, main_image_url, is_featured, created_at) 
                      VALUES ($category_id, '$name', '$description', $price, " . ($original_price ? "'$original_price'" : "NULL") . ", '$amazon_affiliate_link', '$main_image_url', $is_featured, NOW())";
            if (mysqli_query($conn, $query)) {
                $success = "Product added successfully!";
            } else {
                $errors[] = "Failed to add product: " . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['add_review'])) {
        // Review form submission
        $product_id = (int)$_POST['product_id'];
        $reviewer_name = mysqli_real_escape_string($conn, trim($_POST['reviewer_name']));
        $rating = (float)$_POST['rating'];
        $comment = mysqli_real_escape_string($conn, trim($_POST['comment']));
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;

        if ($product_id <= 0) $errors[] = "Product is required.";
        if (empty($reviewer_name)) $errors[] = "Reviewer name is required.";
        if ($rating < 0 || $rating > 5) $errors[] = "Rating must be between 0 and 5.";
        if (empty($comment)) $errors[] = "Comment is required.";

        if (empty($errors)) {
            $query = "INSERT INTO reviews (product_id, user_id, reviewer_name, rating, comment, is_verified, created_at) 
                      VALUES ($product_id, NULL, '$reviewer_name', $rating, '$comment', $is_verified, NOW())";
            if (mysqli_query($conn, $query)) {
                $success = "Review added successfully!";
            } else {
                $errors[] = "Failed to add review: " . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['add_blog'])) {
        // Blog form submission
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $image_url = mysqli_real_escape_string($conn, trim($_POST['image_url']));
        $content = mysqli_real_escape_string($conn, trim($_POST['content']));

        if (empty($title)) $errors[] = "Blog title is required.";
        if (empty($description)) $errors[] = "Description is required.";
        if (empty($content)) $errors[] = "Content is required.";

        if (empty($errors)) {
            $query = "INSERT INTO blog_posts (title, description, image_url, content, created_at) 
                      VALUES ('$title', '$description', '$image_url', '$content', NOW())";
            if (mysqli_query($conn, $query)) {
                $success = "Blog post added successfully!";
            } else {
                $errors[] = "Failed to add blog post: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hiba</title>
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
    <style>
        .form-container {
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="font-['Pacifico'] text-2xl text-primary">Hiba</a>
                <div class="flex items-center gap-4">
                    <span class="text-gray-700 font-medium">Admin Panel</span>
                    <a href="index.php?logout=true" class="text-primary hover:text-primary/80"><i class="ri-logout-box-r-line text-xl"></i></a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Admin Dashboard</h1>

        <!-- Feedback Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="form-container p-6 rounded-lg mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Add New Product</h2>
            <form method="POST" action="admin.php" class="space-y-6">
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" required>
                        <option value="">Select a category</option>
                        <?php while ($category = mysqli_fetch_assoc($result_categories)): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endwhile; mysqli_data_seek($result_categories, 0); ?>
                    </select>
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="name" id="name" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Original Price (optional)</label>
                        <input type="number" name="original_price" id="original_price" step="0.01" min="0" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label for="amazon_affiliate_link" class="block text-sm font-medium text-gray-700">Amazon Affiliate Link</label>
                    <input type="text" name="amazon_affiliate_link" id="amazon_affiliate_link" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div>
                    <label for="main_image_url" class="block text-sm font-medium text-gray-700">Main Image URL</label>
                    <input type="text" name="main_image_url" id="main_image_url" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_featured" id="is_featured" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Mark as Featured</label>
                </div>
                <button type="submit" name="add_product" class="w-full bg-primary text-white px-4 py-2.5 rounded-button font-medium hover:bg-primary/90">Add Product</button>
            </form>
        </div>

        <!-- Add Review Form -->
        <div class="form-container p-6 rounded-lg mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Add New Review</h2>
            <form method="POST" action="admin.php" class="space-y-6">
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">Product</label>
                    <select name="product_id" id="product_id" class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" required>
                        <option value="">Select a product</option>
                        <?php while ($product = mysqli_fetch_assoc($result_products)): ?>
                            <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                        <?php endwhile; mysqli_data_seek($result_products, 0); ?>
                    </select>
                </div>
                <div>
                    <label for="reviewer_name" class="block text-sm font-medium text-gray-700">Reviewer Name</label>
                    <input type="text" name="reviewer_name" id="reviewer_name" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">Rating (0-5)</label>
                    <input type="number" name="rating" id="rating" step="0.1" min="0" max="5" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div>
                    <label for="comment" class="block text-sm font-medium text-gray-700">Comment</label>
                    <textarea name="comment" id="comment" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_verified" id="is_verified" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_verified" class="ml-2 text-sm text-gray-700">Verified Purchase</label>
                </div>
                <button type="submit" name="add_review" class="w-full bg-primary text-white px-4 py-2.5 rounded-button font-medium hover:bg-primary/90">Add Review</button>
            </form>
        </div>

        <!-- Add Blog Post Form -->
        <div class="form-container p-6 rounded-lg">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Add New Blog Post</h2>
            <form method="POST" action="admin.php" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="title" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required></textarea>
                </div>
                <div>
                    <label for="image_url" class="block text-sm font-medium text-gray-700">Image URL</label>
                    <input type="text" name="image_url" id="image_url" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea name="content" id="content" rows="6" class="mt-1 w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required></textarea>
                </div>
                <button type="submit" name="add_blog" class="w-full bg-primary text-white px-4 py-2.5 rounded-button font-medium hover:bg-primary/90">Add Blog Post</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-8 text-center">
        <p>© 2025 Hiba. All rights reserved.</p>
    </footer>

    <?php mysqli_close($conn); ?>
</body>
</html>