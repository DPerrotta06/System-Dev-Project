document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".menu-toggle");
    toggles.forEach(toggle => {
        toggle.addEventListener("change", function () {
            const target = document.getElementById(this.dataset.target);
            target.classList.toggle("hidden", !this.checked);
        });
    });
});