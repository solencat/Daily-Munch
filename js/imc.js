// Get the form area
const form = document.querySelector("#form-bmi");
// console.log(form);

// Get the result area
const result = document.querySelector("#bmi-result");
// console.log(result);

// Add an event listener on submit of a form
form.addEventListener('submit',(event) => {
    // Avoid expected behaviour of form waiting for a server connection
    event.preventDefault();

    // Display result hidden by default
    result.style.display = "block";

    // Get the weight from the form
    const weight = document.querySelector("#bmi-weight").value;

    // Get the height from the form
    const height = document.querySelector("#bmi-height").value;

    // Assert that a valid number has been entered for height
    if(!height || isNaN(height) || height <= 0){
        result.innerText = "Taille non règlementaire, veuillez entrer un nombre valide supérieur à 0";
    }
    // Assert that a valid number has been entered for weight
    else if (!weight || isNaN(weight) || weight <= 0){
            result.innerText = "Poids non règlementaire, veuillez entrer un nombre valide supérieur à 0";
    }
    // If all is in order calculate bmi and add result to the result area
    else {
        const bmi = (weight / ((height/100) **2));
        result.innerText = `Votre IMC est:
        ${bmi.toFixed(1)}`
    }
})