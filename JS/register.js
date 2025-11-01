// registration.js

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("multiStepForm");
  const steps = document.querySelectorAll(".form-step");
  const indicators = document.querySelectorAll(".step");
  let currentStep = 0;

  // Handle Next Step
  window.nextStep = (step) => {
    if (validateStep(step)) {
      steps[step - 1].classList.remove("active");
      steps[step].classList.add("active");

      indicators[step - 1].classList.remove("active");
      indicators[step].classList.add("active");

      currentStep++;
    }
  };

  // Handle Previous Step
  window.prevStep = (step) => {
    steps[step - 1].classList.remove("active");
    steps[step - 2].classList.add("active");

    indicators[step - 1].classList.remove("active");
    indicators[step - 2].classList.add("active");

    currentStep--;
  };

  // ✅ Form submit via Fetch API
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const submitBtn = document.getElementById("submitBtn");
    submitBtn.disabled = true;
    submitBtn.textContent = "Registering...";

    try {
      const response = await fetch("../PHP/register_action.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        alert(result.message);
        form.reset();
        window.location.href = "../project/login.php"; // redirect to login
      } else {
        if (result.errors && Object.keys(result.errors).length > 0) {
          displayErrors(result.errors);
        } else {
          alert(result.message);
          console.error(result.error_details || "No error details.");
        }
      }
    } catch (error) {
      console.error("Request failed:", error);
      alert("Something went wrong. Please try again later.");
    }

    submitBtn.disabled = false;
    submitBtn.textContent = "Register";
  });

  // Validate individual step
  function validateStep(step) {
    let valid = true;
    const fields = steps[step - 1].querySelectorAll("input, select");
    fields.forEach((field) => {
      const errorDiv = document.getElementById(field.id + "_error");
      if (errorDiv) errorDiv.textContent = "";
      if (field.hasAttribute("required") && !field.value.trim()) {
        if (errorDiv) errorDiv.textContent = "This field is required.";
        valid = false;
      }
    });
    return valid;
  }

  // Display server-side validation errors
  function displayErrors(errors) {
    Object.entries(errors).forEach(([key, message]) => {
      const errorDiv = document.getElementById(`${key}_error`);
      if (errorDiv) {
        errorDiv.textContent = message;
      }
    });
  }

  // Calculate age automatically
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  if (birthdateInput) {
    birthdateInput.addEventListener("change", () => {
      const birthdate = new Date(birthdateInput.value);
      const today = new Date();
      let age = today.getFullYear() - birthdate.getFullYear();
      const m = today.getMonth() - birthdate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
        age--;
      }
      ageInput.value = age;
    });
  }
});

// ✅ Password visibility toggle
function togglePassword(id) {
  const input = document.getElementById(id);
  input.type = input.type === "password" ? "text" : "password";
}
