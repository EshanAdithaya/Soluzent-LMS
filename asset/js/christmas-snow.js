// Snow Effect Script
function initChristmasTheme() {
    // Create snow container if it doesn't exist
    let snowContainer = document.getElementById('snow');
    if (!snowContainer) {
        snowContainer = document.createElement('div');
        snowContainer.id = 'snow';
        snowContainer.className = 'snow';
        document.body.appendChild(snowContainer);
    }

    // Add Christmas background if it doesn't exist
    let christmasBg = document.querySelector('.christmas-bg');
    if (!christmasBg) {
        christmasBg = document.createElement('div');
        christmasBg.className = 'christmas-bg';
        document.body.insertBefore(christmasBg, document.body.firstChild);
    }

    const numberOfSnowflakes = 50;

    // Create initial snowflakes
    for (let i = 0; i < numberOfSnowflakes; i++) {
        createSnowflake();
    }

    function createSnowflake() {
        const snowflake = document.createElement('div');
        snowflake.className = 'snowflake';
        
        // Random properties for more natural movement
        const size = Math.random() * 5 + 5;
        const startPositionX = Math.random() * window.innerWidth;
        const startPositionY = -10;
        const duration = Math.random() * 3 + 2;
        const delay = Math.random() * 5;

        snowflake.style.width = `${size}px`;
        snowflake.style.height = `${size}px`;
        snowflake.style.left = `${startPositionX}px`;
        snowflake.style.top = `${startPositionY}px`;
        snowflake.style.animationDuration = `${duration}s`;
        snowflake.style.animationDelay = `${delay}s`;
        snowflake.style.opacity = Math.random();

        snowContainer.appendChild(snowflake);

        // Remove and recreate snowflake after animation
        snowflake.addEventListener('animationend', () => {
            snowflake.remove();
            createSnowflake();
        });
    }
}

// Initialize theme when DOM is loaded
document.addEventListener('DOMContentLoaded', initChristmasTheme);

// Optional: Adjust snow on window resize
window.addEventListener('resize', () => {
    const snowContainer = document.getElementById('snow');
    if (snowContainer) {
        snowContainer.innerHTML = '';
        initChristmasTheme();
    }
});