<?php
// Get the error code from server if available, default to 404
$error_code = $_SERVER['REDIRECT_STATUS'] ?? 404;

// Define error messages
$error_messages = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    500 => 'Internal Server Error',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout'
];

// Get error message or default to 'Unknown Error'
$error_message = $error_messages[$error_code] ?? 'Unknown Error';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_code; ?> - <?php echo $error_message; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'navbar.php'; ?>

    <div class="min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <!-- Error Code -->
            <h1 class="text-9xl font-bold text-indigo-600">
                <?php echo $error_code; ?>
            </h1>
            
            <!-- Error Message -->
            <h2 class="mt-4 text-3xl font-bold text-gray-900">
                <?php echo $error_message; ?>
            </h2>
            
            <!-- Helpful Message -->
            <p class="mt-4 text-lg text-gray-600">
                <?php if($error_code == 404): ?>
                    The page you're looking for doesn't exist or has been moved.
                <?php else: ?>
                    Something went wrong. Please try again later.
                <?php endif; ?>
            </p>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                <a href="javascript:history.back()" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go Back
                </a>
                <a href="/" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Return Home
                </a>
            </div>

            <!-- Contact Support -->
            <p class="mt-8 text-sm text-gray-500">
                Need help? <a href="/contact" class="font-medium text-indigo-600 hover:text-indigo-500">Contact Support</a>
            </p>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>
</body>
</html>