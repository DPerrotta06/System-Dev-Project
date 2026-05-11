document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".menu-toggle");
    toggles.forEach(toggle => {
        toggle.addEventListener("click", function () {
            const target = document.getElementById(this.dataset.target);
            if (!target) {
                return;
            }
            const isHidden = target.classList.contains("hidden");
            toggles.forEach(t => {
                const otherId = t.dataset.target;
                const other  = document.getElementById(otherId);
                if(other){
                    other.classList.add("hidden");
                    t.textContent = t.textContent.replace("▲", "▼");
                }
            });
            if(isHidden){
                target.classList.remove("hidden");
                this.textContent = this.textContent.replace("▼", "▲");
            }
        });
    });
});