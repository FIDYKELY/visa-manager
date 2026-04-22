// assets/js/visa-form.js

document.addEventListener('DOMContentLoaded', function () {
    console.log("go");
    const form = document.querySelector('form');
    if (!form) return;

    const arrival = form.querySelector('input[name="arrival_date"]');
    const departure = form.querySelector('input[name="departure_date"]');

    if (arrival && departure) {
        form.addEventListener('submit', function (e) {
            const start = new Date(arrival.value);
            const end = new Date(departure.value);
            if (start && end && end <= start) {
                e.preventDefault();
                alert("La date de départ doit être postérieure à la date d’arrivée.");
            }
        });
    }

    // Niveau 2
    const selectVille = document.querySelector('select[name="depot_ville"]');
    const depotP = document.getElementById('depot');

    selectVille.addEventListener('change', function() {
        console.log("change");
        const parts = this.value.split(' - ');
        depotP.textContent = parts[0] || '';
    });
});