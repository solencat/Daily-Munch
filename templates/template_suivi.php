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
        <link rel="stylesheet" href="css/repasdujour.css">
    </head>

    <body>
        <?php include __DIR__ . "/components/component_header.php"; ?>

        <main>

            <section class="meal-overview">
                <h1>Mon repas</h1>
                <p class="calories-total">Calories total:</p>
            </section>

            <section class="breakfast">
                <h1>Déjeuner</h1>
                <div class="card-food">
                    <img src="#" alt="Baguette">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="Beurre">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="Confiture">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="Croissant">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <p class="calories-total">Calories total:</p>
            </section>

            <section class="dinner">
                <h1>Diner</h1>
                <div class="card-food">
                    <img src="#" alt="steak haché">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="frites">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="haricots">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <p class="calories-total">Calories total:</p>
            </section>

            <section class="supper">
                <h1>Souper</h1>
                <div class="card-food">
                    <img src="#" alt="Salade césar">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="Pepsi">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <div class="card-food">
                    <img src="#" alt="Yaourt Grec">
                    <p class="card-food-title"></p>
                    <p class="card-food-portion"></p>
                    <p class="card-food-calories"></p>
                </div>
                <p class="calories-total">Calories total:</p>
            </section>

        </main>

        <?php include __DIR__ . "/components/component_footer.php"; ?>
    </body>
</html>