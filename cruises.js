$(document).ready(function () {
    $("#cruise-form").on("submit", function (e) {
        e.preventDefault();
        $("#cruise-results").empty();
        let errors = [];

        const destination = $("#destination-cruise").val();
        const departFrom = new Date($("#depart-from").val());
        const departTo = new Date($("#depart-to").val());
        const durationMin = parseInt($("#duration-min").val());
        const durationMax = parseInt($("#duration-max").val());
        const adults = parseInt($("#adults-cruise").val());
        const children = parseInt($("#children-cruise").val());
        const infants = parseInt($("#infants-cruise").val());

        const startDate = new Date("2024-09-01");
        const endDate = new Date("2024-12-01");

        if (!destination) {
            errors.push("Please select a destination.");
        }
        if (departFrom < startDate || departFrom > endDate || departTo < startDate || departTo > endDate) {
            errors.push("Departure dates must be between Sep 1, 2024 and Dec 1, 2024.");
        }
        if (departTo < departFrom) {
            errors.push('The "departing to" date must be after the "departing from" date.');
        }
        if (durationMin < 3) {
            errors.push("Minimum duration cannot be less than 3 days.");
        }
        if (durationMax > 10) {
            errors.push("Maximum duration cannot be greater than 10 days.");
        }
        if (durationMin > durationMax) {
            errors.push("Minimum duration cannot be greater than maximum duration.");
        }

        const totalGuests = adults + children;
        if (totalGuests > 2 && infants === 0) {
            errors.push("Number of guests (adults + children) cannot be more than 2 per room.");
        }

        if (errors.length > 0) {
            $("#cruise-results").html(errors.join("<br>"));
        } else {
            let output = `<h3>Cruise Search Details</h3>
                          <p>Destination: ${destination}</p>
                          <p>Departing Between: ${departFrom.toDateString()} and ${departTo.toDateString()}</p>
                          <p>Duration: ${durationMin} to ${durationMax} days</p>
                          <p>Adults: ${adults}</p>
                          <p>Children: ${children}</p>
                          <p>Infants: ${infants}</p>`;
            $("#cruise-results").html(output);
        }
    });
});
