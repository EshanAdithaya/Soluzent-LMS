<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOLUZENT LMS - Learn Without Limits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<?php include_once 'navbar.php';?>
    <!-- Hero Section -->
    <div class="pt-16">
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class=" z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:pb-28 xl:pb-32">
                    <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 lg:mt-16">
                        <div class="text-center">
                            <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                                <span class="block">Transform Your Learning</span>
                                <span class="block text-indigo-600">With SOLUZENT LMS</span>
                            </h1>
                            <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                                Affordable, secure, and feature-rich learning management system. Connect with multiple mentors, access quality resources, and enhance your learning journey.
                            </p>
                            <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                                <div class="rounded-md shadow">
                                    <a href="signup.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                                        Start Learning
                                    </a>
                                </div>
                                <div class="mt-3 sm:mt-0 sm:ml-3">
                                    <a href="features.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 md:py-4 md:text-lg md:px-10">
                                        Explore Features
                                    </a>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Overview -->
    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Why Choose SOLUZENT LMS?</h2>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Multi-Mentor System</h3>
                    <p class="mt-2 text-gray-600">Learn from multiple experts and get diverse perspectives on your subjects.</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-shield-alt text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Enhanced Security</h3>
                    <p class="mt-2 text-gray-600">Advanced security measures to protect your data and learning materials.</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-coins text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Cost-Effective</h3>
                    <p class="mt-2 text-gray-600">High-quality education at affordable prices with flexible payment options.</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-laptop text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Interactive Learning</h3>
                    <p class="mt-2 text-gray-600">Engaging content and real-time interaction with mentors and peers.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4 text-center">
                <div>
                    <div class="text-4xl font-bold text-indigo-600">500+</div>
                    <div class="mt-2 text-gray-600">Active Students</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-indigo-600">50+</div>
                    <div class="mt-2 text-gray-600">Expert Mentors</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-indigo-600">100+</div>
                    <div class="mt-2 text-gray-600">Courses</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-indigo-600">95%</div>
                    <div class="mt-2 text-gray-600">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>

<?php include_once 'footer.php'; ?>
</body>
</html>