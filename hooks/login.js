/**
 * @param {SubmitEvent} ev
 */
async function handleLoginSubmit(ev) {
    ev.preventDefault();
    const data = new FormData(ev.target, ev.submitter);
    const errors = [];

    const phoneNumber = data.get("phone-number")?.trim() ?? "";
    const password = data.get("password") ?? "";

    if (!phoneNumber) {
        errors.push("Phone Number is required.");
    }
    if (!password) {
        errors.push("Password is required.");
    }

    if (phoneNumber && !/^\d{3}-\d{3}-\d{4}$/.test(phoneNumber)) {
        errors.push("Phone number must be formatted as ddd-ddd-dddd (e.g., 123-456-7890).");
    }

    if (errors.length === 0) {
        try {
            const res = await fetch("/php/login.php", {
                method: "POST",
                body: JSON.stringify({
                    phoneNumber,
                    password,
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
            } else {
                // Success - redirect to index.php
                window.location.href = "/index.php";
                return;
            }
        } catch (err) {
            errors.push(`Server returned error: ${err.message}`);
        }
    }

    const errorDiv = document.getElementById("login-errors");
    errorDiv.innerHTML = "";
    if (errors.length > 0) {
        errorDiv.innerHTML = "<ul>" + errors.map((error) => `<li>${error}</li>`).join("") + "</ul>";
        return;
    }
}

document.getElementById("login-form").addEventListener("submit", handleLoginSubmit);

