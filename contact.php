<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SOLUZENT LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (Same as index.php) -->
    <?php include_once 'navbar.php';?>

    <!-- Contact Hero -->
    <div class="pt-16">
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-extrabold text-gray-900">Get in Touch</h1>
                    <p class="mt-4 text-xl text-gray-500">We're here to help with any questions about our platform</p>
                </div>

                <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Contact Form -->
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>
                        <form action="process_contact.php" method="POST">
                            <div class="mb-4">
                                <label for="name" class="block text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:border-indigo-500">
                            </div>
                            <div class="mb-4">
                                <label for="email" class="block text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:border-indigo-500">
                            </div>
                            <div class="mb-4">
                                <label for="subject" class="block text-gray-700 mb-2">Subject</label>
                                <input type="text" id="subject" name="subject" required
                                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:border-indigo-500">
                            </div>
                            <div class="mb-4">
                                <label for="message" class="block text-gray-700 mb-2">Message</label>
                                <textarea id="message" name="message" rows="4" required
                                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:border-indigo-500"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                                Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h2>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt text-indigo-600 mt-1 mr-4"></i>
                                    <div>
                                        <h3 class="font-semibold">Address</h3>
                                        <p class="text-gray-600">123 Tech Street, Silicon Valley<br>CA 94025, USA</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-phone text-indigo-600 mt-1 mr-4"></i>
                                    <div>
                                        <h3 class="font-semibold">Phone</h3>
                                        <p class="text-gray-600">+1 (234) 567-8900</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-envelope text-indigo-600 mt-1 mr-4"></i>
                                    <div>
                                        <h3 class="font-semibold">Email</h3>
                                        <p class="text-gray-600">info@soluzent.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Business Hours -->
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Business Hours</h2>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Monday - Friday</span>
                                    <span class="text-gray-900">9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Saturday</span>
                                    <span class="text-gray-900">10:00 AM - 4:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Sunday</span>
                                    <span class="text-gray-900">Closed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="mt-16">
                    <div class="bg-gray-200 h-64 rounded-lg">
                        <!-- Replace with actual map implementation -->
                        <div class="h-full flex items-center justify-center">
                            <p class="text-gray-600">Map placeholder - Integrate with Google Maps API</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer ( Completing the Contact page from previous section -->
    
    <!-- Footer (Same as index.php) -->
    <?php include_once 'footer.php'; ?>

<!-- Contact Form Processing Script -->
<script>
    // Form validation
    const contactForm = document.querySelector('form');
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();
        
        if (name && email && subject && message) {
            // Here you would typically send the form data to your server
            // For now, we'll just show a success message
            alert('Thank you for your message. We will get back to you soon!');
            contactForm.reset();
        }
    });
</script>
</body>
</html>