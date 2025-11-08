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
        checkInLabel.textContent = "Check-In Date:";
        checkInLabel.htmlFor = "car-check-in";
        const checkInInput = document.createElement("input");
        checkInInput.type = "date";
        checkInInput.id = "car-check-in";
        checkInInput.required = true;
        checkInInput.setAttribute("min", "2024-09-01");
        checkInInput.setAttribute("max", "2024-12-01");
        checkInDiv.appendChild(checkInLabel);
        checkInDiv.appendChild(checkInInput);
        form.appendChild(checkInDiv);

        const checkOutDiv = document.createElement("div");
        const checkOutLabel = document.createElement("label");
        checkOutLabel.textContent = "Check-Out Date:";
        checkOutLabel.htmlFor = "car-check-out";
        const checkOutInput = document.createElement("input");
        checkOutInput.type = "date";
        checkOutInput.id = "car-check-out";
        checkOutInput.required = true;
        checkOutInput.setAttribute("min", "2024-09-01");
        checkOutInput.setAttribute("max", "2024-12-01");
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

        // Suggestions based on previous bookings (stored in sessionStorage.booking_history)
        const suggestionsDiv = document.createElement("div");
        suggestionsDiv.id = "car-suggestions";
        suggestionsDiv.style.marginBottom = "1rem";
        main.insertBefore(suggestionsDiv, form);

        try {
            const rawHistory = sessionStorage.getItem("booking_history");
            if (rawHistory) {
                const history = JSON.parse(rawHistory);
                // prefer most recent car booking, otherwise use most recent flight booking
                let recentCar = null;
                let recentFlight = null;
                for (let i = history.length - 1; i >= 0; i--) {
                    const h = history[i];
                    if (!recentCar && h.type === "car") recentCar = h.record;
                    if (!recentFlight && h.type === "flight") recentFlight = h.record;
                    if (recentCar && recentFlight) break;
                }

                const suggestions = [];
                if (recentCar) {
                    // suggest same city & type and a couple of nearby options
                    suggestions.push({
                        city: recentCar.city,
                        type: recentCar.type,
                        checkIn: recentCar.checkIn_date,
                        checkOut: recentCar.checkOut_date,
                        label: `Same as last car (${recentCar.type} in ${recentCar.city})`,
                    });
                    // alternate: suggest Economy in same city
                    if (recentCar.type !== "Economy")
                        suggestions.push({
                            city: recentCar.city,
                            type: "Economy",
                            checkIn: recentCar.checkIn_date,
                            checkOut: recentCar.checkOut_date,
                            label: `Economy in ${recentCar.city}`,
                        });
                } else if (recentFlight) {
                    // use flight destination as suggested city
                    const destCity =
                        (recentFlight.flights &&
                            recentFlight.flights.outbound &&
                            recentFlight.flights.outbound.destination) ||
                        recentFlight.flights?.return?.destination ||
                        recentFlight.flight?.destination ||
                        null;
                    if (destCity) {
                        suggestions.push({
                            city: destCity,
                            type: "Economy",
                            checkIn: null,
                            checkOut: null,
                            label: `From your recent flight to ${destCity}`,
                        });
                    }
                }

                if (suggestions.length > 0) {
                    let html = `<h4>Suggested Cars</h4><div style="display:flex;gap:0.5rem;flex-direction:column;">`;
                    suggestions.slice(0, 3).forEach((s, idx) => {
                        html += `<div style="display:flex;align-items:center;gap:0.5rem;"><span>${s.label}</span><button type="button" data-idx="${idx}" class="apply-suggestion">Use</button></div>`;
                    });
                    html += `</div>`;
                    suggestionsDiv.innerHTML = html;

                    // attach handlers
                    const buttons = suggestionsDiv.querySelectorAll(".apply-suggestion");
                    buttons.forEach((btn) => {
                        btn.addEventListener("click", (e) => {
                            const idx = Number(btn.getAttribute("data-idx"));
                            const s = suggestions[idx];
                            if (!s) return;
                            cityInput.value = s.city || cityInput.value;
                            // try to set car type if present in options
                            try {
                                Array.from(carTypeSelect.options).forEach((opt) => {
                                    if (opt.value.toLowerCase() === (s.type || "").toLowerCase()) opt.selected = true;
                                });
                            } catch {}
                            // convert checkIn/checkOut (if provided as human-readable) to yyyy-mm-dd
                            function toISODate(dstr) {
                                if (!dstr) return null;
                                const dt = new Date(dstr);
                                if (isNaN(dt)) return null;
                                const yyyy = dt.getFullYear();
                                const mm = String(dt.getMonth() + 1).padStart(2, "0");
                                const dd = String(dt.getDate()).padStart(2, "0");
                                return `${yyyy}-${mm}-${dd}`;
                            }
                            const inISO = toISODate(s.checkIn);
                            const outISO = toISODate(s.checkOut);
                            if (inISO) checkInInput.value = inISO;
                            if (outISO) checkOutInput.value = outISO;

                            // submit the form to show matching cars
                            try {
                                form.requestSubmit();
                            } catch (err) {
                                // fallback
                                form.dispatchEvent(new Event("submit", { cancelable: true }));
                            }
                        });
                    });
                }
            }
        } catch (err) {
            console.warn("Could not read booking_history for suggestions:", err);
        }

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const city = cityInput.value;
            const carType = carTypeSelect.value;
            const checkIn = new Date(checkInInput.value + "T00:00:00");
            const checkOut = new Date(checkOutInput.value + "T00:00:00");

            const startDate = new Date("2024-09-01T00:00:00");
            const endDate = new Date("2024-12-01T23:59:59");

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
                                        <p>Check-out: ${checkOut.toDateString()}</p>
                                        <div id="available-cars"></div>`;
            }

            const listEl = document.getElementById("available-cars");
            if (listEl) {
                var xml = new XMLHttpRequest();
                xml.open("GET", "db/rental_cars.xml", false);
                xml.send();
                var carData = xml.responseXML;
                if (carData) {
                    carData = new DOMParser().parseFromString(xml.responseText, "text/xml");
                    var carList = carData.getElementsByTagName("Car");
                    listEl.innerHTML += `<br><table id="car-results" style="width: 100%;">
                                                <tr style="text-align: left;">
                                                    <th colspan="6" style="padding-left: 10px;"><h3 style="line-height: 0.5px;">Available Cars</h3></th>
                                                </tr>
                                                <tr style="text-align: left; width: 100%; height: 25px; background-color: gainsboro; padding-left: 10px;">
                                                    <th style="width: 60px; padding-left: 10px;">ID</th>
                                                    <th style="width: 105px; padding-left: 10px;">City</th>
                                                    <th style="width: 105px; padding-left: 10px;">Type</th>
                                                    <th style="width: 135px; padding-left: 10px;">Check-In Date</th>
                                                    <th style="width: 135px; padding-left: 10px;">Check-Out Date</th>
                                                    <th style="width: 105px; padding-left: 10px;">Price Per Day</th>
                                                    <th style="background-color: white; width: 120px; padding-left: 10px;"></th>
                                                </tr>
                                             </table>`;
                    for (const car of carList) {
                        if (
                            car.getElementsByTagName("city")[0].firstChild.data.toLowerCase() === city.toLowerCase() &&
                            car.getElementsByTagName("type")[0].firstChild.data.toLowerCase() === carType.toLowerCase()
                        ) {
                            const id = car.getAttribute("id");
                            const rental_city = car.getElementsByTagName("city")[0].firstChild.data;
                            const rental_type = car.getElementsByTagName("type")[0].firstChild.data;
                            const checkInDate = checkIn.toDateString();
                            const checkOutDate = checkOut.toDateString();
                            const price = car.getElementsByTagName("pricePerDay")[0].firstChild.data;

                            let tableRef = document.getElementById("car-results");
                            let result = document.createElement("tr");

                            let idCol = document.createElement("td");
                            idCol.textContent = id;
                            idCol.style.paddingLeft = "10px";
                            result.appendChild(idCol);

                            let cityCol = document.createElement("td");
                            cityCol.textContent = rental_city;
                            cityCol.style.paddingLeft = "10px";
                            result.appendChild(cityCol);

                            let typeCol = document.createElement("td");
                            typeCol.textContent = rental_type;
                            typeCol.style.paddingLeft = "10px";
                            typeCol.style.fontWeight = "bold";
                            result.appendChild(typeCol);

                            let checkInCol = document.createElement("td");
                            checkInCol.textContent = checkInDate;
                            checkInCol.style.paddingLeft = "10px";
                            result.appendChild(checkInCol);

                            let checkOutCol = document.createElement("td");
                            checkOutCol.textContent = checkOutDate;
                            checkOutCol.style.paddingLeft = "10px";
                            result.appendChild(checkOutCol);

                            let priceCol = document.createElement("td");
                            priceCol.textContent = "$" + price;
                            priceCol.style.paddingLeft = "10px";
                            priceCol.style.fontWeight = "bold";
                            priceCol.style.backgroundColor = "#F0F0F0";
                            result.appendChild(priceCol);

                            let bookCol = document.createElement("td");
                            const myButton = document.createElement("button");
                            myButton.innerText = "Add to Cart";
                            myButton.addEventListener("click", () => {
                                const cart = {
                                    car: { id: id, city: rental_city, type: rental_type, pricePerDay: price },
                                    checkIn_date: checkIn.toDateString(),
                                    checkOut_date: checkOut.toDateString(),
                                };
                                try {
                                    sessionStorage.setItem("rentals_cart", JSON.stringify(cart));
                                } catch {}
                                window.location.href = "cart.html";
                            });
                            bookCol.appendChild(myButton);
                            bookCol.style.paddingLeft = "10px";
                            result.appendChild(bookCol);
                            tableRef.appendChild(result);
                            /* result += `<tr style="height: 22px;">
                                            <td style="padding-left: 10px;">${id}</td>
                                            <td style="font-weight: bold; padding-left: 10px;">${name}</td>
                                            <td style="padding-left: 10px;">${hotelCity}</td>
                                            <td style="padding-left: 10px;">${checkInDate}</td>
                                            <td style="padding-left: 10px;">${checkOutDate}</td>
                                            <td style="font-weight: bold; background-color: #F0F0F0; padding-left: 10px;">$${price}</td>
                                            <td style="padding-left: 10px;"><button id="cart">Add to Cart</button></td>
                                       </tr>`; */
                        }
                    }
                }
            }
        });
    }
});
