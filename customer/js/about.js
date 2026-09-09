document.addEventListener("DOMContentLoaded", function () {


    // =====================================================
    // CUSTOMER PROFILE DROPDOWN
    // =====================================================

    const profileButton =
        document.getElementById("profileButton");

    const profileMenu =
        document.getElementById("profileMenu");


    if (profileButton && profileMenu) {

        profileButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                profileMenu.classList.toggle("show");

            }
        );


        document.addEventListener(
            "click",
            function (event) {

                if (
                    !profileMenu.contains(event.target) &&
                    !profileButton.contains(event.target)
                ) {

                    profileMenu.classList.remove("show");

                }

            }
        );

    }

});