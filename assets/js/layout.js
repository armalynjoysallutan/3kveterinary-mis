document.addEventListener("DOMContentLoaded", () => {

    const container = document.querySelector(".container");
    const menuToggle = document.getElementById("menu-toggle");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    if (!container || !menuToggle) return;

    // Open / Close sidebar
    menuToggle.addEventListener("click", () => {
        container.classList.toggle("sidebar-open");
    });

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", () => {
            container.classList.remove("sidebar-open");
        });
    }

});