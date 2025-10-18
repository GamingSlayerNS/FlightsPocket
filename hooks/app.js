const validCities = [
    "Austin",
    "Corpus Christi",
    "Dallas",
    "El Paso",
    "Fort Worth",
    "Houston",
    "San Antonio",
    "Fresno",
    "Los Angeles",
    "Oakland",
    "Sacramento",
    "San Diego",
    "San Francisco",
    "San Jose"
];

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

/**
 * @param {HTMLInputElement} inputElem 
 */
function resetCitiesPopup(inputElem) {
    const popupId = "cities-input-popup";
    let popup = document.getElementById(popupId);

    const inputCity = inputElem.value.trim().toLowerCase();
    if (inputCity.length === 0 || document.activeElement !== inputElem) {
        if (popup) popup.style.display = "none";
        if (inputCity.length == 0) inputElem.style.borderColor = "";
        return;
    }

    if (!popup) {
        popup = document.createElement("div");
        document.body.appendChild(popup);
        popup.id = popupId;
    } else {
        popup.replaceChildren();
        popup.style.display = "unset";
    }

    const pos = inputElem.getBoundingClientRect();
    popup.style.left = `${pos.x}px`;
    popup.style.top = `${pos.y + pos.height}px`;
    popup.style.width = `${pos.width}px`;

    const cities = validCities.filter((c) => c.toLowerCase().includes(inputCity));
    for (const city of cities) {
        const cityRow = document.createElement("div");
        cityRow.innerText = city;
        popup.appendChild(cityRow);
    }

    if (cities.length === 0 && inputCity.length > 0) {
        inputElem.style.borderColor = "red";
    } else {
        inputElem.style.borderColor = "";
    }
}