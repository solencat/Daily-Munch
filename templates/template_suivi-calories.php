<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? "Suivi des calories", ENT_QUOTES, "UTF-8") ?></title>

        <!-- Font Montserrat -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <!-- Styles -->
        <link rel="stylesheet" href="css/variables.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/footer.css">
        <link rel="stylesheet" href="css/suivi.css">
    </head>

    <body>
        <?php
            include __DIR__ . "/components/component_header.php";

            $serie = $data["serie"];
            $resume = $data["resume"];
            $periode = (int) $data["periode"];

            $esc = static fn(?string $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
            $nombre = static fn(float $valeur): string => number_format($valeur, 0, ",", " ");

            $libelles = [7 => "7 jours", 14 => "14 jours", 30 => "30 jours", 90 => "90 jours"];

            $moyenneReference = $resume["moyenne_jour_suivi"];

            $donneesGraphique = [
                "periode" => $periode,
                "moyenne" => $moyenneReference,
                "jours" => array_map(
                    static fn(array $jour): array => [
                        "date" => $jour["date"],
                        "etiquette" => $jour["etiquette"],
                        "etiquetteLongue" => $jour["etiquette_longue"],
                        "kcal" => $jour["kcal"],
                        "repas" => $jour["repas"],
                        "aujourdhui" => $jour["aujourdhui"],
                    ],
                    $serie
                ),
            ];
        ?>

        <main class="suivi-page">
            <h1>Suivi des calories</h1>
            <p class="intro">
                Calculé à partir de vos repas enregistrés dans <a href="/repas">Mes repas</a>.
            </p>

            <nav class="periodes" aria-label="Période affichée">
                <?php foreach ($data["periodes"] as $option) : ?>
                    <a class="periode<?= $option === $periode ? " periode--active" : "" ?>"
                       href="/suivi?periode=<?= (int) $option ?>"
                       <?= $option === $periode ? 'aria-current="page"' : "" ?>><?= $esc($libelles[$option]) ?></a>
                <?php endforeach; ?>
            </nav>

            <section class="chiffres" aria-label="Chiffres clés de la période">
                <article class="chiffre">
                    <h2>Aujourd'hui</h2>
                    <p class="valeur"><?= $nombre($resume["aujourdhui"]) ?> <span class="unite">kcal</span></p>
                </article>

                <article class="chiffre">
                    <h2>Moyenne par jour renseigné</h2>
                    <p class="valeur"><?= $nombre($resume["moyenne_jour_suivi"]) ?> <span class="unite">kcal</span></p>
                </article>

                <article class="chiffre">
                    <h2>Jours renseignés</h2>
                    <p class="valeur"><?= (int) $resume["jours_suivis"] ?>
                        <span class="unite">sur <?= (int) $resume["jours"] ?></span></p>
                </article>

                <article class="chiffre">
                    <h2>Jour le plus élevé</h2>
                    <?php if ($resume["maximum_date"] !== null) : ?>
                        <p class="valeur"><?= $nombre($resume["maximum"]) ?> <span class="unite">kcal</span></p>
                        <p class="detail"><?= $esc($resume["maximum_date"]) ?></p>
                    <?php else : ?>
                        <p class="valeur">—</p>
                    <?php endif; ?>
                </article>
            </section>

            <?php if ($resume["jours_suivis"] === 0) : ?>
                <p class="vide" role="status">
                    Aucun repas enregistré sur cette période.
                    <a href="/repas">Enregistrez un repas</a> pour voir la courbe se remplir.
                </p>
            <?php endif; ?>

            <section class="carte" aria-labelledby="titre-graphique">
                <h2 id="titre-graphique">Calories consommées par jour</h2>
                <p class="aide">
                    Une barre par jour, en kcal. La ligne pointillée indique la moyenne des jours renseignés
                    (<?= $nombre($moyenneReference) ?> kcal).
                </p>

                <div id="graphique-calories" class="graphique"
                     role="img"
                     aria-label="Graphique en barres des calories consommées par jour. Les mêmes valeurs sont détaillées dans le tableau ci-dessous."></div>

                <noscript>
                    <p class="aide">Le graphique a besoin de JavaScript. Le tableau ci-dessous contient les mêmes données.</p>
                </noscript>
            </section>

            <section class="carte" aria-labelledby="titre-tableau">
                <h2 id="titre-tableau">Détail jour par jour</h2>

                <table class="tableau-jours">
                    <caption>
                        Calories et nombre de repas par jour, sur <?= $esc($libelles[$periode]) ?>.
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Jour</th>
                            <th scope="col">Repas</th>
                            <th scope="col">Calories</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($serie as $jour) : ?>
                            <tr<?= $jour["aujourdhui"] ? ' class="ligne-aujourdhui"' : "" ?>>
                                <th scope="row">
                                    <?= $esc(ucfirst($jour["etiquette_longue"])) ?>
                                    <?php if ($jour["aujourdhui"]) : ?>
                                        <span class="marqueur">aujourd'hui</span>
                                    <?php endif; ?>
                                </th>
                                <td><?= (int) $jour["repas"] ?></td>
                                <td class="cellule-kcal"><?= $nombre($jour["kcal"]) ?> kcal</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">Total de la période</th>
                            <td><?= array_sum(array_column($serie, "repas")) ?></td>
                            <td class="cellule-kcal"><?= $nombre($resume["total"]) ?> kcal</td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </main>

        <?php include __DIR__ . "/components/component_footer.php"; ?>

        <script type="application/json" id="donnees-suivi"><?=
            json_encode(
                $donneesGraphique,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
            )
        ?></script>
        <script src="js/vendor/d3.min.js"></script>
        <script src="js/suivi.js"></script>
    </body>
</html>
