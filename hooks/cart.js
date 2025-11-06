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

    try {
        flight_cart = JSON.parse(sessionStorage.getItem("fp_cart"));
        hotel_cart = JSON.parse(sessionStorage.getItem("hotels_cart"));
    } catch {}

    if ((!flight_cart || !flight_cart.flight) && !hotel_cart) {
        container.innerHTML = `<p>Your cart is empty. Go to the <a href="flights.html">Flights</a> page to add a flight.</p>`;
        return;
    } else {
        if (hotel_cart) {
            const { hotel, checkIn_date, checkOut_date, passengers } = hotel_cart;
            console.log("Hotel ID: " + hotel.id + "\nHotel Name: " + hotel.name + "\nCheck-In Date: " + checkIn_date + "\nCheck-Out Date: " + checkOut_date + "\nNumber of Adults: " + Number(passengers?.adults || 0) + "\nNumber of Children: " + Number(passengers?.children || 0));
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
            submit.addEventListener('click', () => {            
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
                    total_price: "$" + totalPrice.toFixed(2)
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
                xml.open('GET', 'db/hotels.xml', false);
                xml.send();
                var hotelData = xml.responseXML;
                if (hotelData) {
                    hotelData = (new DOMParser()).parseFromString(xml.responseText, 'text/xml');
                    var hotelList = hotelData.getElementsByTagName("Hotel");
                    for (const hotels of hotelList) {
                        if (hotels.getAttribute("id") === hotel.id) {
                            hotels.getElementsByTagName("numAvailableRooms")[0].textContent = newSeats;
                            break;
                        }
                    }
                    const serializer = new XMLSerializer();
                    const updatedXMLString = serializer.serializeToString(hotelData);

                    const blob = new Blob([updatedXMLString], { type: 'application/xml' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'hotels.xml';
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
                } catch { }
            });
        }
        if (flight_cart || flight_cart.flight) {
            const { flight, passengers } = flight_cart;
            const adults = Number(passengers?.adults || 0);
            const children = Number(passengers?.children || 0);
            const infants = Number(passengers?.infants || 0);
            const totalPax = adults + children + infants;

            const adultCost = flight.price;
            const childCost = flight.price * 0.7;
            const infantCost = flight.price * 0.1;
            const totalPrice = adults * adultCost + children * childCost + infants * infantCost;

            const headerHtml = `
                <div class="flight-summary">
                    <h3>Selected Flight</h3>
                    <p><strong>${flight.flightId}</strong> — ${flight.origin} → ${flight.destination}</p>
                    <p>Departure: ${flight.departureDate} ${flight.departureTime} | Arrival: ${flight.arrivalDate} ${flight.arrivalTime
                            }</p>
                    <p>Price (Adult): $${adultCost.toFixed(2)} | Child: $${childCost.toFixed(2)} | Infant: $${infantCost.toFixed(
                                2
                            )}</p>
                    <p>Passengers: Adults ${adults}, Children ${children}, Infants ${infants}</p>
                    <h4>Total: $${totalPrice.toFixed(2)}</h4>
                </div>
            `;

            const form = document.createElement("form");
            form.id = "booking-form";
            form.className = "booking-form";

            const fields = [];
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
                fields.push(group);
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

                // validation for SSN format ###-##-####
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

                // Generate booking number
                // const userId = `U${Date.now().toString(36)}`;
                const bookingNumber = `B${Math.random().toString(36).slice(2, 8).toUpperCase()}`;

                const summary = {
                    userId,
                    bookingNumber,
                    flightId: flight.flightId,
                    origin: flight.origin,
                    destination: flight.destination,
                    departureDate: flight.departureDate,
                    arrivalDate: flight.arrivalDate,
                    departureTime: flight.departureTime,
                    arrivalTime: flight.arrivalTime,
                    passengers: passengersList,
                };

                // Update seats in a local copy of flights DB and trigger download
                try {
                    let flights = [];
                    try {
                        const cached = sessionStorage.getItem("fp_flights_db");
                        if (cached) flights = JSON.parse(cached);
                    } catch { }
                    if (!flights || flights.length === 0) {
                        const res = await fetch("db/flights.json", { cache: "no-store" });
                        if (res.ok) flights = await res.json();
                    }
                    const idx = flights.findIndex((f) => f.flightId === flight.flightId);
                    if (idx !== -1) {
                        const newSeats = Math.max(0, Number(flights[idx].availableSeats) - totalPax);
                        flights[idx].availableSeats = newSeats;
                        try {
                            sessionStorage.setItem("fp_flights_db", JSON.stringify(flights));
                        } catch { }
                        // download updated DB file to satisfy assignment "update the number of available seats"
                        const blob = new Blob([JSON.stringify(flights, null, 2)], { type: "application/json" });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url;
                        a.download = "db/flights.json";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }
                } catch (err) {
                    console.warn("Could not update flights DB:", err);
                }

                // Show confirmation
                const details = `
                <h3>Booking Confirmed</h3>
                <p><strong>User ID:</strong> ${summary.userId}</p>
                <p><strong>Booking #:</strong> ${summary.bookingNumber}</p>
                <p><strong>Flight:</strong> ${summary.flightId} — ${summary.origin} → ${summary.destination}</p>
                <p><strong>Departure:</strong> ${summary.departureDate} ${summary.departureTime}</p>
                <p><strong>Arrival:</strong> ${summary.arrivalDate} ${summary.arrivalTime}</p>
                <h4>Passengers</h4>
                <ul>
                    ${summary.passengers.map((p) => `<li>${p.ssn} — ${p.firstName} ${p.lastName} (${p.dob})</li>`).join("")}
                </ul>
                <p><strong>Total Paid:</strong> $${totalPrice.toFixed(2)}</p>
                `;

                container.innerHTML = details;
                try {
                    sessionStorage.removeItem("fp_cart");
                } catch { }
            });
        }
    }
});
