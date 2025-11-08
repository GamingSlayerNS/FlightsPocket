document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("cart-content");
    if (!container) return;
    container.innerHTML = ``;

    let flight_cart = null;
    let hotel_cart = null;

    // generate user id
    const randId = `U${Date.now().toString(36)}`;
    const userId = sessionStorage.getItem("userId");
    if (userId === null) {
        sessionStorage.setItem("userId", randId);
    }

    let rentals_cart = null;
    try {
        flight_cart = JSON.parse(sessionStorage.getItem("fp_cart"));
        hotel_cart = JSON.parse(sessionStorage.getItem("hotels_cart"));
        rentals_cart = JSON.parse(sessionStorage.getItem("rentals_cart"));
    } catch {}

    const hasFlightCart = Boolean(
        flight_cart && (flight_cart.flight || flight_cart.flights || flight_cart.tripType === "round-trip")
    );
    const hasRentalCart = Boolean(rentals_cart && rentals_cart.car);

    if (!hasFlightCart && !hotel_cart) {
        container.innerHTML = `<p>Your cart is empty. Go to the <a href="flights.html">Flights</a> page to add a flight.</p>`;
        return;
    } else {
        if (hotel_cart) {
            const { hotel, checkIn_date, checkOut_date, passengers } = hotel_cart;
            console.log(
                "Hotel ID: " +
                    hotel.id +
                    "\nHotel Name: " +
                    hotel.name +
                    "\nCheck-In Date: " +
                    checkIn_date +
                    "\nCheck-Out Date: " +
                    checkOut_date +
                    "\nNumber of Adults: " +
                    Number(passengers?.adults || 0) +
                    "\nNumber of Children: " +
                    Number(passengers?.children || 0)
            );
            const adults = Number(passengers?.adults || 0);
            const children = Number(passengers?.children || 0);
            const infants = Number(passengers?.infants || 0);
            const numRoomsNeeded = Number(hotel.num_rooms_needed);
            const price = Number(hotel.pricePerNight);
            const numNights = (new Date(checkOut_date) - new Date(checkIn_date)) / (1000 * 60 * 60 * 24);
            const totalPrice = numRoomsNeeded * price * numNights;
            const headerHtml = `
                <div class="hotel-summary">
                    <h3>Selected Hotel</h3>
                    <p><strong>Hotel Name: ${hotel.name}</strong></p>
                    <p>Hotel-ID: ${hotel.id}</p>
                    <p>City: ${hotel.city}</p>
                    <p>Guests: Adults ${adults}, Children ${children}, Infants ${infants}</p>
                    <p>Check-In Date: ${checkIn_date}</p>
                    <p>Check-Out Date: ${checkOut_date}</p>
                    <p>Rooms Needed: ${numRoomsNeeded}</p>
                    <p>Price Per Night: $${price.toFixed(2)}</p>
                    <h4>Total: $${totalPrice.toFixed(2)}</h4>
                </div>
            `;
            container.innerHTML += headerHtml;

            const submit = document.createElement("button");
            submit.type = "submit";
            submit.textContent = "Book Hotel";
            container.appendChild(submit);
            submit.addEventListener("click", () => {
                // create hotel-booking.json file
                const bookingNumber = `B${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
                const hotel_booking = {
                    user_id: userId,
                    booking_number: bookingNumber,
                    hotel_id: hotel.id,
                    hotel_city: hotel.city,
                    hotel_name: hotel.name,
                    checkIn_date: checkIn_date,
                    checkOut_date: checkOut_date,
                    adult_guests: adults,
                    children_guests: children,
                    infant_guests: infants,
                    num_rooms_needed: numRoomsNeeded,
                    price_per_night: "$" + price.toFixed(2),
                    total_price: "$" + totalPrice.toFixed(2),
                };
                const blob = new Blob([JSON.stringify(hotel_booking, null, 2)], { type: "application/json" });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "hotel-booking.json"; // overwrite the same file when saving ('hotel-booking.json' file located in 'db' folder)
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                console.log("Created hotel-booking.json file.");

                // update hotels.xml file
                const newSeats = Math.max(0, Number(hotel.num_rooms_available) - numRoomsNeeded);
                var xml = new XMLHttpRequest();
                xml.open("GET", "db/hotels.xml", false);
                xml.send();
                var hotelData = xml.responseXML;
                if (hotelData) {
                    hotelData = new DOMParser().parseFromString(xml.responseText, "text/xml");
                    var hotelList = hotelData.getElementsByTagName("Hotel");
                    for (const hotels of hotelList) {
                        if (hotels.getAttribute("id") === hotel.id) {
                            hotels.getElementsByTagName("numAvailableRooms")[0].textContent = newSeats;
                            break;
                        }
                    }
                    const serializer = new XMLSerializer();
                    const updatedXMLString = serializer.serializeToString(hotelData);

                    const blob = new Blob([updatedXMLString], { type: "application/xml" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "hotels.xml";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    console.log("Successfully updated 'hotels.xml' file.");
                } else {
                    console.log("Could not find 'hotels.xml'!");
                }
                try {
                    sessionStorage.removeItem("hotels_cart");
                } catch {}
            });
        }
        if (flight_cart) {
            // Support both one-way (flight) and round-trip (flights.outbound + flights.return)
            let adults = 0,
                children = 0,
                infants = 0,
                totalPax = 0;
            let isRound = flight_cart.tripType === "round-trip";
            let outbound = null,
                ret = null;
            if (isRound) {
                const passengers = flight_cart.passengers || {};
                adults = Number(passengers?.adults || 0);
                children = Number(passengers?.children || 0);
                infants = Number(passengers?.infants || 0);
                totalPax = adults + children + infants;
                outbound = flight_cart.flights?.outbound || null;
                ret = flight_cart.flights?.return || null;
            } else {
                const { flight, passengers } = flight_cart;
                adults = Number(passengers?.adults || 0);
                children = Number(passengers?.children || 0);
                infants = Number(passengers?.infants || 0);
                totalPax = adults + children + infants;
                outbound = flight || null;
            }

            if (!outbound) {
                container.innerHTML += `<p>Selected flight data is missing or invalid.</p>`;
                return;
            }

            // compute per-passenger costs
            const adultCost = Number(outbound.price) + (ret ? Number(ret.price) : 0);
            const childCost = adultCost * 0.7;
            const infantCost = adultCost * 0.1;
            const totalPrice = adults * adultCost + children * childCost + infants * infantCost;

            const headerHtml = `
                <div class="flight-summary">
                    <h3>Selected Flight${isRound ? " (Round-Trip)" : ""}</h3>
                    <p><strong>${outbound.flightId}${ret ? ` ⇄ ${ret.flightId}` : ""}</strong> — ${outbound.origin} → ${
                outbound.destination
            }${ret ? ` ⇄ ${ret.origin} → ${ret.destination}` : ""}</p>
                    <p>Outbound: ${outbound.departureDate} ${outbound.departureTime} | ${outbound.arrivalDate} ${
                outbound.arrivalTime
            }</p>
                    ${
                        ret
                            ? `<p>Return: ${ret.departureDate} ${ret.departureTime} | ${ret.arrivalDate} ${ret.arrivalTime}</p>`
                            : ""
                    }
                    <p>Price (Adult): $${adultCost.toFixed(2)} | Child: $${childCost.toFixed(
                2
            )} | Infant: $${infantCost.toFixed(2)}</p>
                    <p>Passengers: Adults ${adults}, Children ${children}, Infants ${infants}</p>
                    <h4>Total: $${totalPrice.toFixed(2)}</h4>
                </div>
            `;

            const form = document.createElement("form");
            form.id = "booking-form";
            form.className = "booking-form";

            for (let i = 0; i < totalPax; i++) {
                const paxType = i < adults ? "Adult" : i < adults + children ? "Child" : "Infant";
                const group = document.createElement("div");
                group.className = "flex-row";
                group.innerHTML = `
                <label>Passenger ${i + 1} (${paxType})</label>
                <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:14rem;">
                    <input type="text" placeholder="First Name" required pattern="[A-Za-z]+" class="p-first" />
                    <input type="text" placeholder="Last Name" required pattern="[A-Za-z]+" class="p-last" />
                    <input type="date" placeholder="Date of Birth" required class="p-dob" />
                    <input type="text" placeholder="SSN (ddd-dd-dddd)" required class="p-ssn" />
                </div>`;
                form.appendChild(group);
            }

            const submit = document.createElement("button");
            submit.type = "submit";
            submit.textContent = "Book Flight";
            form.appendChild(submit);

            container.innerHTML += headerHtml;
            container.appendChild(form);

            form.addEventListener("submit", async (e) => {
                e.preventDefault();

                const ssnRe = /^\d{3}-\d{2}-\d{4}$/;
                const passengersList = [];
                let hasError = false;
                const groups = form.querySelectorAll(".flex-row");
                groups.forEach((g, idx) => {
                    const first = g.querySelector(".p-first").value.trim();
                    const last = g.querySelector(".p-last").value.trim();
                    const dob = g.querySelector(".p-dob").value.trim();
                    const ssn = g.querySelector(".p-ssn").value.trim();
                    if (!first || !last || !dob || !ssnRe.test(ssn)) {
                        hasError = true;
                    }
                    passengersList.push({ ssn, firstName: first, lastName: last, dob });
                });

                if (hasError) {
                    alert("Please complete all passenger details. SSN must be in the format ddd-dd-dddd.");
                    return;
                }

                const bookingNumber = `B${Math.random().toString(36).slice(2, 8).toUpperCase()}`;

                const summary = {
                    userId,
                    bookingNumber,
                    passengers: passengersList,
                };

                // Update seats in flights DB for outbound (and return if round-trip)
                try {
                    let flights = [];
                    try {
                        const cached = sessionStorage.getItem("fp_flights_db");
                        if (cached) flights = JSON.parse(cached);
                    } catch {}
                    if (!flights || flights.length === 0) {
                        const res = await fetch("db/flights.json", { cache: "no-store" });
                        if (res.ok) flights = await res.json();
                    }

                    const changedIds = [];
                    const updateSeatsFor = (flightId) => {
                        const idx = flights.findIndex((f) => f.flightId === flightId);
                        if (idx !== -1) {
                            const newSeats = Math.max(0, Number(flights[idx].availableSeats) - totalPax);
                            flights[idx].availableSeats = newSeats;
                            changedIds.push(flightId);
                        }
                    };

                    if (outbound && outbound.flightId) updateSeatsFor(outbound.flightId);
                    if (ret && ret.flightId) updateSeatsFor(ret.flightId);

                    try {
                        sessionStorage.setItem("fp_flights_db", JSON.stringify(flights));
                    } catch {}

                    // download updated DB
                    const blob = new Blob([JSON.stringify(flights, null, 2)], { type: "application/json" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "db/flights.json";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (err) {
                    console.warn("Could not update flights DB:", err);
                }

                // Show confirmation
                const details = `
                <h3>Booking Confirmed</h3>
                <p><strong>User ID:</strong> ${summary.userId}</p>
                <p><strong>Booking #:</strong> ${summary.bookingNumber}</p>
                ${
                    outbound
                        ? `<p><strong>Outbound:</strong> ${outbound.flightId} — ${outbound.origin} → ${outbound.destination} | ${outbound.departureDate} ${outbound.departureTime}</p>`
                        : ""
                }
                ${
                    ret
                        ? `<p><strong>Return:</strong> ${ret.flightId} — ${ret.origin} → ${ret.destination} | ${ret.departureDate} ${ret.departureTime}</p>`
                        : ""
                }
                <h4>Passengers</h4>
                <ul>
                    ${summary.passengers
                        .map((p) => `<li>${p.ssn} — ${p.firstName} ${p.lastName} (${p.dob})</li>`)
                        .join("")}
                </ul>
                <p><strong>Total Paid:</strong> $${totalPrice.toFixed(2)}</p>
                `;

                container.innerHTML = details;
                try {
                    sessionStorage.removeItem("fp_cart");
                } catch {}
                // Also create a booking JSON file for the flight(s)
                try {
                    const bookingRecord = {
                        userId: summary.userId,
                        bookingNumber: summary.bookingNumber,
                        totalPrice: totalPrice,
                        flights: {},
                    };
                    if (outbound) bookingRecord.flights.outbound = outbound;
                    if (ret) bookingRecord.flights.return = ret;
                    bookingRecord.passengers = summary.passengers;
                    const blobBooking = new Blob([JSON.stringify(bookingRecord, null, 2)], {
                        type: "application/json",
                    });
                    const urlBooking = URL.createObjectURL(blobBooking);
                    const aBooking = document.createElement("a");
                    aBooking.href = urlBooking;
                    aBooking.download = "flight-booking.json";
                    document.body.appendChild(aBooking);
                    aBooking.click();
                    document.body.removeChild(aBooking);
                    URL.revokeObjectURL(urlBooking);
                    try {
                        // append to booking_history in sessionStorage
                        const raw = sessionStorage.getItem("booking_history");
                        const history = raw ? JSON.parse(raw) : [];
                        history.push({ type: "flight", record: bookingRecord, timestamp: new Date().toISOString() });
                        sessionStorage.setItem("booking_history", JSON.stringify(history));
                    } catch (err) {
                        console.warn("Could not save booking history:", err);
                    }
                } catch (err) {
                    console.warn("Could not create flight booking file:", err);
                }
            });
        }
        // Rentals / Car booking rendering
        if (hasRentalCart) {
            const cart = rentals_cart;
            const car = cart.car;
            const checkIn = cart.checkIn_date;
            const checkOut = cart.checkOut_date;

            const headerHtml = `
                <div class="car-summary">
                    <h3>Selected Car</h3>
                    <p><strong>${car.id}</strong> — ${car.city} | ${car.type}</p>
                    <p>Check-In: ${checkIn}</p>
                    <p>Check-Out: ${checkOut}</p>
                    <p>Price per day: $${Number(
                        car.pricePerDay || car.pricePerDay || car.pricePerDay || car.pricePerDay || car.pricePerDay || 0
                    )}</p>
                </div>
            `;
            container.innerHTML += headerHtml;

            const bookBtn = document.createElement("button");
            bookBtn.textContent = "Book Car";
            container.appendChild(bookBtn);
            bookBtn.addEventListener("click", () => {
                const bookingNumber = `B${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
                const carBooking = {
                    userId,
                    bookingNumber,
                    carId: car.id,
                    city: car.city,
                    type: car.type,
                    checkIn_date: checkIn,
                    checkOut_date: checkOut,
                    pricePerDay:
                        car.pricePerDay ||
                        car.pricePerDay ||
                        car.pricePerDay ||
                        car.pricePerDay ||
                        car.pricePerDay ||
                        0,
                };
                // download booking JSON
                const blob = new Blob([JSON.stringify(carBooking, null, 2)], { type: "application/json" });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "car-booking.json";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                try {
                    // append car booking to booking_history
                    const raw = sessionStorage.getItem("booking_history");
                    const history = raw ? JSON.parse(raw) : [];
                    history.push({ type: "car", record: carBooking, timestamp: new Date().toISOString() });
                    sessionStorage.setItem("booking_history", JSON.stringify(history));
                } catch (err) {
                    console.warn("Could not save booking history:", err);
                }

                try {
                    var xml = new XMLHttpRequest();
                    xml.open("GET", "db/rental_cars.xml", false);
                    xml.send();
                    var carData = xml.responseXML;
                    if (carData) {
                        carData = new DOMParser().parseFromString(xml.responseText, "text/xml");
                        var carList = carData.getElementsByTagName("Car");
                        for (const c of carList) {
                            if (c.getAttribute("id") === car.id) {
                                let inNode = c.getElementsByTagName("checkInDate")[0];
                                let outNode = c.getElementsByTagName("checkOutDate")[0];
                                if (inNode) inNode.textContent = checkIn;
                                if (outNode) outNode.textContent = checkOut;
                                break;
                            }
                        }
                        const serializer = new XMLSerializer();
                        const updatedXMLString = serializer.serializeToString(carData);
                        const blobXML = new Blob([updatedXMLString], { type: "application/xml" });
                        const urlXML = URL.createObjectURL(blobXML);
                        const aXML = document.createElement("a");
                        aXML.href = urlXML;
                        aXML.download = "rental_cars.xml";
                        document.body.appendChild(aXML);
                        aXML.click();
                        document.body.removeChild(aXML);
                        URL.revokeObjectURL(urlXML);
                    }
                } catch (err) {
                    console.warn("Could not update rental_cars.xml", err);
                }

                try {
                    sessionStorage.removeItem("rentals_cart");
                } catch {}
                container.innerHTML = `<h3>Car booked</h3><p>Booking #: ${bookingNumber}</p>`;
            });
        }
    }
});
