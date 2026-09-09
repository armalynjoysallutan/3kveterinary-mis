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

    <title>3K Pet Solution - Services</title>

    <link
        rel="stylesheet"
        href="./css/services.css"
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

        <a href="./about.php">
            About Us
        </a>

        <a
            href="./services.php"
            class="active"
        >
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
     SERVICES HERO
========================================================= -->

<section class="services-hero">


    <div class="services-hero-overlay">


        <div class="services-hero-content">

            <h1>
                Your Pet’s Health Starts Here
            </h1>


            <p>
                From preventive care to advanced treatment,
                we deliver safe and reliable veterinary services.
            </p>

        </div>


    </div>


    <!-- WAVE -->

    <div class="hero-wave"></div>


</section>



<!-- =========================================================
     OUR SERVICES INTRO
========================================================= -->

<section class="services-intro">


    <div class="services-intro-content">


        <h2>
            Our Services
        </h2>


        <p>
            We offer comprehensive veterinary care tailored
            to meet all your pet's health needs.
        </p>


    </div>


</section>



<!-- =========================================================
     DATABASE SERVICES
========================================================= -->

<section class="services-list-section">


    <div
        id="servicesContainer"
        class="services-container"
    >

        <div class="services-loading">

            Loading services...

        </div>

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
            2026 3K Pet Solution Animal Clinic.
            All rights reserved.
        </p>

    </div>


</footer>



<script src="./js/services.js"></script>


</body>

</html>