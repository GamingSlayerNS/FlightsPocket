document.addEventListener("DOMContentLoaded", () => {
    const flightForm = document.getElementById("flight-form");
    const arrivalDateContainer = document.getElementById("arrival-date-container");
    const passengerIcon = document.getElementById("passenger-icon");
    const passengerForm = document.getElementById("passenger-form");
    const resultsDiv = document.getElementById("flight-results");

    if (flightForm) {
        const tripTypeRadios = document.querySelectorAll('input[name="trip-type"]');
        tripTypeRadios.forEach((radio) => {
            radio.addEventListener("change", () => {
                if (document.getElementById("round-trip").checked) {
                    arrivalDateContainer.style.display = "block";
                } else {
                    arrivalDateContainer.style.display = "none";
                }
            });
        });

        if (passengerIcon) {
            passengerIcon.addEventListener("click", () => {
                passengerForm.style.display = passengerForm.style.display === "none" ? "block" : "none";
            });
        }

        flightForm.addEventListener("submit", (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const origin = document.getElementById("origin").value;
            const destination = document.getElementById("destination").value;
            const departureDate = new Date(document.getElementById("departure-date").value);
            const arrivalDate = document.getElementById("round-trip").checked
                ? new Date(document.getElementById("arrival-date").value)
                : null;
            const adults = document.getElementById("adults").value;
            const children = document.getElementById("children").value;
            const infants = document.getElementById("infants").value;

            const validCities = [
                "Austin",
                "Dallas",
                "Houston",
                "San Antonio",
                "Los Angeles",
                "San Diego",
                "San Francisco",
                "San Jose",
            ];
            const startDate = new Date("2024-09-01");
            const endDate = new Date("2024-12-01");

            if (!validCities.includes(origin)) {
                errors.push("Origin must be a major city in Texas or California.");
            }
            if (!validCities.includes(destination)) {
                errors.push("Destination must be a major city in Texas or California.");
            }
            if (departureDate < startDate || departureDate > endDate) {
                errors.push("Departure date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (arrivalDate && (arrivalDate < startDate || arrivalDate > endDate)) {
                errors.push("Arrival date must be between Sep 1, 2024 and Dec 1, 2024.");
            }

            if (errors.length > 0) {
                resultsDiv.innerHTML = errors.join("<br>");
            } else {
                let output = `<h3>Flight Search Details</h3>
                              <p>Trip Type: ${document.getElementById("one-way").checked ? "One Way" : "Round Trip"}</p>
                              <p>Origin: ${origin}</p>
                              <p>Destination: ${destination}</p>
                              <p>Departure Date: ${departureDate.toDateString()}</p>`;
                if (arrivalDate) {
                    output += `<p>Arrival Date: ${arrivalDate.toDateString()}</p>`;
                }
                output += `<p>Adults: ${adults}</p>
                           <p>Children: ${children}</p>
                           <p>Infants: ${infants}</p>`;
                resultsDiv.innerHTML = output;
            }
        });
    }
});
