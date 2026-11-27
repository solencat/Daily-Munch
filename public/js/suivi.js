/**
 * Graphique du suivi des calories (D3 v7, copie locale dans js/vendor).
 *
 * Le SVG est décoratif pour les technologies d'assistance : le conteneur
 * porte un résumé en aria-label et le tableau de la page contient les mêmes
 * chiffres, jour par jour.
 */

(() => {
    "use strict";

    const source = document.getElementById("donnees-suivi");
    const conteneur = document.getElementById("graphique-calories");

    if (!source || !conteneur || typeof window.d3 === "undefined") {
        return;
    }

    let donnees;

    try {
        donnees = JSON.parse(source.textContent);
    } catch (erreur) {
        return;
    }

    const jours = Array.isArray(donnees.jours) ? donnees.jours : [];

    if (jours.length === 0) {
        return;
    }

    const d3 = window.d3;
    const formater = new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 });
    const moyenne = Math.round(Number(donnees.moyenne) || 0);

    const maximum = jours.reduce((plus, jour) => Math.max(plus, Number(jour.kcal) || 0), 0);
    const plafond = Math.max(500, Math.ceil((maximum * 1.15) / 100) * 100);

    function dessiner() {
        const largeur = Math.max(280, Math.floor(conteneur.clientWidth));
        const etroit = largeur < 560;
        const hauteur = etroit ? 340 : 400;
        const marges = { haut: 16, droite: 20, bas: 56, gauche: etroit ? 46 : 64 };
        const largeurUtile = largeur - marges.gauche - marges.droite;
        const hauteurUtile = hauteur - marges.haut - marges.bas;

        d3.select(conteneur).selectAll("*").remove();

        const svg = d3.select(conteneur)
            .append("svg")
            .attr("viewBox", `0 0 ${largeur} ${hauteur}`)
            .attr("width", largeur)
            .attr("height", hauteur)
            .attr("aria-hidden", "true")
            .attr("focusable", "false");

        const zone = svg.append("g")
            .attr("transform", `translate(${marges.gauche},${marges.haut})`);

        const x = d3.scaleBand()
            .domain(jours.map((jour, index) => index))
            .range([0, largeurUtile])
            .padding(0.28);

        const y = d3.scaleLinear()
            .domain([0, plafond])
            .range([hauteurUtile, 0]);

        // Grille horizontale + axe des kcal
        const axeY = d3.axisLeft(y)
            .ticks(etroit ? 4 : 5)
            .tickSize(-largeurUtile)
            .tickFormat((valeur) => formater.format(valeur));

        const groupeY = zone.append("g").attr("class", "axe axe-y");
        groupeY.call(axeY);
        groupeY.selectAll(".tick line").attr("stroke", "#e2e8f2");
        groupeY.selectAll(".domain").attr("stroke", "#b9c4d4");
        groupeY.selectAll("text").attr("fill", "#3f5069").attr("font-size", etroit ? 11 : 12);

        // Étiquettes de jours : une sur N quand la période est longue
        const pas = jours.length > 14 ? Math.ceil(jours.length / 10) : 1;

        const axeX = d3.axisBottom(x)
            .tickValues(jours.map((jour, index) => index).filter((index) => index % pas === 0))
            .tickFormat((index) => jours[index].etiquette);

        const groupeX = zone.append("g")
            .attr("class", "axe axe-x")
            .attr("transform", `translate(0,${hauteurUtile})`);
        groupeX.call(axeX);
        groupeX.selectAll(".domain").attr("stroke", "#b9c4d4");
        groupeX.selectAll("text")
            .attr("fill", "#3f5069")
            .attr("font-size", etroit ? 11 : 12)
            .attr("transform", jours.length > 14 ? "rotate(-45)" : null)
            .attr("text-anchor", jours.length > 14 ? "end" : "middle");

        // Barres
        zone.selectAll("rect.barre")
            .data(jours)
            .join("rect")
            .attr("class", (jour) => {
                if (jour.aujourdhui) {
                    return "barre barre--aujourdhui";
                }
                return Number(jour.kcal) > 0 ? "barre" : "barre barre--vide";
            })
            .attr("x", (jour, index) => x(index))
            .attr("width", x.bandwidth())
            .attr("rx", 3)
            .attr("y", (jour) => (Number(jour.kcal) > 0 ? y(jour.kcal) : hauteurUtile - 3))
            .attr("height", (jour) => (Number(jour.kcal) > 0 ? hauteurUtile - y(jour.kcal) : 3))
            .append("title")
            .text((jour) => `${jour.etiquetteLongue} : ${formater.format(jour.kcal)} kcal`
                + (jour.repas > 0 ? ` (${jour.repas} repas)` : " (aucun repas)"));

        // Valeur au-dessus des barres non nulles (lisible sans survol)
        zone.selectAll("text.valeur-barre")
            .data(jours.filter((jour) => Number(jour.kcal) > 0))
            .join("text")
            .attr("class", "valeur-barre")
            .attr("x", (jour) => x(jours.indexOf(jour)) + x.bandwidth() / 2)
            .attr("y", (jour) => y(jour.kcal) - 6)
            .attr("text-anchor", "middle")
            .attr("fill", "#0b203b")
            .attr("font-family", "var(--font)")
            .attr("font-size", etroit ? 10 : 11)
            .attr("font-weight", "700")
            .text((jour) => formater.format(jour.kcal));

        // Moyenne des jours renseignés
        if (moyenne > 0) {
            zone.append("line")
                .attr("class", "moyenne")
                .attr("x1", 0)
                .attr("x2", largeurUtile)
                .attr("y1", y(moyenne))
                .attr("y2", y(moyenne));

            zone.append("text")
                .attr("class", "moyenne-label")
                .attr("x", 4)
                .attr("y", y(moyenne) - 8)
                .attr("text-anchor", "start")
                .text(`moyenne ${formater.format(moyenne)} kcal`);
        }

        if (maximum === 0) {
            zone.append("text")
                .attr("class", "message-vide")
                .attr("x", largeurUtile / 2)
                .attr("y", hauteurUtile / 2)
                .attr("text-anchor", "middle")
                .text("Aucun repas enregistré sur cette période.");
        }

        // Résumé pour les lecteurs d'écran, porté par le conteneur.
        const premier = jours[0];
        const dernier = jours[jours.length - 1];
        conteneur.setAttribute(
            "aria-label",
            `Graphique en barres des calories par jour, du ${premier.etiquette} au ${dernier.etiquette} : `
            + `${maximum === 0 ? "aucune calorie enregistrée" : "maximum " + formater.format(maximum) + " kcal"}, `
            + `moyenne ${formater.format(moyenne)} kcal par jour renseigné. `
            + "Les mêmes valeurs sont détaillées dans le tableau ci-dessous."
        );
    }

    dessiner();

    // Redessine quand la largeur du conteneur change (fenêtre, zoom, mobile).
    // ResizeObserver ne suffit pas partout (émulation, vieux navigateurs) :
    // l'événement resize sert de filet, les deux passant par le même garde-fou.
    // Le redessin est direct : requestAnimationFrame ne se déclenche pas dans
    // un onglet en arrière-plan, et le graphique resterait à son ancienne taille.
    let largeurPrecedente = conteneur.clientWidth;

    function redessinerSiLargeurChange() {
        const largeur = conteneur.clientWidth;

        if (Math.abs(largeur - largeurPrecedente) < 2) {
            return;
        }

        largeurPrecedente = largeur;
        dessiner();
    }

    if (typeof ResizeObserver !== "undefined") {
        const observateur = new ResizeObserver(redessinerSiLargeurChange);
        observateur.observe(conteneur);
    }

    window.addEventListener("resize", redessinerSiLargeurChange);
})();
