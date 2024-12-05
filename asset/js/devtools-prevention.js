// File: assets/js/devtools-prevention.js
// Store the previous window size
let windowWidth = window.innerWidth;
let windowHeight = window.innerHeight;

// Function to check if DevTools is open
const isDevToolsOpen = () => {
    // Method 1: Check window size difference (DevTools taking up space)
    const widthThreshold = window.innerWidth - windowWidth;
    const heightThreshold = window.innerHeight - windowHeight;
    
    // Method 2: Check if DevTools element exists
    const devtools = /./;
    devtools.toString = function() {
        return false;
    }

    // Method 3: Performance timing check
    const startTime = performance.now();
    console.log(devtools);
    const endTime = performance.now();
    
    // Update stored window size
    windowWidth = window.innerWidth;
    windowHeight = window.innerHeight;

    // Return true if any detection method indicates DevTools
    return (
        widthThreshold > 100 || 
        heightThreshold > 100 || 
        endTime - startTime > 100
    );
}

// Prevent keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (
        e.key === 'F12' || 
        (e.ctrlKey && e.shiftKey && (e.key.toLowerCase() === 'i' || e.key.toLowerCase() === 'j' || e.key.toLowerCase() === 'c')) ||
        (e.ctrlKey && e.key.toLowerCase() === 'u')
    ) {
        e.preventDefault();
        return false;
    }
});

// Prevent right click
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    return false;
});

// Variable to track if alert has been shown
let alertShown = false;

// Check for DevTools periodically
setInterval(function() {
    if (isDevToolsOpen() && !alertShown) {
        alertShown = true;
        alert('Developer tools are not allowed on this page!');
        window.location.href = 'about:blank';
    }
}, 1000);

// Reset alert flag if DevTools is closed
setInterval(function() {
    if (!isDevToolsOpen()) {
        alertShown = false;
    }
}, 1000);