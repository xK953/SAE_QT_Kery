// On s'assure que tout le HTML est chargé avant d'agir sur la page
document.addEventListener("DOMContentLoaded", () => {
    // ==========================================
    // 0. MODE NUIT GLOBAL
    // ==========================================
    const boutonModeNuit = document.getElementById('mode-nuit-toggle');
    const modeNuitActif = localStorage.getItem('modeNuit') === 'actif';

    document.body.classList.toggle('mode-nuit', modeNuitActif);

    if (boutonModeNuit) {
        boutonModeNuit.textContent = modeNuitActif ? 'Mode clair ☀️' : 'Mode nuit 🌙';

        boutonModeNuit.addEventListener('click', () => {
            const nouveauModeNuit = !document.body.classList.contains('mode-nuit');

            document.body.classList.toggle('mode-nuit', nouveauModeNuit);
            localStorage.setItem('modeNuit', nouveauModeNuit ? 'actif' : 'inactif');
            boutonModeNuit.textContent = nouveauModeNuit ? 'Mode clair ☀️' : 'Mode nuit 🌙';
        });
    }

    // ==========================================
    // 1. AUTO-DISPARITION DES MESSAGES D'ALERTE
    // ==========================================
    const alertes = document.querySelectorAll('.message-succes, .message-erreur');
    
    alertes.forEach(alerte => {
        // Après 4 secondes (4000 ms), on lance l'animation de disparition
        setTimeout(() => {
            alerte.style.transition = "opacity 0.5s ease-out, transform 0.5s ease-out";
            alerte.style.opacity = "0";
            alerte.style.transform = "translateY(-10px)";
            
            // Une fois l'animation finie (500 ms), on supprime complètement l'élément du DOM
            setTimeout(() => alerte.remove(), 500);
        }, 4000);
    });

    // ==========================================
    // 2. TOGGLE POUR AFFICHER/MASQUER LE MOT DE PASSE
    // ==========================================
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        // On crée une petite "boîte" pour contenir l'input et l'icône
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        
        // On insère la boîte juste avant l'input, puis on déplace l'input dedans
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        // On crée le bouton "Œil"
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'btn-toggle-password';
        toggleBtn.textContent = '👁️';
        wrapper.appendChild(toggleBtn);

        // L'action au clic : on alterne entre le type 'password' et 'text'
        toggleBtn.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggleBtn.textContent = isPassword ? '🙈' : '👁️';
        });
    });

    // ==========================================
    // 3. CALCUL DYNAMIQUE DE LA DATE DE RETOUR
    // ==========================================
    const dateDebut = document.getElementById('date_debut');
    const dureeJours = document.getElementById('duree_jours');

    // On vérifie qu'on est bien sur une page contenant ces champs (création ou modification)
    if (dateDebut && dureeJours) {
        
        // On crée le paragraphe qui affichera le résultat
        const affichageRetour = document.createElement('p');
        affichageRetour.className = 'date-retour-dynamique';
        
        // On le place juste après le champ de durée
        dureeJours.parentNode.insertBefore(affichageRetour, dureeJours.nextSibling);

        const calculerRetour = () => {
            if (dateDebut.value && dureeJours.value && parseInt(dureeJours.value) > 0) {
                // Création d'un objet Date en ajoutant les jours
                const date = new Date(dateDebut.value);
                date.setDate(date.getDate() + parseInt(dureeJours.value));
                
                // Formatage à la française
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                affichageRetour.textContent = `✈️ Date de retour : ${date.toLocaleDateString('fr-FR', options)}`;
            } else {
                affichageRetour.textContent = '';
            }
        };

        // On écoute la frappe au clavier ou le changement à la souris
        dateDebut.addEventListener('input', calculerRetour);
        dureeJours.addEventListener('input', calculerRetour);
        
        // On lance le calcul une première fois au chargement (très utile sur modifier_voyage.php !)
        calculerRetour();
    }

    // ==========================================
    // 4. DATE DE RETOUR DANS LA LISTE DES VOYAGES
    // ==========================================
    const datesRetourListe = document.querySelectorAll('.date-retour-liste');

    datesRetourListe.forEach(element => {
        const dateDebutVoyage = element.dataset.dateDebut;
        const dureeVoyage = parseInt(element.dataset.dureeJours);

        if (dateDebutVoyage && dureeVoyage > 0) {
            const dateRetour = new Date(dateDebutVoyage);
            dateRetour.setDate(dateRetour.getDate() + dureeVoyage);

            element.innerHTML = `<strong>Retour le :</strong> ${dateRetour.toLocaleDateString('fr-FR')}`;
        }
    });
});
