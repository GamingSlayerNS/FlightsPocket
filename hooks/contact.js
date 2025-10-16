document.addEventListener("DOMContentLoaded", () => {
    const contactForm = document.getElementById("contact-form");
    const errorDiv = document.getElementById("contact-errors");

    if (contactForm) {
        contactForm.addEventListener("submit", (e) => {
            e.preventDefault();
            errorDiv.innerHTML = "";
            let errors = [];

            const firstName = document.getElementById("first-name").value;
            const lastName = document.getElementById("last-name").value;
            const phone = document.getElementById("phone").value;
            const gender = document.querySelector('input[name="gender"]:checked');
            const email = document.getElementById("email").value;
            const comment = document.getElementById("comment").value;

            // Validation
            if (!/^[A-Z][a-z]*$/.test(firstName)) {
                errors.push("First name must be alphabetic and start with a capital letter.");
            }
            if (!/^[A-Z][a-z]*$/.test(lastName)) {
                errors.push("Last name must be alphabetic and start with a capital letter.");
            }
            if (firstName === lastName) {
                errors.push("First and last name cannot be the same.");
            }
            if (!/^\(\d{3}\) \d{3}-\d{4}$/.test(phone)) {
                errors.push("Phone number must be in the format (ddd) ddd-dddd.");
            }
            if (!gender) {
                errors.push("Please select a gender.");
            }
            if (!/.*@.*\..*/.test(email)) {
                errors.push("Invalid email address.");
            }
            if (comment.length < 10) {
                errors.push("Comment must be at least 10 characters long.");
            }

            if (errors.length > 0) {
                errorDiv.innerHTML = errors.join("<br>");
            } else {
                alert("Form submitted successfully!\n\nFirst Name: " + firstName + "\nLast Name: " + lastName + "\nPhone Number: " + phone + "\nGender: " + gender.value + "\nEmail: " + email + "\nComment: " + comment);
                contactForm.reset();
            }
        });
    }
});
