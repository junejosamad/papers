// Check for saved user preference, if any, on load of the website
const currentTheme = localStorage.getItem('theme');
if (currentTheme) {
    document.documentElement.setAttribute('data-theme', currentTheme);
} else {
    // Optionally check system preference
    // if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    //     document.documentElement.setAttribute('data-theme', 'dark');
    // }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    let targetTheme = 'light';

    if (currentTheme === 'dark') {
        targetTheme = 'light';
    } else {
        targetTheme = 'dark';
    }

    document.documentElement.setAttribute('data-theme', targetTheme);
    localStorage.setItem('theme', targetTheme);

    // Update icon if present
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.textContent = targetTheme === 'dark' ? 'light_mode' : 'dark_mode';
    }
}

// Ensure icon state matches theme on load
document.addEventListener('DOMContentLoaded', () => {
    const icon = document.getElementById('theme-icon');
    if (icon) {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        icon.textContent = currentTheme === 'dark' ? 'light_mode' : 'dark_mode';

        // Also fix button style if needed
        icon.parentElement.title = currentTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }
});
