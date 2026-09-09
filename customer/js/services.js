document.addEventListener("DOMContentLoaded", function () {


    /* =========================================================
       PROFILE DROPDOWN
    ========================================================= */

    const profileButton =
        document.getElementById("profileButton");

    const profileMenu =
        document.getElementById("profileMenu");


    if (profileButton && profileMenu) {

        profileButton.addEventListener("click", function (event) {

            event.stopPropagation();

            profileMenu.classList.toggle("show");

        });


        document.addEventListener("click", function () {

            profileMenu.classList.remove("show");

        });

    }



    /* =========================================================
       SERVICE ICONS
    ========================================================= */

    const icons = {

        consultation: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <circle cx="12" cy="7" r="4"></circle>

                <path d="M4 21c0-4.2 3.6-7 8-7s8 2.8 8 7"></path>

            </svg>
        `,


        medication: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M10 3h4"></path>
                <path d="M12 3v5"></path>
                <path d="M8 8h8"></path>
                <path d="M7 8h10l1 12H6L7 8z"></path>
                <path d="M9 13h6"></path>

            </svg>
        `,


        confinement: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M3 21V5h18v16"></path>
                <path d="M3 9h18"></path>
                <path d="M8 5v16"></path>
                <path d="M16 5v16"></path>

            </svg>
        `,


        treatment: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M12 21s-7-4.5-7-11V5l7-3 7 3v5c0 6.5-7 11-7 11z"></path>
                <path d="M12 8v6"></path>
                <path d="M9 11h6"></path>

            </svg>
        `,


        vaccine: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M14 4l6 6"></path>
                <path d="M12 6l6 6"></path>
                <path d="M16 2l6 6"></path>
                <path d="M4 20l9-9"></path>
                <path d="M3 21l4-1-3-3-1 4z"></path>

            </svg>
        `,


        surgical: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M5 19l14-14"></path>
                <path d="M5 5l14 14"></path>

                <circle cx="5" cy="5" r="2"></circle>
                <circle cx="19" cy="19" r="2"></circle>

            </svg>
        `,


        laboratory: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M9 3h6"></path>

                <path d="M10 3v7l-5 8a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-8V3"></path>

                <path d="M8 15h8"></path>

            </svg>
        `,


        ultrasound: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <rect
                    x="3"
                    y="5"
                    width="18"
                    height="14"
                    rx="2"
                ></rect>

                <path d="M7 15c2-5 4-5 5-1s3 4 5-1"></path>

            </svg>
        `,


        dental: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M7 3c-2 1-3 3-2 6l2 10c.2 1 1.5 1.5 2.2.4L12 14l2.8 5.4c.7 1.1 2 .6 2.2-.4l2-10c1-3-1-5-3-6-1.5-.7-2.5 1-4 1S8.5 2.3 7 3z"></path>

            </svg>
        `,


        default: `
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <circle
                    cx="12"
                    cy="12"
                    r="9"
                ></circle>

                <path d="M12 8v8"></path>
                <path d="M8 12h8"></path>

            </svg>
        `
    };



    /* =========================================================
       GET ICON
    ========================================================= */

    function getIcon(serviceName) {

        const name =
            serviceName
                .toLowerCase()
                .trim();


        if (name.includes("consult")) {
            return icons.consultation;
        }


        if (
            name.includes("medication") ||
            name.includes("medicine")
        ) {
            return icons.medication;
        }


        if (
            name.includes("confinement") ||
            name.includes("boarding")
        ) {
            return icons.confinement;
        }


        if (
            name.includes("surgical") ||
            name.includes("surgery")
        ) {
            return icons.surgical;
        }


        if (
            name.includes("vaccine") ||
            name.includes("rabies") ||
            name.includes("9 in 1") ||
            name.includes("kennel cough") ||
            name.includes("quadcat")
        ) {
            return icons.vaccine;
        }


        if (
            name.includes("blood") ||
            name.includes("smear") ||
            name.includes("test") ||
            name.includes("laboratory") ||
            name.includes("lab")
        ) {
            return icons.laboratory;
        }


        if (name.includes("ultrasound")) {
            return icons.ultrasound;
        }


        if (
            name.includes("dental") ||
            name.includes("hygiene")
        ) {
            return icons.dental;
        }


        if (
            name.includes("treatment") ||
            name.includes("skin") ||
            name.includes("heartworm") ||
            name.includes("tick") ||
            name.includes("flea")
        ) {
            return icons.treatment;
        }


        return icons.default;
    }



    /* =========================================================
       SERVICE DESCRIPTIONS
    ========================================================= */

    function getDescription(serviceName) {

        const name =
            serviceName
                .toLowerCase()
                .trim();


        const descriptions = {

            "consultation":
                "Professional veterinary consultation to assess your pet's health and needs.",

            "medication":
                "Proper medication and veterinary guidance to support your pet's recovery.",

            "confinement":
                "Safe and supervised care for pets requiring monitoring and recovery.",

            "boarding":
                "Comfortable and supervised temporary care for your beloved pets.",

            "treatment":
                "Veterinary treatment designed to address your pet's health concerns.",

            "anti-rabies":
                "Rabies vaccination to help protect your pet from this serious disease.",

            "9 in 1 vaccine":
                "Comprehensive vaccination to help protect your pet against multiple diseases.",

            "kennel cough":
                "Vaccination and care to help protect pets from kennel cough.",

            "quadcat":
                "Vaccination designed to help protect cats against common infectious diseases.",

            "tablet deworming":
                "Deworming treatment to help protect your pet from intestinal parasites.",

            "paste deworming":
                "Easy-to-administer deworming treatment for parasite prevention.",

            "surgical procedures":
                "Veterinary surgical procedures performed with proper care and monitoring.",

            "skin disease treatment":
                "Veterinary care for common skin conditions affecting your pet.",

            "blood chem":
                "Laboratory testing to help evaluate your pet's internal health.",

            "smear test":
                "Diagnostic testing used to help identify possible health conditions.",

            "ultrasound":
                "Non-invasive diagnostic imaging to examine your pet's internal organs.",

            "test kit":
                "Diagnostic testing to help detect specific conditions affecting your pet.",

            "tick and flea treatment":
                "Treatment to help protect your pet from ticks, fleas, and related problems.",

            "heartworm":
                "Preventive care and treatment support against heartworm disease.",

            "dental hygiene":
                "Dental care to help maintain your pet's oral health."
        };


        if (descriptions[name]) {
            return descriptions[name];
        }


        for (const key in descriptions) {

            if (name.includes(key)) {
                return descriptions[key];
            }

        }


        return "Professional veterinary care provided by our dedicated veterinary team.";
    }



    /* =========================================================
       LOAD SERVICES
    ========================================================= */

    const servicesContainer =
        document.getElementById("servicesContainer");


    async function loadServices() {

        if (!servicesContainer) {
            return;
        }


        try {

            const response = await fetch(
                "../process/get_customer_services.php"
            );


            if (!response.ok) {
                throw new Error(
                    "Unable to load services."
                );
            }


            const data =
                await response.json();


            if (!data.success) {

                throw new Error(
                    data.message ||
                    "Unable to load services."
                );

            }


            if (
                !data.services ||
                data.services.length === 0
            ) {

                servicesContainer.innerHTML = `
                    <div class="no-services">
                        No services are currently available.
                    </div>
                `;

                return;
            }


            renderServices(data.services);


        } catch (error) {

            console.error(
                "Services Error:",
                error
            );


            servicesContainer.innerHTML = `
                <div class="services-error">
                    Unable to load services right now.
                    Please try again later.
                </div>
            `;

        }

    }



    /* =========================================================
       RENDER SERVICES
    ========================================================= */

    function renderServices(services) {

        const groupedServices = {};


        services.forEach(function (service) {

            const category =
                service.category_name ||
                "Veterinary Services";


            if (!groupedServices[category]) {

                groupedServices[category] = [];

            }


            groupedServices[category].push(service);

        });


        servicesContainer.innerHTML = "";


        Object.keys(groupedServices)
            .forEach(function (categoryName) {


                const categorySection =
                    document.createElement("section");


                categorySection.className =
                    "service-category";


                const categoryTitle =
                    document.createElement("h3");


                categoryTitle.className =
                    "category-title";


                categoryTitle.textContent =
                    categoryName;


                const servicesGrid =
                    document.createElement("div");


                servicesGrid.className =
                    "services-grid";


                groupedServices[categoryName]
                    .forEach(function (service) {


                        const card =
                            document.createElement("article");


                        card.className =
                            "service-card";


                        const icon =
                            document.createElement("div");


                        icon.className =
                            "service-icon";


                        icon.innerHTML =
                            getIcon(
                                service.service_name
                            );


                        const title =
                            document.createElement("h3");


                        title.textContent =
                            service.service_name;


                        const description =
                            document.createElement("p");


                        description.textContent =
                            getDescription(
                                service.service_name
                            );


                        card.appendChild(icon);

                        card.appendChild(title);

                        card.appendChild(description);

                        servicesGrid.appendChild(card);

                    });


                categorySection.appendChild(
                    categoryTitle
                );


                categorySection.appendChild(
                    servicesGrid
                );


                servicesContainer.appendChild(
                    categorySection
                );

            });

    }



    /* =========================================================
       START
    ========================================================= */

    loadServices();

});