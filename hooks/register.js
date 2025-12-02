/**
 * @param {SubmitEvent} ev
 */
async function handleRegisterSubmit(ev) {
    ev.preventDefault();
    const data = new FormData(ev.target, ev.submitter);
    const errors = [];

    const firstName = data.get("first-name")?.trim() ?? "";
    const lastName = data.get("last-name")?.trim() ?? "";
    const phoneNumber = data.get("phone-number")?.trim() ?? "";
    const dateOfBirth = data.get("date-of-birth") || "";
    const email = data.get("email")?.trim() ?? "";
    const password = data.get("password") ?? "";
    const confirmPassword = data.get("confirm-password") ?? "";
    const gender = data.get("gender");

    if (!firstName) {
        errors.push("First Name is required.");
    }
    if (!lastName) {
        errors.push("Last Name is required.");
    }
    if (!phoneNumber) {
        errors.push("Phone Number is required.");
    }
    if (!dateOfBirth) {
        errors.push("Date of Birth is required.");
    }
    if (!email) {
        errors.push("Email is required.");
    }
    if (!password) {
        errors.push("Password is required.");
    }
    if (!confirmPassword) {
        errors.push("Confirmation Password is required.");
    }

    if (phoneNumber && !/^\d{3}-\d{3}-\d{4}$/.test(phoneNumber)) {
        errors.push("Phone number must be formatted as ddd-ddd-dddd (e.g., 123-456-7890).");
    }

    if (password && password.length < 8) {
        errors.push("Password must be at least 8 characters long.");
    }

    if (password && confirmPassword && password !== confirmPassword) {
        errors.push("Passwords do not match.");
    }

    if (dateOfBirth) {
        const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        const match = dateOfBirth.match(datePattern);

        if (!match) {
            errors.push(
                "Date of birth must have 2 digits for month, 2 digits for day, and 4 digits for year (MM/DD/YYYY format)."
            );
        } else {
            const month = parseInt(match[1], 10);
            const day = parseInt(match[2], 10);
            const year = parseInt(match[3], 10);

            if (month < 1 || month > 12) {
                errors.push("Date of birth: Month must be between 01 and 12.");
            }

            if (day < 1 || day > 31) {
                errors.push("Date of birth: Day must be between 01 and 31.");
            }

            const currentYear = new Date().getFullYear();
            if (year > currentYear) {
                errors.push("Date of birth: Must be before " + currentYear + ".");
            }
        }
    }

    if (email && (!email.includes("@") || !email.endsWith(".com"))) {
        errors.push("Email must contain @ and .com");
    }

    if (errors.length === 0) {
        try {
            const res = await fetch("/php/register.php", {
                method: "POST",
                body: JSON.stringify({
                    firstName,
                    lastName,
                    phoneNumber,
                    dateOfBirth,
                    email,
                    password,
                    gender,
                }),
                headers: {
                    "Content-Type": "application/json",
                },
            });
            const json = await res.json();
            if (!json.success) {
                if ("errors" in json) {
                    errors.push(...json.errors);
                } else if ("error" in json) {
                    errors.push(json.error);
                } else {
                    errors.push("Server returned unknown error");
                }
            }
        } catch (err) {
            errors.push(`Server returned error: ${err.message}`);
        }
    }

    const errorDiv = document.getElementById("register-errors");
    errorDiv.innerHTML = "";
    if (errors.length > 0) {
        errorDiv.innerHTML = "<ul>" + errors.map((error) => `<li>${error}</li>`).join("") + "</ul>";
        return;
    }

    window.location.href = "/index.php";
}

document.getElementById("register-form").addEventListener("submit", handleRegisterSubmit);
