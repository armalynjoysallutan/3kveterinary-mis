<div class="topbar">

    <div class="topbar-left">

        <button id="menu-toggle" class="menu-toggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <h2><?php echo $pageTitle ?? 'Dashboard'; ?></h2>

    </div>

    <?php if (!empty($showAdminInfo)) : ?>
        <div class="admin-info">
            Welcome,
            <strong><?php echo $_SESSION['admin_username']; ?></strong>
        </div>
    <?php endif; ?>

</div>