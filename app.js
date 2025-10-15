document.addEventListener("DOMContentLoaded", () => {
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
    const body = document.body;

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
