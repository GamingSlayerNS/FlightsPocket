document.addEventListener("DOMContentLoaded", () => {
    const main = document.getElementById("car-main");

    if (main) {
        const form = document.createElement("form");
        form.id = "car-form";

        const cityDiv = document.createElement("div");
        const cityLabel = document.createElement("label");
        cityLabel.textContent = "City:";
        cityLabel.htmlFor = "car-city";
        const cityInput = document.createElement("input");
        cityInput.type = "text";
        cityInput.id = "car-city";
        cityInput.required = true;
        cityDiv.appendChild(cityLabel);
        cityDiv.appendChild(cityInput);
        form.appendChild(cityDiv);

        const carTypeDiv = document.createElement("div");
        const carTypeLabel = document.createElement("label");
        carTypeLabel.textContent = "Car Type:";
        carTypeLabel.htmlFor = "car-type";
        const carTypeSelect = document.createElement("select");
        carTypeSelect.id = "car-type";
        carTypeSelect.required = true;
        const carTypes = ["Economy", "SUV", "Compact", "Midsize"];
        carTypes.forEach((type) => {
            const option = document.createElement("option");
            option.value = type;
            option.textContent = type;
            carTypeSelect.appendChild(option);
        });
        carTypeDiv.appendChild(carTypeLabel);
        carTypeDiv.appendChild(carTypeSelect);
        form.appendChild(carTypeDiv);

        const checkInDiv = document.createElement("div");
        const checkInLabel = document.createElement("label");
        checkInLabel.textContent = "Check-in Date:";
        checkInLabel.htmlFor = "car-check-in";
        const checkInInput = document.createElement("input");
        checkInInput.type = "date";
        checkInInput.id = "car-check-in";
        checkInInput.required = true;
        checkInDiv.appendChild(checkInLabel);
        checkInDiv.appendChild(checkInInput);
        form.appendChild(checkInDiv);

        const checkOutDiv = document.createElement("div");
        const checkOutLabel = document.createElement("label");
        checkOutLabel.textContent = "Check-out Date:";
        checkOutLabel.htmlFor = "car-check-out";
        const checkOutInput = document.createElement("input");
        checkOutInput.type = "date";
        checkOutInput.id = "car-check-out";
        checkOutInput.required = true;
        checkOutDiv.appendChild(checkOutLabel);
        checkOutDiv.appendChild(checkOutInput);
        form.appendChild(checkOutDiv);

        const submitButton = document.createElement("button");
        submitButton.type = "submit";
        submitButton.textContent = "Submit";
        form.appendChild(submitButton);

        const resultsDiv = document.createElement("div");
        resultsDiv.id = "car-results";

        main.appendChild(form);
        main.appendChild(resultsDiv);

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const city = cityInput.value;
            const carType = carTypeSelect.value;
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);

            const startDate = new Date("2024-09-01");
            const endDate = new Date("2024-12-01");

            if (checkIn < startDate || checkIn > endDate) {
                errors.push("Check-in date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut < startDate || checkOut > endDate) {
                errors.push("Check-out date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut <= checkIn) {
                errors.push("Check-out date must be after check-in date.");
            }

            if (errors.length > 0) {
                resultsDiv.innerHTML = errors.join("<br>");
            } else {
                resultsDiv.innerHTML = `<h3>Car Rental Details</h3>
                                        <p>City: ${city}</p>
                                        <p>Car Type: ${carType}</p>
                                        <p>Check-in: ${checkIn.toDateString()}</p>
                                        <p>Check-out: ${checkOut.toDateString()}</p>`;
            }
        });
    }
});
