<?php
    $currentPage = $_SERVER['REQUEST_URI'];
?>

<header>
    <a href="/">
        <img src="../images/Logo.png" alt="Logo The Daily Munch" id="header-logo">
    </a>

    <nav class="header-nav-main">
        <ul>
            <li>
                <a href="/"
                <?= $currentPage == "/" ? "class='tab-active'" : ""?>
                >ACCUEIL</a>
            </li>
            <li>
                <a href="./repas.html">REPAS</a>
            </li>
            <li>
                <a href="./outils.html">OUTILS</a>
            </li>
            <li>
                <a href="./suivi.html">SUIVI</a>
            </li>
        </ul>
    </nav>

    <div class="account-container">
        <a href="./profil.html" class="profile-link">
            <img src="../images/account_circle.png" alt="profile icon">
            PROFIL
        </a>
        <a href="./connexion.html" class="login-link">
            <img src="../images/login.png" alt="login icon">
            CONNEXION
        </a>
    </div>
</header>