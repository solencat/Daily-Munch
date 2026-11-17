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
        <link rel="stylesheet" href="css/imc.css">
    </head>

    <body>
        <?php include "components/header.php"; ?>

        <main>
            <form action="#" method="POST" id="form-bmi">
                <h1>Calculateur d'IMC</h1>
                <div class="bmi-height-container">
                    <label for="">Taille en cm:</label>
                    <input type="number" placeholder="174" id="bmi-height" required></input>
                </div>

                <div class="bmi-weight-container">
                    <label for="">Poids en kg:</label>
                    <input type="number" placeholder="70" id="bmi-weight" required></input>
                </div>
                
                <button type="submit">Calculer</button>

                <p id="bmi-result"></p>

                <h2>Guide de lecture du résultat</h2>

                <div class="bmi-guideline">
                    <p>Sous-poids: < 18.5 </p>
                    <p>Poids normal: 18.5-24.9</p>
                    <p>Surpoids: 25-29.9</p>
                    <p>Obèse: 30+</p>
                </div>

                

            </form>
        </main>

        <?php include "components/footer.php"; ?>
    </body>
    <script src="../js/imc.js"></script>
</html>