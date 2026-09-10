/* =========================================
   SETTINGS PAGE
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const settingsCards =
    document.querySelectorAll(".settings-card");

settingsCards.forEach(function (card) {

    const footer =
        card.querySelector(".settings-card-footer");

    if (!footer) {
        return;
    }

    const link =
        card.querySelector("a[href]");

    const target =
        footer.getAttribute("data-href") ||
        (link ? link.getAttribute("href") : "");

    if (!target) {
        return;
    }

    card.addEventListener("click", function () {
        window.location.href = target;
    });

});

});