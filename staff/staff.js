

// Toggle Navbar for Mobile View
const hamburger = document.getElementById("hamburger");
const navbar = document.querySelector(".navbar");
hamburger.addEventListener("click", () => {
  navbar.classList.toggle("active");
  hamburger.classList.toggle("active");
});

window.onload = function() {
  setTimeout(() => {
    document.getElementById("preloader").style.display = "none";
  }, 2000); // Delay for glowing effect
};

document.addEventListener("DOMContentLoaded", function () {
  // Reset all forms on page load
  document.querySelectorAll("form").forEach(form => form.reset());

  // Prevent form resubmission on refresh
  if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
  }
});
