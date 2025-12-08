document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("cart-content");
    if (!container) return;
    container.innerHTML = ``;

    let flight_cart = null;
    let hotel_cart = null;
    let rental_cart = null;

    // generate user id
    const randId = `U${Date.now().toString(36)}`;
    const userId = sessionStorage.getItem("userId");
    if (userId === null) {
        sessionStorage.setItem("userId", randId);
    }

    try {
        flight_cart = JSON.parse(sessionStorage.getItem("fp_cart"));
        hotel_cart = JSON.parse(sessionStorage.getItem("hotels_cart"));
        rental_cart = JSON.parse(sessionStorage.getItem("rentals_cart"));
    } catch {}

    const hasFlightCart = Boolean(
        flight_cart && (flight_cart.flight || flight_cart.flights || flight_cart.tripType === "round-trip")
    );
    /* const hasRentalCart = Boolean(rentals_cart && rentals_cart.car); */

    // if (!hasFlightCart && !hotel_cart) {
    if (!hasFlightCart && !hotel_cart && !rental_cart) {
        container.innerHTML = `<p>Your cart is empty. Go to the <a href="stays.php">Stays</a> page to book a hotel, the <a href="flights.php">Flights</a> page to add a flight, or the <a href="cars.php">Cars</a> page to book a car.</p>`;
        return;
    } else {
        if (hotel_cart) {
            run_HotelCart(userId, hotel_cart, container, false);

            document.addEventListener('click', function (event) {
                if (event.target.matches('#hotel-submit')) {
                    run_HotelCart(userId, hotel_cart, container, true);
                }
            });

            if (flight_cart || rental_cart) {
                const border = document.createElement('p');
                border.style.borderBottom = "2px dashed black";
                border.style.margin = "22px 0 0 -10px";
                container.appendChild(border);
            }
        }
        let flightContainer = document.getElementById('flight-container');
        if (!flightContainer) {
            flightContainer = document.createElement('div');
            flightContainer.setAttribute('id', 'flight-container');
            container.appendChild(flightContainer);
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
                flightContainer.innerHTML += `<p>Selected flight data is missing or invalid.</p>`;
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
                    <p><strong>Departing Flight:</strong>&emsp;Departing&ensp;${outbound.departureDate} ${outbound.departureTime}&ensp;|&ensp;Arrival&ensp;${outbound.arrivalDate} ${
                outbound.arrivalTime
            }</p>
                    ${
                        ret
                            ? `<p><strong>Returning Flight:</strong>&emsp;Departing&ensp;${ret.departureDate} ${ret.departureTime}&ensp;|&ensp;Arrival&ensp;${ret.arrivalDate} ${ret.arrivalTime}</p>`
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
            submit.id = "flight-submit";
            submit.type = "submit";
            submit.textContent = "Book Flight";
            form.appendChild(submit);

            flightContainer.innerHTML += headerHtml;
            flightContainer.appendChild(form);
            
            // ssn input formatter to enforce ddd-dd-dddd as user types
            const ssnInput = document.querySelector(".p-ssn");
            document.addEventListener("input", function(e) {
                if (e.target.matches(".p-ssn")) {
                    const digits = ssnInput.value.replace(/\D/g, "").slice(0, 9);
                    const parts = [];
                    if (digits.length > 0) parts.push(digits.slice(0, Math.min(3, digits.length)));
                    if (digits.length >= 3) parts[0] += "-";
                    if (digits.length > 3) parts.push(digits.slice(3, Math.min(5, digits.length)));
                    if (digits.length >= 5) parts[1] += "-";
                    if (digits.length > 5) parts.push(digits.slice(5));
                    ssnInput.value = parts.join("");
                }
            });

            document.addEventListener("submit", async (e) => {
                e.preventDefault();
                if (e.submitter.matches('#flight-submit')) {
                    const ssnRe = /^\d{3}-\d{2}-\d{4}$/;
                    const passengersList = [];
                    let hasError = false;

                    const groups = document.querySelector("#booking-form").querySelectorAll(".flex-row");
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
                    let flights = [];
                    try {
                        const cached = sessionStorage.getItem("fp_flights_db");
                        if (cached) flights = JSON.parse(cached);
                    } catch {}
                    if (!flights || flights.length === 0) {
                        const res = await fetch("flights.json", { cache: "no-store" });
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

                    try {
                        sessionStorage.setItem("fp_flights_db", JSON.stringify(flights));
                    } catch {}

                    const bookings = [];
                    const bookingsCopy = [];
                    if (outbound && outbound.flightId) {
                        bookings.push({ flightId: outbound.flightId, passengerCount: totalPax });
                        bookingsCopy.push({ flightId: outbound.flightId, passengerCount: totalPax, currentAvailableSeats: flights[flights.findIndex((f) => f.flightId === outbound.flightId)].availableSeats });
                        updateSeatsFor(outbound.flightId);
                    }
                    if (ret && ret.flightId) {
                        bookings.push({ flightId: ret.flightId, passengerCount: totalPax });
                        bookingsCopy.push({ flightId: ret.flightId, passengerCount: totalPax, currentAvailableSeats: flights[flights.findIndex((f) => f.flightId === ret.flightId)].availableSeats });
                        updateSeatsFor(ret.flightId);
                    }

                    // Show confirmation
                    const details = `
                    <h3>Flight Booking Confirmed!</h3>
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
                    <h4 id="flightCountdownDisplay"></h4>
                    `;

                    document.getElementById('flight-container').innerHTML = details;
                    
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
                        const bookingRecordCopy = bookingRecord.passengers;

                        for (let i = 0; i < totalPax; i++) {
                            const passengerCost = i < adults ? adultCost : i < adults + children ? childCost : infantCost;
                            const passengerType = i < adults ? "Adult" : i < adults + children ? "Child" : "Infant";
                            bookingRecordCopy[i].price = passengerCost;
                            bookingRecordCopy[i].type = passengerType;
                        }

                        const usedPhp = await postPhpOrDownload({
                            body: JSON.stringify({ bookings: bookingsCopy, userId, bookingNumber, totalPrice, passengers: bookingRecordCopy }),
                            url: '/php/book-flight.php',
                            fetchContentType: 'application/json',
                            fallback: JSON.stringify(flights, null, 2),
                            fallbackType: 'application/json',
                            fallbackName: 'flights.json',
                        });

                        if (!usedPhp) {
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
                        }
                    } catch (err) {
                        console.warn("Could not create flight booking file:", err);
                    }

                    let count = 15;
                    const countdownElement = document.getElementById('flightCountdownDisplay');
                    countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

                    if (rental_cart) {
                        const border = document.createElement('p');
                        border.style.borderBottom = "2px dashed black";
                        border.style.margin = "22px 0 0 -10px";
                        document.getElementById('flight-container').appendChild(border);
                    }

                    const countdownInterval = setInterval(() => {
                        count--;
                        countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

                        if (count <= 0) {
                            clearInterval(countdownInterval);
                            countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";
                            location.reload();
                        }
                    }, 1000);
                }
            });

            if (rental_cart) {
                const border = document.createElement('p');
                border.style.borderBottom = "2px dashed black";
                border.style.margin = "22px 0 0 -10px";
                document.getElementById('flight-container').appendChild(border);
            }
        }
        // Rentals / Car booking rendering
        if (rental_cart) {
            const cart = rental_cart;
            const car = cart.car;
            const checkIn = cart.checkIn_date;
            const checkOut = cart.checkOut_date;
            const numDays = (new Date(checkOut) - new Date(checkIn)) / (1000 * 60 * 60 * 24);
            const price = Number(
                        car.pricePerDay || car.pricePerDay || car.pricePerDay || car.pricePerDay || car.pricePerDay || 0
                    );
            const totalPrice = price * numDays;

            const headerHtml = `
                <div id="car-container">
                    <div class="car-summary">
                        <h3>Selected Car</h3>
                        <p><strong>Car ID: ${car.id}</strong></p>
                        <p>City: ${car.city} | Type: ${car.type}</p>
                        <p>Check-In Date: ${checkIn}</p>
                        <p>Check-Out Date: ${checkOut}</p>
                        <p>Price Per Day: $${price.toFixed(2)}</p>
                        <h4>Total: $${totalPrice.toFixed(2)}</h4>
                    </div>
                </div>
            `;
            container.innerHTML += headerHtml;
            const carContainer = document.getElementById('car-container');

            const bookBtn = document.createElement("button");
            bookBtn.id = "rental-submit";
            bookBtn.textContent = "Book Car";
            carContainer.appendChild(bookBtn);
            if (document.querySelector("#rental-submit")) {
                document.querySelector("#rental-submit").addEventListener("click", async () => {
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

                    const usedPhp = await postPhpOrDownload({
                        body: JSON.stringify(carBooking),
                        fetchContentType: 'application/json',
                        fallback: JSON.stringify(carBooking, null, 2),
                        fallbackType: 'application/json',
                        fallbackName: 'car-booking.json',
                        url: '/php/book-car.php'
                    });

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
                        if (carData && !usedPhp) {
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
                    carContainer.innerHTML = `<h3>Car Booking Confirmed!</h3>
                                              <p>Booking #: ${carBooking.bookingNumber}</p>
                                              <p>User ID: ${carBooking.userId}</p>
                                              <p>Car ID: ${carBooking.carId}</p>
                                              <p>City: ${carBooking.city}</p>
                                              <p>Type: ${carBooking.type}</p>
                                              <p>Check-In Date: ${carBooking.checkIn_date}</p>
                                              <p>Check-Out Date: ${carBooking.checkOut_date}</p>
                                              <p>Price Per Day: $${Number(carBooking.pricePerDay).toFixed(2)}</p>
                                              <h4>Total Paid: $${totalPrice.toFixed(2)}</h4>
                                              <h4 id="carCountdownDisplay"></h4>
                    `;

                    let count = 15;
                    const countdownElement = document.getElementById('carCountdownDisplay');
                    countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

                    const countdownInterval = setInterval(() => {
                        count--;
                        countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

                        if (count <= 0) {
                            clearInterval(countdownInterval);
                            countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";
                            location.reload();
                        }
                    }, 1000);
                });
            }
        }
    }
});

