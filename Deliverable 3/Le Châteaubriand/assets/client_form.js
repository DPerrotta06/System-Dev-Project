document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".menu-toggle");
    toggles.forEach(toggle => {
        toggle.addEventListener("click", function () {
            const target = document.getElementById(this.dataset.target);
            if (!target) {
                return;
            }
            const isHidden = target.classList.contains("hidden");
            target.classList.toggle("hidden", !isHidden);

            this.textContent = isHidden
                ? this.textContent.replace("▼", "▲")
                : this.textContent.replace("▲", "▼");
        });
    });
});