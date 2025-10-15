document.addEventListener("DOMContentLoaded", () => {
    const stayForm = document.getElementById("stay-form");
    const resultsDiv = document.getElementById("stay-results");

    if (stayForm) {
        stayForm.addEventListener("submit", (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const city = document.getElementById("city").value;
            const checkIn = new Date(document.getElementById("check-in").value);
            const checkOut = new Date(document.getElementById("check-out").value);
            const adults = parseInt(document.getElementById("adults-stay").value);
            const children = parseInt(document.getElementById("children-stay").value);
            const infants = parseInt(document.getElementById("infants-stay").value);

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

            if (!validCities.includes(city)) {
                errors.push("City must be a major city in Texas or California.");
            }
            if (checkIn < startDate || checkIn > endDate) {
                errors.push("Check-in date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut < startDate || checkOut > endDate) {
                errors.push("Check-out date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut <= checkIn) {
                errors.push("Check-out date must be after check-in date.");
            }

            const totalGuests = adults + children;
            const roomsNeeded = Math.ceil(totalGuests / 2);

            if (errors.length > 0) {
                resultsDiv.innerHTML = errors.join("<br>");
            } else {
                let output = `<h3>Stay Search Details</h3>
                              <p>City: ${city}</p>
                              <p>Check-in: ${checkIn.toDateString()}</p>
                              <p>Check-out: ${checkOut.toDateString()}</p>
                              <p>Adults: ${adults}</p>
                              <p>Children: ${children}</p>
                              <p>Infants: ${infants}</p>
                              <p>Rooms Needed: ${roomsNeeded}</p>`;
                resultsDiv.innerHTML = output;
            }
        });
    }
});
