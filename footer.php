    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="mb-8 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">SOLUZENT LMS</h3>
                    <p class="text-gray-300">Empowering education through technology and innovation.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-300 hover:text-white">About Us</a></li>
                        <li><a href="features.php" class="text-gray-300 hover:text-white">Features</a></li>
                        <li><a href="pricing.php" class="text-gray-300 hover:text-white">Pricing</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Info</h4>
                    <ul class="space-y-2">
                        <li><i class="fas fa-envelope mr-2"></i> info@soluzent.com</li>
                        <li><i class="fas fa-phone mr-2"></i> +1 234 567 8900</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> 123 Tech Street, City</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center">
                <p class="text-gray-300">&copy; 2024 SOLUZENT Technology. All rights reserved.</p>
            </div>
        </div>
    </footer>
    

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>