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

                <form id="form-signup" action="/register" method="POST">
                    <h1>S'enregistrer</h1>

                    <div class="email-container">
                        <label for="email">E-mail *</label>
                        <input type="text" placeholder="jeandupont@gmail.com" id="email" name="email" required>
                    </div>

                    <div class="password-container">
                        <label for="pass">Mot passe *</label>
                        <input type="password" placeholder="" id="pass" name="pass" required>
                    </div>

                    <div class="confirm-password-container">
                        <label for="confirm-pass">Mot passe *</label>
                        <input type="password" placeholder="" id="confirm-pass" name="confirm-pass" required>
                    </div>

                    <p>Le mot de passe doit contenir au moins 16 caractères alphanumériques</p>


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
                    <button type="submit" name="submit" value="enregistrement">S'enregistrer</button>
                    <p><?= $data["msg"] ?? "" ?></p>
                </form>
            </section>

            <section id="login">
                <form id ="form-login" action="/login" method="POST">
                    <h1>Se connecter</h1>

                    <div class="email-container">
                        <label for="email">E-mail *</label>
                        <input type="text" placeholder="jeandupont@gmail.com" id="email" name="email" required>
                    </div>

                    <div class="password-container">
                        <label for="pass">Mot passe *</label>
                        <input type="password" placeholder="" id="pass" name="pass" required>
                    </div>

                    <button type="submit" name="submit" value="connexion">Se connecter</button>
                    <p><?= $data["msg"] ?? "" ?></p>
                </form>
            </section>

        </main>

        <?php include __DIR__ . "/components/component_footer.php"; ?>
    </body>
</html>