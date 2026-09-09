<?php
session_start();

/*
|--------------------------------------------------------------------------
| CUSTOMER LOGIN STATUS
|--------------------------------------------------------------------------
*/

$is_customer_logged_in =
    isset($_SESSION["account_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "Customer";


/*
|--------------------------------------------------------------------------
| CUSTOMER NAME
|--------------------------------------------------------------------------
*/

$customer_name = $_SESSION["customer_name"] ?? "Customer";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>3K Pet Solution - About Us</title>

    <link
        rel="stylesheet"
        href="./css/about.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="site-header">


    <!-- LOGO AREA -->

    <div class="header-logo">

        <a href="./about.php">

            <img
                src="../assets/images/logo.png"
                alt="3K Pet Solution Logo"
            >

        </a>

    </div>


    <!-- NAVIGATION -->

    <nav class="main-nav">

        <a
            href="./about.php"
            class="active"
        >
            About Us
        </a>

        <a href="./services.php">
            Services
        </a>

        <a href="./team.php">
            Our Team
        </a>

        <a href="./appointments.php">
            Appointment
        </a>

        <a href="./products.php">
            Products
        </a>

    </nav>


    <!-- ACCOUNT / PROFILE -->

    <div class="profile-area">

        <?php if ($is_customer_logged_in): ?>

            <!-- LOGGED-IN CUSTOMER -->
            <button
                type="button"
                class="profile-button"
                id="profileButton"
                aria-label="Open customer profile menu"
            >

                <img
                    src="../assets/images/default-user.png"
                    alt="Customer Profile"
                >

            </button>


            <div
                class="profile-menu"
                id="profileMenu"
            >

                <div class="profile-name">
                    <?= htmlspecialchars($customer_name) ?>
                </div>


                <a href="./profile.php">
                    My Profile
                </a>


                <a href="../process/logout.php">
                    Logout
                </a>

            </div>

        <?php else: ?>

            <!-- GUEST -->
            <a
                href="./login.php"
                class="guest-auth-button"
            >
                Sign Up / Login
            </a>

        <?php endif; ?>

    </div>


</header>



<main>


<!-- =========================================================
     HERO SECTION
========================================================= -->

<section class="hero-section">


    <div class="hero-content">


        <div class="hero-text">

            <h1>

                <span>
                    Where every paw, purr,
                </span>

                <span>
                    heartbeat
                </span>

                is treated
                <br>

                like family.

            </h1>


            <p>

                Compassionate care you can trust for
                your beloved pets.

            </p>


            <a
                href="./appointments.php"
                class="hero-button"
            >

                Book Appointment

            </a>

        </div>


        <div class="hero-image">

            <img
                src="../assets/images/customer/hero-vets.png"
                alt="Veterinarians caring for pets"
            >

        </div>


    </div>


</section>



<!-- =========================================================
     STATISTICS
========================================================= -->

<section class="stats-section">


    <div class="stats-container">


        <div class="stat-card">

            <strong>
                100%
            </strong>

            <span>
                Certified<br>
                Veterinarians
            </span>

        </div>


        <div class="stat-card">

            <strong>
                10+
            </strong>

            <span>
                Years Of<br>
                Experience
            </span>

        </div>


        <div class="stat-card">

            <strong>
                10K+
            </strong>

            <span>
                Happy Pets
            </span>

        </div>


    </div>


</section>



<!-- =========================================================
     CLINIC IMAGE + MISSION/VISION
========================================================= -->

<section class="clinic-section">


    <div class="clinic-background">


        <div class="clinic-image-wrapper">

            <img
                src="../assets/images/customer/clinic.png"
                alt="3K Pet Animal Clinic"
            >

        </div>


        <div class="mission-vision">


            <article class="mission-card">

                <h2>
                    MISSION
                </h2>

                <p>

                    "To provide compassionate, comprehensive
                    veterinary care that treats every pet like
                    family, empowers pet parents with knowledge,
                    and advances the wellbeing of animals in our
                    community through excellence, innovation,
                    and unconditional love."

                </p>

            </article>


            <article class="vision-card">

                <h2>
                    VISION
                </h2>

                <p>

                    "To be a trusted leader in veterinary
                    healthcare, recognized for delivering
                    innovative, high-quality, and compassionate
                    services that strengthen the bond between
                    pets and their families, while setting the
                    standard for excellence in animal care."

                </p>

            </article>


        </div>


    </div>


</section>



<!-- =========================================================
     WHY CHOOSE US
========================================================= -->

<section class="why-section">


    <h2 class="section-title">
        Why Choose us?
    </h2>


    <div class="why-cards">


        <!-- TRUST -->

        <article class="why-card trust-card">

            <img
                src="../assets/images/customer/trust.png"
                alt="Trust and transparency"
            >


            <div class="why-card-content">

                <h3>
                    Trust and Transparency
                </h3>

                <p>

                    Open communication and lasting
                    relationships through honest care.

                </p>

            </div>

        </article>



        <!-- MEDICAL -->

        <article class="why-card medical-card">

            <img
                src="../assets/images/customer/medical.png"
                alt="Medical expertise"
            >


            <div class="why-card-content">

                <h3>
                    Medical Expertise
                </h3>

                <p>

                    Board-certified veterinarians with
                    decades of combined experience.

                </p>

            </div>

        </article>



        <!-- PET CENTERED -->

        <article class="why-card pets-card">

            <img
                src="../assets/images/customer/pets.png"
                alt="Pet-centered approach"
            >


            <div class="why-card-content">

                <h3>
                    Pet-centered approach
                </h3>

                <p>

                    Tailored treatment plans for your
                    pet's unique needs.

                </p>

            </div>

        </article>


    </div>


</section>



<!-- =========================================================
     ABOUT US
========================================================= -->

<section class="about-section">


    <div class="about-content">


        <div class="about-text">

            <h2>
                About Us
            </h2>


            <p>

                At 3K Pet Animal Clinic, we treat every pet
                like family. Our clinic is dedicated to
                providing compassionate, high quality
                veterinary care to ensure the health and
                happiness of your beloved companions.

            </p>


            <p>

                With years of experience and a team of
                certified veterinarians, we offer a wide range
                of services - from routine check-ups and
                vaccinations to surgeries and treatment.

            </p>

        </div>


        <div class="about-images">


            <img
                src="../assets/images/customer/about-pet-1.png"
                alt="Pet receiving veterinary care"
                class="about-image-one"
            >


            <img
                src="../assets/images/customer/about-pet-2.png"
                alt="Puppy receiving veterinary care"
                class="about-image-two"
            >


        </div>


    </div>


</section>



<!-- =========================================================
     CLINIC CARE IMAGE
========================================================= -->

<section class="clinic-care-section">


    <div class="clinic-care-container">

        <img
            src="../assets/images/customer/clinic.png"
            alt="3K Pet Animal Clinic"
        >

    </div>


</section>



</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="site-footer">


    <div class="footer-container">


        <!-- ABOUT -->

        <div class="footer-column footer-about">

            <h3>
                About 3K Pet Animal Clinic
            </h3>

            <p>

                Caring for every paw with expert veterinary
                services.Your pet's health and happiness are
                our top priority.

            </p>


            <hr>


            <h3>
                Contact info
            </h3>

            <p>

                2ND Floor R. Basa corner Lopez Jaena St.<br>
                Cavite City

            </p>

            <p>
                046-484-4371 / 09286939761 / 09065653525
            </p>

            <p>
                docferdinand@gmail.com
            </p>

        </div>



        <!-- LOGO -->

        <div class="footer-column footer-logo">

            <img
                src="../assets/images/logo.png"
                alt="3K Pet Solution Logo"
            >

        </div>



        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h3>
                Quick links
            </h3>

            <a href="./about.php">
                About Us
            </a>

            <a href="./services.php">
                Services
            </a>

            <a href="./team.php">
                Our Team
            </a>

            <a href="./appointments.php">
                Appointment
            </a>

            <a href="./products.php">
                Products
            </a>

        </div>



        <!-- CLINIC HOURS -->

        <div class="footer-column footer-hours">

            <h3>
                Clinic Hours
            </h3>

            <p>
                8:00 AM to 4:00 PM
            </p>

        </div>



        <!-- SOCIALS -->

        <div class="footer-column footer-socials">

            <h3>
                Socials
            </h3>

            <a
                href="#"
                class="facebook-link"
                onclick="return false;"
            >

                <span class="facebook-icon">
                    f
                </span>

                3K Pet Solution Animal Clinic

            </a>

        </div>


    </div>


    <div class="footer-bottom">

        <p>
            2026 3K Pet Solution Animal Clinic. All rights reserved.
        </p>

    </div>


</footer>



<script src="./js/about.js"></script>

</body>

</html>