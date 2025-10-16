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

            const origin = document.getElementById("origin").value.trim();
        	const destination = document.getElementById("destination").value.trim();
        	const departureDate = new Date(document.getElementById("departure-date").value + "T00:00:00");
        	const arrivalDate = document.getElementById("round-trip").checked
            	? new Date(document.getElementById("arrival-date").value + "T00:00:00")
            	: null;
            const adults = document.getElementById("adults").value;
            const children = document.getElementById("children").value;
            const infants = document.getElementById("infants").value;

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
            const startDate = new Date("2024-09-01T00:00:00");
            const endDate = new Date("2024-12-01T23:59:59");
            const lowerCaseCities = validCities.map(function (item) {
                return item.toLowerCase();
            });

            if (!lowerCaseCities.includes(origin.toLowerCase())) {
                errors.push("Origin must be a major city in Texas or California.");
            }
            if (!lowerCaseCities.includes(destination.toLowerCase())) {
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
