<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? "Mes repas", ENT_QUOTES, "UTF-8") ?></title>

        <!-- Font Montserrat -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <!-- Styles -->
        <link rel="stylesheet" href="css/variables.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/footer.css">
        <link rel="stylesheet" href="css/repas.css">
    </head>

    <body>
        <?php
            include __DIR__ . "/components/component_header.php";

            $date = $data["date"];
            $flash = $data["flash"] ?? null;
            $jour = new \DateTimeImmutable($date);
            $veille = $jour->modify("-1 day");
            $lendemain = $jour->modify("+1 day");
            $aujourdhui = new \DateTimeImmutable($data["today"]);

            $jours = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
            $mois = ["", "janvier", "février", "mars", "avril", "mai", "juin", "juillet",
                "août", "septembre", "octobre", "novembre", "décembre"];

            $etiquette = static function (\DateTimeImmutable $d) use ($jours, $mois): string {
                return $jours[(int) $d->format("w")] . " " . (int) $d->format("j")
                    . " " . $mois[(int) $d->format("n")];
            };

            $esc = static fn(?string $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
        ?>

        <main class="repas-page">
            <h1>Mes repas</h1>

            <nav class="jour-navigation" aria-label="Changer de journée">
                <a class="lien-jour" href="/repas?date=<?= $veille->format("Y-m-d") ?>" rel="prev">
                    <span aria-hidden="true">←</span> <?= $esc(ucfirst($etiquette($veille))) ?>
                </a>

                <p class="jour-courant">
                    <span class="sr-only">Journée affichée : </span><?= $esc(ucfirst($etiquette($jour))) ?>
                </p>

                <?php if ($lendemain <= $aujourdhui) : ?>
                    <a class="lien-jour" href="/repas?date=<?= $lendemain->format("Y-m-d") ?>" rel="next">
                        <?= $esc(ucfirst($etiquette($lendemain))) ?> <span aria-hidden="true">→</span>
                    </a>
                <?php else : ?>
                    <span class="lien-jour lien-jour--inactif">Demain (à venir)</span>
                <?php endif; ?>
            </nav>

            <?php if (is_array($flash) && !empty($flash["messages"])) : ?>
                <ul class="flash flash--<?= $flash["type"] === "error" ? "erreur" : "succes" ?>"
                    role="<?= $flash["type"] === "error" ? "alert" : "status" ?>">
                    <?php foreach ($flash["messages"] as $message) : ?>
                        <li><?= $esc($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <section class="carte" aria-labelledby="titre-saisie">
                <h2 id="titre-saisie">Ajouter un repas</h2>

                <form id="form-repas" action="/repas" method="POST">
                    <div class="champs-ligne">
                        <div class="champ">
                            <label for="date_meal">Date du repas</label>
                            <input type="date" id="date_meal" name="date_meal"
                                   value="<?= $esc($date) ?>" max="<?= $esc($data["today"]) ?>" required>
                        </div>

                        <div class="champ">
                            <label for="meal_name">Repas</label>
                            <input type="text" id="meal_name" name="meal_name" list="liste-repas"
                                   maxlength="50" placeholder="Déjeuner" required
                                   aria-describedby="aide-repas">
                            <datalist id="liste-repas">
                                <?php foreach ($data["suggestions"] as $suggestion) : ?>
                                    <option value="<?= $esc($suggestion) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <p class="aide" id="aide-repas">Choisissez une proposition ou écrivez le vôtre.</p>
                        </div>
                    </div>

                    <fieldset class="produits">
                        <legend>Produits du repas</legend>

                        <div class="champ">
                            <label for="recherche-produit">Rechercher un produit</label>
                            <input type="search" id="recherche-produit" autocomplete="off"
                                   placeholder="pâte à tartiner, yaourt nature, pain complet…"
                                   aria-describedby="aide-recherche etat-recherche">
                            <p class="aide" id="aide-recherche">
                                À partir de 2 caractères. Base Open Food Facts installée localement.
                            </p>
                            <p class="aide" id="etat-recherche" role="status" aria-live="polite"></p>
                        </div>

                        <ul class="resultats" id="resultats-recherche" aria-label="Résultats de la recherche"></ul>

                        <h3 class="titre-selection">Produits ajoutés</h3>

                        <ul class="selection" id="produits-selectionnes" aria-label="Produits ajoutés au repas"></ul>

                        <p class="aide" id="etat-selection">Aucun produit ajouté pour le moment.</p>

                        <input type="hidden" name="items" id="items-json" value="">

                        <p class="total-repas" id="total-repas" aria-live="polite">
                            Total du repas : <strong>0 kcal</strong>
                        </p>
                    </fieldset>

                    <button type="submit" class="bouton-principal">Enregistrer le repas</button>
                </form>
            </section>

            <section class="carte" aria-labelledby="titre-journee">
                <h2 id="titre-journee">Repas enregistrés — <?= $esc($etiquette($jour)) ?></h2>

                <?php if ($data["meals"] === []) : ?>
                    <p class="vide">Aucun repas enregistré pour cette journée.</p>
                <?php else : ?>
                    <div class="journee">
                        <?php foreach ($data["meals"] as $repas) : ?>
                            <article class="repas-carte">
                                <header class="repas-entete">
                                    <h3><?= $esc($repas->getName()) ?></h3>
                                    <p class="repas-total"><?= number_format($repas->getTotalKcal(), 0, ",", " ") ?> kcal</p>
                                </header>

                                <ul class="repas-produits">
                                    <?php foreach ($repas->getItems() as $produit) : ?>
                                        <li>
                                            <span class="produit-nom"><?= $esc($produit->getProductName()) ?></span>
                                            <span class="produit-quantite"><?= number_format($produit->getQuantityG(), 0, ",", " ") ?> g</span>
                                            <span class="produit-kcal"><?= number_format($produit->getKcal(), 0, ",", " ") ?> kcal</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <form class="suppression" action="/repas/supprimer" method="POST">
                                    <input type="hidden" name="id_log_meal" value="<?= (int) $repas->getId() ?>">
                                    <input type="hidden" name="date" value="<?= $esc($date) ?>">
                                    <button type="submit" class="bouton-discret">
                                        Supprimer ce repas<span class="sr-only"> : <?= $esc($repas->getName()) ?></span>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <p class="total-journee">
                        Total de la journée : <strong><?= number_format($data["totalKcal"], 0, ",", " ") ?> kcal</strong>
                    </p>
                <?php endif; ?>
            </section>
        </main>

        <?php include __DIR__ . "/components/component_footer.php"; ?>
    </body>
    <script src="js/repas.js"></script>
</html>
