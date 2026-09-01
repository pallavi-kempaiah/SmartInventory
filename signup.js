const signupForm = document.getElementById("signupForm");

signupForm.addEventListener("submit", function (event) {

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (name === "") {
        event.preventDefault();
        alert("Please enter your full name.");
        return;
    }

    if (email === "") {
        event.preventDefault();
        alert("Please enter your email.");
        return;
    }

    if (password.length < 6) {
        event.preventDefault();
        alert("Password must contain at least 6 characters.");
        return;
    }

    if (password !== confirmPassword) {
        event.preventDefault();
        alert("Passwords do not match.");
        return;
    }

    // Validation passed.
    // The form will now be submitted to signup.php.
});
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        button.textContent = "👁️‍🗨️";
    } else {
        input.type = "password";
        button.textContent = "👁️";
    }
}