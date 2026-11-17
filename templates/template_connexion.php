<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?? "" ?></title>

        <!-- Font Montserrat -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        
        <!-- Styles -->
        <link rel="stylesheet" href="css/variables.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/footer.css">
        <link rel="stylesheet" href="css/connexion.css">
    </head>

    <body>
        <?php include __DIR__ . "/components/component_header.php"; ?>

        <main>

            <section id="signup">

                <form id="form-signup" action="#" method="POST">
                    <h1>S'enregistrer</h1>

                    <div class="email-container">
                        <label for="email">E-mail *</label>
                        <input type="text" placeholder="jeandupont@gmail.com" id="email" name="email" required>
                    </div>

                    <div class="password-container">
                        <label for="password-signup">Mot passe *</label>
                        <input type="password" placeholder="" id="password-signup" name="password-signup" required>
                        <p>Le mot de passe doit contenir au moins 16 caractères alphanumériques</p>
                    </div>

                    <div class="confirm-password-container">
                        <label for="confirm-password-signup">Mot passe *</label>
                        <input type="password" placeholder="" id="confirm-password-signup" name="confirm-password-signup" required>
                    </div>

                    <h2>Profil</h2>
                    <div class="firstname-container">
                        <label for="firstname">Prénom *</label>
                        <input type="text" placeholder="Jean" id="firstname" name="firstname" required>
                    </div>
                    <div class="lastname-container">
                        <label for="lastname">Nom *</label>
                        <input type="text" placeholder="Dupont" id="lastname" name="lastname" required>
                    </div>
                    
                    <div class="height-container">
                        <label for="height">Taille (en cm) *</label>
                        <input type="number" placeholder="180" id="height" name="height">
                    </div>
                    <div class="weight-container">
                        <label for="weight">Poids (en kg) *</label>
                        <input type="text" placeholder="80" id="weight" name="weight">
                    </div>
                    <h3>* champ obligatoire</h2>
                    <button type="submit" name="submit">S'enregistrer</button>
                </form>
            </section>

            <section id="login">
                <form id ="form-login" action="#" method="POST">
                    <h1>Se connecter</h1>
                    <div class="username-container">
                        <label for="username-login">Nom d'utilisateur *</label>
                        <input type="text" placeholder="" id="username-login" name="username-login" required>
                    </div>
                    <div class="password-container">
                        <label for="password-login">Mot passe *</label>
                        <input type="password" placeholder="" id="password-login" name="password-login" required>
                    </div>
                    <button type="submit" name="submit">Se connecter</button>
                </form>
            </section>

        </main>

        <?php include __DIR__ . "/components/component_footer.php"; ?>
    </body>
</html>