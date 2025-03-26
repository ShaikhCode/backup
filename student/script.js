const hamburger = document.querySelector(".hamburger");
const navbar = document.querySelector(".navbar");

hamburger.addEventListener("click", () => {
  hamburger.classList.toggle("active");
  navbar.classList.toggle("active");
});

document.addEventListener("DOMContentLoaded", function () {
  // Reset all forms on page load
  document.querySelectorAll("form").forEach((form) => form.reset());

  // Prevent form resubmission on refresh
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
});
