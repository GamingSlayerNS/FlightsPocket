document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("cart-content");
    if (!container) return;

    let cart = null;
    try {
        cart = JSON.parse(localStorage.getItem("fp_cart"));
    } catch {}

    if (!cart || !cart.flight) {
        container.innerHTML = `<p>Your cart is empty. Go to the <a href="flights.html">Flights</a> page to add a flight.</p>`;
        return;
    }

    const { flight, passengers } = cart;
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
      <p>Departure: ${flight.departureDate} ${flight.departureTime} | Arrival: ${flight.arrivalDate} ${
        flight.arrivalTime
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

    container.innerHTML = headerHtml;
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

        // Generate user id and booking number
        const userId = `U${Date.now().toString(36)}`;
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
                const cached = localStorage.getItem("fp_flights_db");
                if (cached) flights = JSON.parse(cached);
            } catch {}
            if (!flights || flights.length === 0) {
                const res = await fetch("db/flights.json", { cache: "no-store" });
                if (res.ok) flights = await res.json();
            }
            const idx = flights.findIndex((f) => f.flightId === flight.flightId);
            if (idx !== -1) {
                const newSeats = Math.max(0, Number(flights[idx].availableSeats) - totalPax);
                flights[idx].availableSeats = newSeats;
                try {
                    localStorage.setItem("fp_flights_db", JSON.stringify(flights));
                } catch {}
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
            localStorage.removeItem("fp_cart");
        } catch {}
    });
});
