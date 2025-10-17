document.addEventListener("DOMContentLoaded", () => {
    // Hamburger Menu
    const hamburgerMenu = document.getElementById("hamburger-menu");
    const sidebar = document.querySelector("aside");

    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener("click", () => {
            sidebar.classList.toggle("open");
        });
    }

    // Date and Time
    const datetimeDiv = document.getElementById("datetime");
    if (datetimeDiv) {
        function updateTime() {
            const now = new Date();
            datetimeDiv.textContent = now.toLocaleString();
        }
        updateTime();
        setInterval(updateTime, 1000);
    }

    // Font Size and Background Color
    const fontSizeSlider = document.getElementById("font-size");
    const bgColorPicker = document.getElementById("bg-color");
    const mainContent = document.querySelector("main");

    if (fontSizeSlider && mainContent) {
        fontSizeSlider.addEventListener("input", (e) => {
            mainContent.style.fontSize = `${e.target.value}px`;
        });
    }

    if (bgColorPicker && body) {
        bgColorPicker.addEventListener("input", (e) => {
            body.style.backgroundColor = e.target.value;
        });
    }
});
