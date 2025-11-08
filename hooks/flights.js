document.addEventListener("DOMContentLoaded", () => {
    const flightForm = document.getElementById("flight-form");
    const arrivalDateContainer = document.getElementById("arrival-date-container");
    const passengerIcon = document.getElementById("passenger-icon");
    const passengerForm = document.getElementById("passenger-form");
    const resultsDiv = document.getElementById("flight-results");

    const originInput = document.getElementById("origin");
    if (originInput) {
        originInput.addEventListener("input", (e) => {
            resetCitiesPopup(originInput);
        });
        originInput.addEventListener("blur", (e) => {
            resetCitiesPopup(originInput);
        });
        originInput.addEventListener("focus", (e) => {
            resetCitiesPopup(originInput);
        });
    }

    const destinationInput = document.getElementById("destination");
    if (destinationInput) {
        destinationInput.addEventListener("input", (e) => {
            resetCitiesPopup(destinationInput);
        });
        destinationInput.addEventListener("blur", (e) => {
            resetCitiesPopup(destinationInput);
        });
        destinationInput.addEventListener("focus", (e) => {
            resetCitiesPopup(destinationInput);
        });
    }

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

        flightForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const origin = originInput.value.trim();
            const destination = document.getElementById("destination").value.trim();
            const departureDate = new Date(document.getElementById("departure-date").value + "T00:00:00");
            const arrivalDate = document.getElementById("round-trip").checked
                ? new Date(document.getElementById("arrival-date").value + "T00:00:00")
                : null;
            const adults = parseInt(document.getElementById("adults").value || "0", 10);
            const children = parseInt(document.getElementById("children").value || "0", 10);
            const infants = parseInt(document.getElementById("infants").value || "0", 10);

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
                "San Jose",
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

            // passenger validations
            if (isNaN(adults) || adults < 0 || adults > 4) {
                errors.push("Adults must be between 0 and 4.");
            }
            if (isNaN(children) || children < 0 || children > 4) {
                errors.push("Children must be between 0 and 4.");
            }
            if (isNaN(infants) || infants < 0 || infants > 4) {
                errors.push("Infants must be between 0 and 4.");
            }
            const totalPassengers = adults + children + infants;
            if (totalPassengers <= 0) {
                errors.push("Please select at least one passenger.");
            }

            if (errors.length > 0) {
                resultsDiv.innerHTML = `<div class="error">${errors.join("<br>")}</div>`;
                return;
            }

            // Always show entered criteria
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

            // For one-way, also show available flights and allow add to cart
            const isOneWay = document.getElementById("one-way").checked;
            if (isOneWay) {
                try {
                    const res = await fetch("db/flights.json", { cache: "no-store" });
                    if (!res.ok) throw new Error("Failed to load flights database");
                    const flights = await res.json();
                    // Persist list for later use (optional)
                    try {
                        sessionStorage.setItem("fp_flights_db", JSON.stringify(flights));
                    } catch {}

                    const reqDateStr = document.getElementById("departure-date").value; // yyyy-mm-dd
                    function byCriteria(list, dateStr) {
                        return list.filter(
                            (f) =>
                                f.origin.toLowerCase() === origin.toLowerCase() &&
                                f.destination.toLowerCase() === destination.toLowerCase() &&
                                f.departureDate === dateStr &&
                                Number(f.availableSeats) >= totalPassengers
                        );
                    }

                    let matching = byCriteria(flights, reqDateStr);
                    let searchedWindow = [];
                    if (matching.length === 0) {
                        // search within +/- 3 days
                        const base = new Date(reqDateStr + "T00:00:00");
                        for (let delta = -3; delta <= 3; delta++) {
                            if (delta === 0) continue;
                            const d = new Date(base);
                            d.setDate(base.getDate() + delta);
                            const yyyy = d.getFullYear();
                            const mm = String(d.getMonth() + 1).padStart(2, "0");
                            const dd = String(d.getDate()).padStart(2, "0");
                            const ds = `${yyyy}-${mm}-${dd}`;
                            searchedWindow.push(ds);
                            matching = matching.concat(byCriteria(flights, ds));
                        }
                    }

                    if (matching.length === 0) {
                        output += `<h4>No flights on ${reqDateStr}. Showing none available within ±3 days either.</h4>`;
                    } else {
                        output += `<h3>Available Flights</h3>`;
                        output += `<ul id="available-flights"></ul>`;
                    }
                    resultsDiv.innerHTML = output;

                    const listEl = document.getElementById("available-flights");
                    if (listEl) {
                        matching.sort((a, b) =>
                            (a.departureDate + a.departureTime).localeCompare(b.departureDate + b.departureTime)
                        );
                        for (const f of matching) {
                            const li = document.createElement("li");
                            li.innerHTML = `<strong>${f.flightId}</strong> — ${f.origin} → ${f.destination} | Dep ${
                                f.departureDate
                            } ${f.departureTime} | Arr ${f.arrivalDate} ${f.arrivalTime} | Seats: ${
                                f.availableSeats
                            } | $${f.price.toFixed(2)} `;
                            const btn = document.createElement("button");
                            btn.textContent = "Add to Cart";
                            btn.addEventListener("click", () => {
                                const cart = {
                                    tripType: "one-way",
                                    passengers: { adults, children, infants },
                                    flight: f,
                                };
                                try {
                                    sessionStorage.setItem("fp_cart", JSON.stringify(cart));
                                } catch {}
                                window.location.href = "cart.html";
                            });
                            li.appendChild(btn);
                            listEl.appendChild(li);
                        }
                    }
                } catch (err) {
                    resultsDiv.innerHTML = output + `<div class="error">${err.message}</div>`;
                }
            } else {
                // Round-trip: find matching outbound and return flights and allow add-to-cart
                try {
                    const res = await fetch("db/flights.json", { cache: "no-store" });
                    if (!res.ok) throw new Error("Failed to load flights database");
                    const flights = await res.json();
                    try {
                        sessionStorage.setItem("fp_flights_db", JSON.stringify(flights));
                    } catch {}

                    const outDateStr = document.getElementById("departure-date").value; // yyyy-mm-dd
                    const retDateStr = document.getElementById("arrival-date").value; // yyyy-mm-dd

                    function byCriteria(list, dateStr, fromCity, toCity) {
                        return list.filter(
                            (f) =>
                                f.origin.toLowerCase() === fromCity.toLowerCase() &&
                                f.destination.toLowerCase() === toCity.toLowerCase() &&
                                f.departureDate === dateStr &&
                                Number(f.availableSeats) >= totalPassengers
                        );
                    }

                    // helper to search date +/− 3 days if no exact matches
                    function findWithWindow(list, dateStr, fromCity, toCity) {
                        let matches = byCriteria(list, dateStr, fromCity, toCity);
                        if (matches.length === 0) {
                            const base = new Date(dateStr + "T00:00:00");
                            for (let delta = -3; delta <= 3; delta++) {
                                if (delta === 0) continue;
                                const d = new Date(base);
                                d.setDate(base.getDate() + delta);
                                const yyyy = d.getFullYear();
                                const mm = String(d.getMonth() + 1).padStart(2, "0");
                                const dd = String(d.getDate()).padStart(2, "0");
                                const ds = `${yyyy}-${mm}-${dd}`;
                                matches = matches.concat(byCriteria(list, ds, fromCity, toCity));
                            }
                        }
                        return matches;
                    }

                    const outboundMatches = findWithWindow(flights, outDateStr, origin, destination);
                    const returnMatches = findWithWindow(flights, retDateStr, destination, origin);

                    // pair combinations where both have enough seats
                    const pairs = [];
                    for (const o of outboundMatches) {
                        for (const r of returnMatches) {
                            // ensure they are not the same flight and seats available for totalPassengers
                            if (o.flightId === r.flightId) continue;
                            if (
                                Number(o.availableSeats) >= totalPassengers &&
                                Number(r.availableSeats) >= totalPassengers
                            ) {
                                pairs.push({ outbound: o, return: r, totalPrice: Number(o.price) + Number(r.price) });
                            }
                        }
                    }

                    if (pairs.length === 0) {
                        output += `<h4>No round-trip pairs found for the selected dates (or within ±3 days).</h4>`;
                        resultsDiv.innerHTML = output;
                        return;
                    }

                    // render pairs
                    output += `<h3>Available Round-Trip Options</h3>`;
                    output += `<ul id="available-roundtrips"></ul>`;
                    resultsDiv.innerHTML = output;

                    const listEl = document.getElementById("available-roundtrips");
                    pairs.sort((a, b) => a.totalPrice - b.totalPrice);
                    for (const p of pairs) {
                        const li = document.createElement("li");
                        li.innerHTML = `<strong>${p.outbound.flightId} ⇄ ${p.return.flightId}</strong> — ${
                            p.outbound.origin
                        } → ${p.outbound.destination} | Dep ${p.outbound.departureDate} ${
                            p.outbound.departureTime
                        } | Ret ${p.return.departureDate} ${p.return.departureTime} | Seats: ${Math.min(
                            p.outbound.availableSeats,
                            p.return.availableSeats
                        )} | Total $${p.totalPrice.toFixed(2)}`;
                        const btn = document.createElement("button");
                        btn.textContent = "Add Round-Trip to Cart";
                        btn.addEventListener("click", () => {
                            const cart = {
                                tripType: "round-trip",
                                passengers: { adults, children, infants },
                                flights: { outbound: p.outbound, return: p.return },
                                totalPrice: p.totalPrice,
                            };
                            try {
                                sessionStorage.setItem("fp_cart", JSON.stringify(cart));
                            } catch {}
                            window.location.href = "cart.html";
                        });
                        li.appendChild(btn);
                        listEl.appendChild(li);
                    }
                } catch (err) {
                    resultsDiv.innerHTML = output + `<div class="error">${err.message}</div>`;
                }
            }
        });
    }
});
