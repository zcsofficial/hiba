<?php
// Start session
session_start();

// Include database configuration
include 'config.php';

// Initialize variables
$email = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If no validation errors, check credentials
    if (empty($errors)) {
        $query = "SELECT user_id, fullname, email, password_hash FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['success'] = "Login successful! Welcome, " . $user['fullname'] . ".";

                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with that email.";
        }
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hiba</title>
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
                        secondary: '#E5E7EB' // Light Gray
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-['Pacifico'] text-primary">Hiba</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-primary font-medium">Login</span>
                    <a href="register.php" class="text-gray-500 font-medium hover:text-primary">Register</a>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 relative">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://public.readdy.ai/ai/img_res/981d4336ab3675c5df5abdabbd4b5217.jpg'); opacity: 0.1;"></div>

            <div class="form-container max-w-md w-full space-y-8 p-8 rounded-lg shadow-xl relative">
                <!-- Login Form -->
                <div class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-gray-900">Welcome back</h2>
                        <p class="mt-2 text-sm text-gray-600">Please sign in to your account</p>
                    </div>

                    <!-- Display errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 text-red-700 p-4 rounded-lg">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-100 text-green-700 p-4 rounded-lg">
                            <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" class="mt-8 space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="email" class="sr-only">Email address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <i class="ri-mail-line text-gray-400"></i>
                                    </div>
                                    <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" required 
                                           class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                                           placeholder="Email address">
                                </div>
                            </div>
                            <div>
                                <label for="password" class="sr-only">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <i class="ri-lock-line text-gray-400"></i>
                                    </div>
                                    <input id="password" name="password" type="password" required 
                                           class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                                           placeholder="Password">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button type="button" onclick="togglePassword('password')" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember-me" name="remember-me" type="checkbox" 
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                            </div>
                            <div class="text-sm">
                                <a href="#" class="font-medium text-primary hover:text-primary/80">Forgot password?</a>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="!rounded-button w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Sign in
                            </button>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or continue with</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" class="!rounded-button w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="ri-google-fill mr-2"></i>
                                Google
                            </button>
                            <button type="button" class="!rounded-button w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="ri-facebook-fill mr-2"></i>
                                Facebook
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    <p>As an Amazon Associate, we earn from qualifying purchases.</p>
                    <p class="mt-1">© 2025 Hiba. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentElement.querySelector('button i');
            if (input.type === 'password') {
                input.type = 'text';
                button.className = 'ri-eye-off-line';
            } else {
                input.type = 'password';
                button.className = 'ri-eye-line';
            }
        }
    </script>
</body>
</html>