async function run_HotelCart(userId, cart, container, clicked) {
    const { hotel, checkIn_date, checkOut_date, passengers } = cart;
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

    if (!clicked) {
        const headerHtml = `
            <div id="hotel-container">
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
            </div>
        `;
        container.innerHTML += headerHtml;
        const hotelContainer = document.getElementById('hotel-container');

        const submit = document.createElement("button");
        submit.id = "hotel-submit";
        submit.type = "submit";
        submit.textContent = "Book Hotel";
        hotelContainer.appendChild(submit);
    } else {
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

        const usedPhp = await postPhpOrDownload({
            body: JSON.stringify(hotel_booking),
            url: '/php/book-hotel.php',
            fallback: JSON.stringify(hotel_booking, null, 2),
            fallbackType: 'application/json',
            fetchContentType: 'application/json',
            fallbackName: 'hotel-booking.json',
        })

        // update hotels.xml file
        const newSeats = Math.max(0, Number(hotel.num_rooms_available) - numRoomsNeeded);
        var xml = new XMLHttpRequest();
        xml.open("GET", "db/hotels.xml", false);
        xml.send();
        var hotelData = xml.responseXML;
        if (hotelData) {
            if (!usedPhp) {
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
            }
        } else {
            console.log("Could not find 'hotels.xml'!");
        }
        try {
            sessionStorage.removeItem("hotels_cart");
        } catch { }
        
        // Show confirmation
        const details = `
            <h3>Hotel Booking Confirmed!</h3>
            <p><strong>User ID:</strong> ${hotel_booking.user_id}</p>
            <p><strong>Booking #:</strong> ${hotel_booking.booking_number}</p>
            <p><strong>Hotel:</strong> ${hotel_booking.hotel_name} — ${hotel_booking.hotel_city} (${hotel_booking.hotel_id})</p>
            <p><strong>Check-In:</strong> ${hotel_booking.checkIn_date}</p>
            <p><strong>Check-Out:</strong> ${hotel_booking.checkOut_date}</p>
            <p><strong>Guests:</strong> Adults ${hotel_booking.adult_guests}, Children ${hotel_booking.children_guests}, Infants ${hotel_booking.infant_guests}</p>
            <p><strong>Rooms:</strong> ${hotel_booking.num_rooms_needed}</p>
            <p><strong>Total Paid:</strong> ${hotel_booking.total_price}</p>
            <h4 id="hotelCountdownDisplay"></h4>
        `;

        document.getElementById('hotel-container').innerHTML = details;
        
        let count = 15;
        const countdownElement = document.getElementById('hotelCountdownDisplay');
        countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

        const countdownInterval = setInterval(() => {
            count--;
            countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";

            if (count <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = "This page will automatically refresh after " + count + " seconds. You can refresh the page before if needed.";
                location.reload();
            }
        }, 1000);
    }
}

async function postPhpOrDownload({ body, fetchContentType, fallback, fallbackType, fallbackName, url, successText }) {
    const phpRes = await fetch('/php/status.php');
    let phpRunning = true;
    if (!phpRes.ok) phpRunning = false;
    const bodyText = await phpRes.text();
    if (bodyText.includes('php-not-running')) phpRunning = false;

    if (phpRunning) {
        const res = await fetch(url, {
            method: "POST",
            body,
            headers: {
              "Content-Type": fetchContentType,
            },
        });
    
        if (!res.ok) {
            alert(`Failed to submit form to PHP Server.\nError: (${res.status}) ${res.statusText}`);
            return;
        }

        if (successText) {
            alert(successText);
            return phpRunning;
        } else {
            return phpRunning;
        }
    } else if (fallback) {
        const blob = new Blob([fallback], { type: fallbackType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = fallbackName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        alert("Could not detect if the PHP Server is running, so you can download the updated data instead.");
        return phpRunning;
    }
}