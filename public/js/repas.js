/**
 * Suivi des repas : recherche de produits Open Food Facts (API locale) et
 * construction du repas côté navigateur.
 *
 * Le serveur ne reçoit que des codes produit et des quantités : les calories
 * affichées ici sont un confort, le calcul qui fait foi est refait en PHP.
 */

(() => {
    "use strict";

    const champRecherche = document.getElementById("recherche-produit");
    const listeResultats = document.getElementById("resultats-recherche");
    const etatRecherche = document.getElementById("etat-recherche");
    const listeSelection = document.getElementById("produits-selectionnes");
    const etatSelection = document.getElementById("etat-selection");
    const champItems = document.getElementById("items-json");
    const zoneTotal = document.getElementById("total-repas");
    const formulaire = document.getElementById("form-repas");

    if (!champRecherche || !formulaire) {
        return;
    }

    const PORTION_PAR_DEFAUT = 100;
    const QUANTITE_MIN = 0.1;
    const QUANTITE_MAX = 5000;

    /** @type {Map<string, {code: string, name: string, kcal100: number, quantite: number}>} */
    const selection = new Map();

    let minuteur = null;
    let controleur = null;

    /* --- utilitaires --- */

    const formaterNombre = (valeur) =>
        new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 }).format(valeur);

    function bornerQuantite(valeur) {
        const nombre = Number(String(valeur).replace(",", "."));
        if (!Number.isFinite(nombre) || nombre <= 0) {
            return null;
        }
        return Math.min(QUANTITE_MAX, Math.max(QUANTITE_MIN, nombre));
    }

    /* --- rendu de la sélection --- */

    function mettreAJourTotal() {
        let total = 0;
        selection.forEach((ligne) => {
            total += (ligne.quantite * ligne.kcal100) / 100;
        });

        zoneTotal.innerHTML = "";
        zoneTotal.append("Total du repas : ");
        const fort = document.createElement("strong");
        fort.textContent = formaterNombre(Math.round(total)) + " kcal";
        zoneTotal.append(fort);

        etatSelection.textContent = selection.size === 0
            ? "Aucun produit ajouté pour le moment."
            : selection.size + (selection.size > 1 ? " produits ajoutés." : " produit ajouté.");
    }

    function serialiserSelection() {
        const lignes = [];
        selection.forEach((ligne) => {
            lignes.push({ code: ligne.code, quantity_g: ligne.quantite });
        });
        champItems.value = JSON.stringify(lignes);
    }

    function dessinerSelection() {
        listeSelection.innerHTML = "";

        selection.forEach((ligne) => {
            const item = document.createElement("li");
            item.className = "ligne-selection";

            const infos = document.createElement("div");
            infos.className = "ligne-infos";

            const nom = document.createElement("span");
            nom.className = "produit-nom";
            nom.textContent = ligne.name;

            const detail = document.createElement("span");
            detail.className = "produit-detail";
            detail.textContent = formaterNombre(ligne.kcal100) + " kcal / 100 g";

            infos.append(nom, detail);

            const blocQuantite = document.createElement("div");
            const etiquette = document.createElement("label");
            etiquette.className = "selection-quantite-label";
            const idQuantite = "quantite-" + ligne.code;
            etiquette.htmlFor = idQuantite;
            etiquette.textContent = "Quantité (g)";

            const champQuantite = document.createElement("input");
            champQuantite.type = "number";
            champQuantite.id = idQuantite;
            champQuantite.className = "selection-quantite";
            champQuantite.min = String(QUANTITE_MIN);
            champQuantite.max = String(QUANTITE_MAX);
            // step="any" : un min à 0,1 avec un pas de 1 rendrait 100 invalide
            // (les valeurs autorisées seraient 0,1 / 1,1 / 2,1…), et le
            // navigateur bloquerait l'envoi du formulaire.
            champQuantite.step = "any";
            champQuantite.value = String(ligne.quantite);
            champQuantite.setAttribute("aria-label", "Quantité en grammes pour " + ligne.name);

            champQuantite.addEventListener("input", () => {
                const quantite = bornerQuantite(champQuantite.value);
                if (quantite === null) {
                    return;
                }
                ligne.quantite = quantite;
                kcalLigne.textContent =
                    formaterNombre(Math.round((quantite * ligne.kcal100) / 100)) + " kcal";
                mettreAJourTotal();
                serialiserSelection();
            });

            champQuantite.addEventListener("blur", () => {
                const quantite = bornerQuantite(champQuantite.value);
                ligne.quantite = quantite === null ? PORTION_PAR_DEFAUT : quantite;
                champQuantite.value = String(ligne.quantite);
                kcalLigne.textContent =
                    formaterNombre(Math.round((ligne.quantite * ligne.kcal100) / 100)) + " kcal";
                mettreAJourTotal();
                serialiserSelection();
            });

            blocQuantite.append(etiquette, champQuantite);

            const kcalLigne = document.createElement("span");
            kcalLigne.className = "ligne-kcal";
            kcalLigne.textContent =
                formaterNombre(Math.round((ligne.quantite * ligne.kcal100) / 100)) + " kcal";

            const boutonRetirer = document.createElement("button");
            boutonRetirer.type = "button";
            boutonRetirer.className = "bouton-retirer";
            boutonRetirer.textContent = "Retirer";
            boutonRetirer.setAttribute("aria-label", "Retirer " + ligne.name + " du repas");
            boutonRetirer.addEventListener("click", () => {
                selection.delete(ligne.code);
                dessinerSelection();
                mettreAJourTotal();
                serialiserSelection();
                champRecherche.focus();
            });

            item.append(infos, blocQuantite, kcalLigne, boutonRetirer);
            listeSelection.append(item);
        });

        mettreAJourTotal();
        serialiserSelection();
    }

    function ajouterProduit(produit, quantite) {
        const existant = selection.get(produit.code);

        if (existant) {
            existant.quantite = Math.min(QUANTITE_MAX, existant.quantite + quantite);
        } else {
            selection.set(produit.code, {
                code: produit.code,
                name: produit.name,
                kcal100: produit.kcal_100g,
                quantite: quantite,
            });
        }

        dessinerSelection();
    }

    /* --- rendu des résultats --- */

    function dessinerResultats(produits, requete) {
        listeResultats.innerHTML = "";

        if (produits.length === 0) {
            etatRecherche.textContent = "Aucun produit trouvé pour « " + requete + " ».";
            return;
        }

        etatRecherche.textContent =
            produits.length + (produits.length > 1 ? " produits trouvés." : " produit trouvé.");

        produits.forEach((produit) => {
            const item = document.createElement("li");
            item.className = "resultat";

            // La vignette existe toujours, même sans image : les lignes de
            // résultat gardent ainsi la même grille.
            const vignette = document.createElement("div");
            vignette.className = "resultat-vignette";

            if (produit.image) {
                const image = document.createElement("img");
                image.src = produit.image;
                image.alt = "";
                image.loading = "lazy";
                image.addEventListener("error", () => image.remove());
                vignette.append(image);
            }

            item.append(vignette);

            const infos = document.createElement("div");
            infos.className = "resultat-infos";

            const nom = document.createElement("span");
            nom.className = "resultat-nom";
            nom.textContent = produit.name;

            const detail = document.createElement("span");
            detail.className = "resultat-detail";
            const morceaux = [
                produit.brands,
                produit.kcal_100g !== null ? formaterNombre(produit.kcal_100g) + " kcal / 100 g" : null,
                produit.portion_label,
            ].filter(Boolean);
            detail.textContent = morceaux.join(" · ");

            infos.append(nom, detail);

            const portion = produit.serving_g && produit.serving_g > 0
                ? produit.serving_g
                : PORTION_PAR_DEFAUT;

            const etiquette = document.createElement("label");
            etiquette.className = "resultat-quantite-label";
            const idQuantite = "portion-" + produit.code;
            etiquette.htmlFor = idQuantite;
            etiquette.textContent = "Quantité (g)";

            const champQuantite = document.createElement("input");
            champQuantite.type = "number";
            champQuantite.id = idQuantite;
            champQuantite.className = "resultat-quantite";
            champQuantite.min = String(QUANTITE_MIN);
            champQuantite.max = String(QUANTITE_MAX);
            // step="any" : un min à 0,1 avec un pas de 1 rendrait 100 invalide
            // (les valeurs autorisées seraient 0,1 / 1,1 / 2,1…), et le
            // navigateur bloquerait l'envoi du formulaire.
            champQuantite.step = "any";
            champQuantite.value = String(portion);
            champQuantite.setAttribute("aria-label", "Quantité en grammes de " + produit.name);

            const conteneurQuantite = document.createElement("div");
            conteneurQuantite.className = "resultat-quantite-bloc";
            conteneurQuantite.append(etiquette, champQuantite);

            const boutonAjouter = document.createElement("button");
            boutonAjouter.type = "button";
            boutonAjouter.className = "bouton-ajouter";
            boutonAjouter.textContent = "Ajouter";
            boutonAjouter.setAttribute("aria-label", "Ajouter " + produit.name + " au repas");
            boutonAjouter.addEventListener("click", () => {
                const quantite = bornerQuantite(champQuantite.value) || portion;
                ajouterProduit(produit, quantite);
                etatRecherche.textContent = produit.name + " ajouté au repas.";
                champRecherche.focus();
            });

            item.append(infos, conteneurQuantite, boutonAjouter);
            listeResultats.append(item);
        });
    }

    /* --- recherche --- */

    async function rechercher(requete) {
        if (controleur) {
            controleur.abort();
        }
        controleur = new AbortController();

        try {
            const reponse = await fetch(
                "/api/products?q=" + encodeURIComponent(requete),
                { signal: controleur.signal, headers: { Accept: "application/json" } }
            );

            if (!reponse.ok) {
                throw new Error("HTTP " + reponse.status);
            }

            const donnees = await reponse.json();
            const produits = (donnees.products || []).map((produit) => ({
                ...produit,
                portion_label: produit.serving_size || produit.quantity || null,
            }));

            dessinerResultats(produits, requete);
        } catch (erreur) {
            if (erreur.name === "AbortError") {
                return;
            }
            etatRecherche.textContent =
                "La recherche a échoué. Vérifiez que le serveur répond, puis réessayez.";
            listeResultats.innerHTML = "";
        }
    }

    champRecherche.addEventListener("input", () => {
        const requete = champRecherche.value.trim();

        window.clearTimeout(minuteur);

        if (requete.length < 2) {
            listeResultats.innerHTML = "";
            etatRecherche.textContent = "";
            return;
        }

        etatRecherche.textContent = "Recherche en cours…";

        minuteur = window.setTimeout(() => rechercher(requete), 250);
    });

    // Entrée dans la recherche ne doit pas envoyer le repas : on relance la
    // recherche à la place.
    champRecherche.addEventListener("keydown", (evenement) => {
        if (evenement.key !== "Enter") {
            return;
        }

        evenement.preventDefault();
        window.clearTimeout(minuteur);

        const requete = champRecherche.value.trim();
        if (requete.length >= 2) {
            rechercher(requete);
        }
    });

    /* --- envoi du formulaire --- */

    formulaire.addEventListener("submit", (evenement) => {
        if (selection.size === 0) {
            evenement.preventDefault();
            etatSelection.textContent =
                "Ajoutez au moins un produit avant d'enregistrer le repas.";
            etatSelection.setAttribute("role", "alert");
            champRecherche.focus();
            return;
        }

        serialiserSelection();
    });

    dessinerSelection();
})();
