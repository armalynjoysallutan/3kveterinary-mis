document.addEventListener("DOMContentLoaded", function () {
    const layout = document.getElementById("appointmentLayout");
    const menuToggle = document.getElementById("menuToggle");

    console.log("layout:", layout);
    console.log("menuToggle:", menuToggle);

    if (!layout || !menuToggle) return;

    menuToggle.addEventListener("click", function () {
        layout.classList.toggle("sidebar-collapsed");
        console.log("clicked, collapsed:", layout.classList.contains("sidebar-collapsed"));
    });
});