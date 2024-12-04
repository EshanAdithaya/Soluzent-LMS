<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - SOLUZENT LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (Same as index.php) -->
    <?php include_once 'navbar.php';?>

    <!-- Pricing Hero -->
    <div class="pt-16">
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-extrabold text-gray-900">Simple, Transparent Pricing</h1>
                    <p class="mt-4 text-xl text-gray-500">Choose the plan that best fits your learning needs</p>
                </div>

                <!-- Pricing Plans -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Basic Plan -->
                    <div class="bg-white p-8 rounded-lg shadow-lg border-2 border-gray-100">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900">Basic</h3>
                            <div class="mt-4">
                                <span class="text-4xl font-bold">$29</span>
                                <span class="text-gray-500">/month</span>
                            </div>
                            <div class="mt-4 text-gray-500">Perfect for individual learners</div>
                        </div>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Access to 5 courses</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>2 mentor connections</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Basic progress tracking</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Email support</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="signup.php" class="block w-full text-center bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">
                                Get Started
                            </a>
                        </div>
                    </div>

                    <!-- Pro Plan -->
                    <div class="bg-white p-8 rounded-lg shadow-lg border-2 border-indigo-600 transform scale-105">
                        <div class="absolute top-0 right-0 bg-indigo-600 text-white px-4 py-1 rounded-bl-lg">
                            Popular
                        </div>
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900">Pro</h3>
                            <div class="mt-4">
                                <span class="text-4xl font-bold">$49</span>
                                <span class="text-gray-500">/month</span>
                            </div>
                            <div class="mt-4 text-gray-500">Best for dedicated students</div>
                        </div>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Unlimited course access</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>5 mentor connections</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Advanced analytics</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Priority support</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Group study sessions</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="signup.php" class="block w-full text-center bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">
                                Get Started
                            </a>
                        </div>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="bg-white p-8 rounded-lg shadow-lg border-2 border-gray-100">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900">Enterprise</h3>
                            <div class="mt-4">
                                <span class="text-4xl font-bold">$99</span>
                                <span class="text-gray-500">/month</span>
                            </div>
                            <div class="mt-4 text-gray-500">For teams and organizations</div>
                        </div>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Everything in Pro</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Unlimited mentor access</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Custom learning paths</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Dedicated success manager</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>API access</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="contact.php" class="block w-full text-center bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">
                                Contact Sales
                            </a>
                        </div>
                    </div>
                </div>

                <!-- FAQs -->
                <div class="mt-16">
                    <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Can I switch plans?</h3>
                            <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Is there a free trial?</h3>
                            <p class="text-gray-600">Yes, we offer a 7-day free trial on all plans so you can test our platform before committing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer (Same as index.php) -->
    <?php include_once 'footer.php'; ?>
</body>
</html>