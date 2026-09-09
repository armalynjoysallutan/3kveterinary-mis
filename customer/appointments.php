<?php
session_start();

/*
|--------------------------------------------------------------------------
| CUSTOMER ACCESS ONLY
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION["account_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Customer"
) {
    header("Location: ./login.php");
    exit();
}

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

    <title>
        3K Pet Solution - Appointment
    </title>

    <!-- SHARED CUSTOMER DESIGN -->
    <link
        rel="stylesheet"
        href="./css/about.css"
    >

    <!-- APPOINTMENT DESIGN -->
    <link
        rel="stylesheet"
        href="./css/appointments.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="site-header">


    <!-- LOGO -->

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

        <a href="./services.php">
            Services
        </a>

        <a href="./team.php">
            Our Team
        </a>

        <a
            href="./appointments.php"
            class="active"
        >
            Appointment
        </a>

        <a href="./products.php">
            Products
        </a>

    </nav>



    <!-- PROFILE -->

    <div class="profile-area">


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


    </div>


</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main>


    <!-- =====================================================
         APPOINTMENT HERO
    ====================================================== -->

    <section class="appointment-hero">


        <div class="appointment-hero-content">


            <h1>

                <span>
                    BOOK YOUR APPOINTMENT
                </span>

                WITH US

            </h1>


            <p>

                Schedule a veterinary appointment to ensure
                timely and appropriate care for your pet.

            </p>


        </div>


    </section>



    <!-- =====================================================
         APPOINTMENT MODULE
    ====================================================== -->

    <section class="appointment-page">


        <!-- MODULE TABS -->

        <div class="appointment-tabs">


            <a
                href="#"
                class="appointment-tab active"
            >

                <span class="tab-icon">
                    ▣
                </span>

                Appointment

            </a>


            <a
                href="#"
                class="appointment-tab"
                aria-disabled="true"
            >

                <span class="tab-icon">
                    ●
                </span>

                Pet Records

            </a>


            <a
                href="#"
                class="appointment-tab"
                aria-disabled="true"
            >

                <span class="tab-icon">
                    ▤
                </span>

                My Appointments

            </a>


        </div>



        <!-- BOOKING CARD -->

        <section class="booking-card">


            <!-- HEADING -->

            <div class="booking-heading">


                <h2>

                    <span class="heading-icon">
                        ▣
                    </span>

                    Book an Appointment

                </h2>


                <p>

                    Fill out the form below to book a visit
                    for your pet. We'll confirm your
                    appointment via email.

                </p>


            </div>



            <!-- REQUIRED NOTICE -->

            <div class="required-notice">

                <span class="notice-icon">
                    i
                </span>

                <span>

                    Please fill in all required fields
                    marked with <strong>*</strong>

                </span>

            </div>



            <!-- =================================================
                 CLIENT TYPE
            ================================================== -->

            <div class="client-type-section">


                <h3>
                    Client Type
                </h3>


                <div class="client-type-options">


                    <!-- EXISTING CLIENT -->

                    <button
                        type="button"
                        class="client-type-card selected"
                        data-client-type="existing"
                        aria-pressed="true"
                    >

                        <span class="client-type-icon">
                            👤
                        </span>


                        <span class="client-type-text">

                            <strong>
                                Existing Client
                            </strong>

                            <small>
                                Select an existing owner
                                and pet record.
                            </small>

                        </span>

                    </button>



                    <!-- NEW CLIENT -->

                    <button
                        type="button"
                        class="client-type-card"
                        data-client-type="new"
                        aria-pressed="false"
                    >

                        <span class="client-type-icon">
                            👤+
                        </span>


                        <span class="client-type-text">

                            <strong>
                                New Client
                            </strong>

                            <small>
                                Create a new owner
                                and pet record.
                            </small>

                        </span>

                    </button>


                </div>


            </div>



            <!-- =========================================================
                EXISTING CLIENT
            ========================================================= -->

            <section
                class="client-form-state"
                id="existingClientState"
            >


                <!-- =====================================================
                    EXISTING CLIENT
                ====================================================== -->

                <div class="form-section-divider">

                    <h3>
                        Existing Client
                    </h3>

                </div>


                <!-- =====================================================
                    SEARCH OWNER + SELECT PET
                ====================================================== -->

                <div class="form-row">

                    <!-- SEARCH OWNER -->

                    <div class="form-group">

                        <label for="existingOwner">

                            Search Owner
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="existingOwner"
                            name="existing_owner"
                            placeholder="Search owner name..."
                            autocomplete="off"
                        >


                        <div
                            class="owner-search-results"
                            id="ownerSearchResults"
                        ></div>

                    </div>


                    <!-- SELECT PET -->

                    <div class="form-group">

                        <label for="existingPet">

                            Select Pet
                            <span class="required">*</span>

                        </label>


                        <select
                            id="existingPet"
                            name="existing_pet"
                        >

                            <option value="">
                                Select Pet
                            </option>

                        </select>


                        <button
                            type="button"
                            class="add-pet-button"
                            id="addNewPetButton"
                        >
                            + Add New Pet
                        </button>

                    </div>

                </div>



                <!-- =====================================================
                    EXISTING CLIENT CONTENT
                    APPOINTMENT INFORMATION + PET INFORMATION
                ====================================================== -->

                <div class="existing-client-columns">


                    <!-- =================================================
                        APPOINTMENT INFORMATION
                    ================================================== -->

                    <div class="appointment-information-column">


                        <div class="form-section-divider">

                            <h3>
                                Appointment Information
                            </h3>

                        </div>


                        <!-- SERVICE CATEGORY + SERVICE -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingServiceCategory">

                                    Service Category
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingServiceCategory"
                                    name="existing_service_category"
                                >

                                    <option value="">
                                        Select Category
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="existingService">

                                    Service
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingService"
                                    name="existing_service"
                                    disabled
                                >

                                    <option value="">
                                        Select Service
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- PREFERRED DATE + PREFERRED TIME -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingPreferredDate">

                                    Preferred Date
                                    <span class="required">*</span>

                                </label>


                                <input
                                    type="date"
                                    id="existingPreferredDate"
                                    name="existing_preferred_date"
                                >

                            </div>


                            <div class="form-group">

                                <label for="existingPreferredTime">

                                    Preferred Time
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingPreferredTime"
                                    name="existing_preferred_time"
                                    disabled
                                >

                                    <option value="">
                                        Select a date first
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- APPOINTMENT TYPE + SERVICE AVAILABILITY -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingAppointmentType">

                                    Appointment Type
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingAppointmentType"
                                    name="existing_appointment_type"
                                >

                                    <option value="">
                                        Select Type
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Service Availability
                                </label>


                                <div
                                    class="service-availability"
                                    id="existingServiceAvailability"
                                >

                                    <div class="availability-icon">
                                        i
                                    </div>


                                    <div class="availability-content">

                                        <strong>
                                            Select a service first
                                        </strong>

                                        <span>
                                            Service schedule will appear here.
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- REASON FOR VISIT -->

                        <div class="form-group full-width">

                            <label for="existingReasonForVisit">
                                Reason for Visit
                            </label>


                            <textarea
                                id="existingReasonForVisit"
                                name="existing_reason_for_visit"
                                rows="4"
                                placeholder="Briefly describe the pet's condition..."
                            ></textarea>

                        </div>


                    </div>



                    <!-- =================================================
                        PET INFORMATION
                    ================================================== -->

                    <div class="pet-information-column">


                        <div class="form-section-divider">

                            <h3>
                                Pet Information
                            </h3>

                        </div>


                        <!-- PET NAME + SPECIES -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingPetName">

                                    Pet Name
                                    <span class="required">*</span>

                                </label>


                                <input
                                    type="text"
                                    id="existingPetName"
                                    name="existing_pet_name"
                                    placeholder="Enter pet's name"
                                >

                            </div>


                            <div class="form-group">

                                <label for="existingSpecies">

                                    Species
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingSpecies"
                                    name="existing_species"
                                >

                                    <option value="">
                                        Select Species
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- BREED + COLOR -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingBreed">

                                    Breed
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingBreed"
                                    name="existing_breed"
                                    disabled
                                >

                                    <option value="">
                                        Select Species first
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="existingColor">

                                    Color
                                    <span class="required">*</span>

                                </label>


                                <input
                                    type="text"
                                    id="existingColor"
                                    name="existing_color"
                                    placeholder="Enter pet's color"
                                >

                            </div>

                        </div>


                        <!-- SEX + WEIGHT -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="existingSex">

                                    Sex
                                    <span class="required">*</span>

                                </label>


                                <select
                                    id="existingSex"
                                    name="existing_sex"
                                >

                                    <option value="">
                                        Select Sex
                                    </option>

                                    <option value="Male">
                                        Male
                                    </option>

                                    <option value="Female">
                                        Female
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="existingWeight">
                                    Weight (kg)
                                </label>


                                <input
                                    type="number"
                                    id="existingWeight"
                                    name="existing_weight"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter weight"
                                >

                            </div>

                        </div>


                        <!-- DATE OF BIRTH -->

                        <div class="form-row single-field-row">

                            <div class="form-group">

                                <label for="existingDateOfBirth">
                                    Date of Birth
                                </label>


                                <input
                                    type="date"
                                    id="existingDateOfBirth"
                                    name="existing_date_of_birth"
                                >

                            </div>

                        </div>


                    </div>


                </div>



                <!-- =====================================================
                    EXISTING CLIENT ACTIONS
                ====================================================== -->

                <div class="form-actions">

                    <button
                        type="button"
                        class="cancel-button"
                        id="cancelExistingAppointment"
                    >
                        Cancel
                    </button>


                    <button
                        type="button"
                        class="book-appointment-button"
                        id="bookExistingAppointment"
                    >
                        Book Appointment
                    </button>

                </div>


            </section>



            <!-- =========================================================
                NEW CLIENT
            ========================================================= -->

            <section
                class="client-form-state hidden"
                id="newClientState"
            >


                <!-- =====================================================
                    APPOINTMENT INFORMATION
                    FIRST
                ====================================================== -->

                <div class="form-section-divider">

                    <h3>
                        Appointment Information
                    </h3>

                </div>


                <!-- SERVICE CATEGORY + SERVICE -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newServiceCategory">

                            Service Category
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newServiceCategory"
                            name="new_service_category"
                        >

                            <option value="">
                                Select Category
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="newService">

                            Service
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newService"
                            name="new_service"
                            disabled
                        >

                            <option value="">
                                Select Service
                            </option>

                        </select>

                    </div>

                </div>


                <!-- PREFERRED DATE + PREFERRED TIME -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newPreferredDate">

                            Preferred Date
                            <span class="required">*</span>

                        </label>


                        <input
                            type="date"
                            id="newPreferredDate"
                            name="new_preferred_date"
                        >

                    </div>


                    <div class="form-group">

                        <label for="newPreferredTime">

                            Preferred Time
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newPreferredTime"
                            name="new_preferred_time"
                            disabled
                        >

                            <option value="">
                                Select a date first
                            </option>

                        </select>

                    </div>

                </div>


                <!-- APPOINTMENT TYPE + SERVICE AVAILABILITY -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newAppointmentType">

                            Appointment Type
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newAppointmentType"
                            name="new_appointment_type"
                        >

                            <option value="">
                                Select Type
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Service Availability
                        </label>


                        <div
                            class="service-availability"
                            id="newServiceAvailability"
                        >

                            <div class="availability-icon">
                                i
                            </div>


                            <div class="availability-content">

                                <strong>
                                    Select a service first
                                </strong>

                                <span>
                                    Service schedule will appear here.
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- REASON FOR VISIT -->

                <div class="form-group full-width">

                    <label for="newReasonForVisit">
                        Reason for Visit
                    </label>


                    <textarea
                        id="newReasonForVisit"
                        name="new_reason_for_visit"
                        rows="4"
                        placeholder="Briefly describe the pet's condition..."
                    ></textarea>

                </div>



                <!-- =====================================================
                    OWNER INFORMATION
                ====================================================== -->

                <div class="form-section-divider new-client-section">

                    <h3>
                        Owner Information
                    </h3>

                </div>


                <!-- LAST NAME + FIRST NAME -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newLastName">

                            Last Name
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="newLastName"
                            name="new_last_name"
                            placeholder="Enter last name"
                        >

                    </div>


                    <div class="form-group">

                        <label for="newFirstName">

                            First Name
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="newFirstName"
                            name="new_first_name"
                            placeholder="Enter first name"
                        >

                    </div>

                </div>


                <!-- MIDDLE NAME + CONTACT NUMBER -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newMiddleName">
                            Middle Name
                        </label>


                        <input
                            type="text"
                            id="newMiddleName"
                            name="new_middle_name"
                            placeholder="Enter middle name"
                        >

                    </div>


                    <div class="form-group">

                        <label for="newContactNumber">

                            Contact Number
                            <span class="required">*</span>

                        </label>


                        <input
                            type="tel"
                            id="newContactNumber"
                            name="new_contact_number"
                            placeholder="e.g. 09123456789"
                        >

                    </div>

                </div>


                <!-- EMAIL + ADDRESS -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newEmail">
                            Email Address
                        </label>


                        <input
                            type="email"
                            id="newEmail"
                            name="new_email"
                            placeholder="Enter email address"
                        >

                    </div>


                    <div class="form-group">

                        <label for="newAddress">

                            Address
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="newAddress"
                            name="new_address"
                            placeholder="Enter full address"
                        >

                    </div>

                </div>



                <!-- =====================================================
                    PET INFORMATION
                ====================================================== -->

                <div class="form-section-divider new-client-section">

                    <h3>
                        Pet Information
                    </h3>

                </div>


                <!-- PET NAME + SPECIES -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newPetName">

                            Pet Name
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="newPetName"
                            name="new_pet_name"
                            placeholder="Enter your pet's name"
                        >

                    </div>


                    <div class="form-group">

                        <label for="newSpecies">

                            Species
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newSpecies"
                            name="new_species"
                        >

                            <option value="">
                                Select Species
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BREED + COLOR -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newBreed">

                            Breed
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newBreed"
                            name="new_breed"
                            disabled
                        >

                            <option value="">
                                Select Species first
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="newColor">

                            Color
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="newColor"
                            name="new_color"
                            placeholder="Enter pet's color"
                        >

                    </div>

                </div>


                <!-- SEX + WEIGHT -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="newSex">

                            Sex
                            <span class="required">*</span>

                        </label>


                        <select
                            id="newSex"
                            name="new_sex"
                        >

                            <option value="">
                                Select Sex
                            </option>

                            <option value="Male">
                                Male
                            </option>

                            <option value="Female">
                                Female
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="newWeight">
                            Weight (kg)
                        </label>


                        <input
                            type="number"
                            id="newWeight"
                            name="new_weight"
                            step="0.01"
                            min="0"
                            placeholder="Enter weight"
                        >

                    </div>

                </div>


                <!-- DATE OF BIRTH -->

                <div class="form-row single-field-row">

                    <div class="form-group">

                        <label for="newDateOfBirth">
                            Date of Birth
                        </label>


                        <input
                            type="date"
                            id="newDateOfBirth"
                            name="new_date_of_birth"
                        >

                    </div>

                </div>



                <!-- =====================================================
                    NEW CLIENT ACTIONS
                ====================================================== -->

                <div class="form-actions">

                    <button
                        type="button"
                        class="cancel-button"
                        id="cancelNewAppointment"
                    >
                        Cancel
                    </button>


                    <button
                        type="button"
                        class="book-appointment-button"
                        id="bookNewAppointment"
                    >
                        Book Appointment
                    </button>

                </div>


            </section>



            <!-- DEVELOPMENT NOTE -->

            <div class="appointment-build-note">

                <strong>
                    Next step:
                </strong>

                We'll add the exact Figma fields for
                the selected client type, including
                pet information, appointment date,
                available time, and booking.

            </div>


        </section>


    </section>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="site-footer">


    <div class="footer-container">


        <!-- ABOUT -->

        <div class="footer-column">


            <h3>
                About 3K Pet Animal Clinic
            </h3>


            <p>

                Caring for every paw with expert
                veterinary services. Your pets health
                and happiness are our top priority.

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

                046-484-4371 /
                0928639761 /
                09065653525

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



    <!-- FOOTER BOTTOM -->

    <div class="footer-bottom">

        <p>

            2026 3K Pet Solution Animal Clinic.
            All rights reserved.

        </p>

    </div>


</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="./js/about.js"></script>

<script src="./js/appointments.js"></script>


</body>

</html>