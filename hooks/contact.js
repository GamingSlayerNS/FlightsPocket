document.addEventListener("DOMContentLoaded", () => {
    const contactForm = document.getElementById("contact-form");
    const errorDiv = document.getElementById("contact-errors");
    // Phone input formatter to enforce (ddd)ddd-dddd as user types
    const phoneInput = document.getElementById("phone");
    if (phoneInput) {
        phoneInput.addEventListener("input", () => {
            const digits = phoneInput.value.replace(/\D/g, "").slice(0, 10);
            const parts = [];
            if (digits.length > 0) parts.push("(" + digits.slice(0, Math.min(3, digits.length)));
            if (digits.length >= 3) parts[0] += ") ";
            if (digits.length > 3) parts.push(digits.slice(3, Math.min(6, digits.length)));
            if (digits.length >= 6) parts[1] += "-";
            if (digits.length > 6) parts.push(digits.slice(6));
            phoneInput.value = parts.join("");
        });
    }

    if (contactForm) {
        contactForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            errorDiv.innerHTML = "";
            let errors = [];

            const firstName = document.getElementById("first-name").value.trim();
            const lastName = document.getElementById("last-name").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const gender = document.querySelector('input[name="gender"]:checked');
            const email = document.getElementById("email").value.trim();
            const comment = document.getElementById("comment").value.trim();

            // Track first invalid field for focusing
            let firstInvalidEl = null;

            // Validation
            if (!/^[A-Z][A-Za-z]*$/.test(firstName)) {
                errors.push("First name must be alphabetic and start with a capital letter.");
                firstInvalidEl = firstInvalidEl || document.getElementById("first-name");
            }
            if (!/^[A-Z][A-Za-z]*$/.test(lastName)) {
                errors.push("Last name must be alphabetic and start with a capital letter.");
                firstInvalidEl = firstInvalidEl || document.getElementById("last-name");
            }
            if (firstName && lastName && firstName === lastName) {
                errors.push("First and last name cannot be the same.");
                firstInvalidEl = firstInvalidEl || document.getElementById("last-name");
            }
            if (!/^\(\d{3}\)\s\d{3}-\d{4}$/.test(phone)) {
                errors.push("Phone number must be in the format (ddd) ddd-dddd.");
                firstInvalidEl = firstInvalidEl || document.getElementById("phone");
            }
            if (!gender) {
                errors.push("Please select a gender.");
                // radio group focus
                firstInvalidEl = firstInvalidEl || document.getElementById("male");
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push("Invalid email address.");
                firstInvalidEl = firstInvalidEl || document.getElementById("email");
            }
            if (comment.length < 10) {
                errors.push("Comment must be at least 10 characters long.");
                firstInvalidEl = firstInvalidEl || document.getElementById("comment");
            }

            if (errors.length > 0) {
                errorDiv.innerHTML = errors.join("<br>");
                errorDiv.scrollIntoView({ behavior: "smooth", block: "nearest" });
                if (firstInvalidEl && typeof firstInvalidEl.focus === "function") {
                    firstInvalidEl.focus();
                }
                return;
            }

            alert(
                "Form submitted successfully!\n\n" +
                    "First Name: " +
                    firstName +
                    "\n" +
                    "Last Name: " +
                    lastName +
                    "\n" +
                    "Phone Number: " +
                    phone +
                    "\n" +
                    "Gender: " +
                    gender.value +
                    "\n" +
                    "Email: " +
                    email +
                    "\n" +
                    "Comment: " +
                    comment
            );

            const contact = {
                firstname: firstName,
                lastname: lastName,
                phoneNum: phone,
                gender: gender.value,
                email: email,
                comment: comment,
            };

            // Load existing contacts, append, and download updated contact-file.json
            let contacts = [];
            try {
                const res = await fetch("db/contact-file.json", { cache: "no-store" });
                if (res.ok) {
                    const data = await res.json();
                    contacts = Array.isArray(data) ? data : data && typeof data === "object" ? [data] : [];
                }
            } catch (err) {
                console.warn("Could not read existing contacts. Creating a new list.", err);
            }
            contacts.push(contact);

            const blob = new Blob([JSON.stringify(contacts, null, 2)], { type: "application/json" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "db/contact-file.json"; // overwrite the same file when saving
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            console.log("Updated contact-file.json download initiated.");

            contactForm.reset();
        });
    }
});
