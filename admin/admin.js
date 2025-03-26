document.addEventListener("DOMContentLoaded", function() {
    const hamburger = document.querySelector(".hamburger");
    const navbar = document.querySelector(".navbar");
    const toggleButton = document.getElementById("toggleButton");
    const formContainer = document.getElementById("avt");
    const profileImg = document.getElementById("profile-img");

    // Toggle navbar menu
    hamburger.addEventListener("click", () => {
        navbar.classList.toggle("active");
        hamburger.classList.toggle("active");
    });

});

document.addEventListener("DOMContentLoaded", function () {
    // Reset all forms on page load
    document.querySelectorAll("form").forEach(form => form.reset());

    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
