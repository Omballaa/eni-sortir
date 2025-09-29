/**
 * Lieu Modal Manager
 * Gère la création et modification de lieux avec géocodage automatique
 */

class LieuModal {
    constructor() {
        this.currentModal = null;
        this.init();
    }

    init() {
        devLog('🚀 Initialisation LieuModal');
        
        // Écouter l'événement personnalisé de chargement de modale
        document.addEventListener('modalContentLoaded', (e) => {
            const modal = e.detail.modal;
            
            // Vérifier si c'est un formulaire de lieu
            if (modal.querySelector('#lieu-form')) {
                devLog('🏢 Formulaire de lieu détecté');
                this.initLieuForm(modal);
            }
        });

        // Fallback avec MutationObserver
        this.setupMutationObserver();
    }

    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.querySelector && node.querySelector('#lieu-form')) {
                        devLog('👁️ MutationObserver: Formulaire lieu détecté');
                        this.initLieuForm(node);
                    }
                });
            });
        });

        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Initialise le formulaire de lieu
     */
    initLieuForm(container) {
        devLog('🏢 === DÉBUT initLieuForm ===');
        
        const form = container.querySelector('#lieu-form');
        if (!form) {
            devLog('❌ Formulaire lieu non trouvé');
            return;
        }

        // Configurer le géocodage automatique
        this.setupGeocodingFeature(form);
        
        // Validation en temps réel
        this.setupFormValidation(form);
        
        // Gestion de la soumission du formulaire
        this.setupFormSubmission(form);
        
        devLog('🏢 === FIN initLieuForm ===');
    }

    /**
     * Configure la fonctionnalité de géocodage automatique
     */
    setupGeocodingFeature(form) {
        const geocodeBtn = form.querySelector('#geocode-btn');
        const rueInput = form.querySelector('input[name*="rue"]');
        const villeSelect = form.querySelector('select[name*="ville"]');
        const latitudeInput = form.querySelector('input[name*="latitude"]');
        const longitudeInput = form.querySelector('input[name*="longitude"]');

        if (!geocodeBtn || !rueInput || !villeSelect) {
            devLog('❌ Éléments requis pour géocodage non trouvés');
            return;
        }

        devLog('✅ Configuration géocodage automatique');

        // Géocodage au clic sur le bouton
        geocodeBtn.addEventListener('click', () => {
            this.performGeocoding(form, rueInput, villeSelect, latitudeInput, longitudeInput);
        });

        // Géocodage automatique quand rue et ville sont remplis
        const autoGeocode = () => {
            if (rueInput.value.trim() && villeSelect.value) {
                // Attendre un peu avant de géocoder (éviter les appels multiples)
                clearTimeout(this.geocodeTimeout);
                this.geocodeTimeout = setTimeout(() => {
                    this.performGeocoding(form, rueInput, villeSelect, latitudeInput, longitudeInput, true);
                }, 1000);
            }
        };

        rueInput.addEventListener('input', autoGeocode);
        villeSelect.addEventListener('change', autoGeocode);
    }

    /**
     * Effectue le géocodage
     */
    async performGeocoding(form, rueInput, villeSelect, latitudeInput, longitudeInput, isAuto = false) {
        const rue = rueInput.value.trim();
        const villeId = villeSelect.value;

        if (!rue || !villeId) {
            if (!isAuto) {
                showToast('Veuillez remplir l\'adresse et sélectionner une ville', 'warning');
            }
            return;
        }

        const geocodeBtn = form.querySelector('#geocode-btn');
        const originalBtnContent = geocodeBtn.innerHTML;

        try {
            // État de chargement
            geocodeBtn.disabled = true;
            geocodeBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Recherche...';

            const response = await fetch('/lieu/geocode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    rue: rue,
                    ville_id: villeId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Remplir les coordonnées
                if (latitudeInput) latitudeInput.value = data.latitude.toFixed(8);
                if (longitudeInput) longitudeInput.value = data.longitude.toFixed(8);

                // Indication visuelle de succès
                if (latitudeInput) latitudeInput.classList.add('is-valid');
                if (longitudeInput) longitudeInput.classList.add('is-valid');

                if (!isAuto) {
                    showToast(`Coordonnées trouvées ! (Confiance: ${Math.round(data.confidence * 100)}%)`, 'success');
                }

                devLog('✅ Géocodage réussi:', data);
            } else {
                if (!isAuto) {
                    showToast(data.message || 'Impossible de trouver les coordonnées', 'error');
                }
                devLog('❌ Échec géocodage:', data.message);
            }
        } catch (error) {
            devLog('❌ Erreur géocodage:', error);
            if (!isAuto) {
                showToast('Erreur lors de la recherche des coordonnées', 'error');
            }
        } finally {
            // Restaurer le bouton
            geocodeBtn.disabled = false;
            geocodeBtn.innerHTML = originalBtnContent;
        }
    }

    /**
     * Configure la validation du formulaire
     */
    setupFormValidation(form) {
        const inputs = form.querySelectorAll('input[required], select[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }

    /**
     * Configure la gestion de la soumission du formulaire
     */
    setupFormSubmission(form) {
        // S'assurer qu'on n'ajoute l'événement qu'une seule fois
        if (form.dataset.submitHandlerAdded) {
            return;
        }
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLieuSubmit(form);
        });
        
        form.dataset.submitHandlerAdded = 'true';
    }

    /**
     * Gère la soumission du formulaire de lieu
     */
    async handleLieuSubmit(form) {
        // Éviter les soumissions multiples
        if (form.dataset.submitting === 'true') {
            console.log('🔧 Soumission déjà en cours, ignorée');
            return;
        }
        
        form.dataset.submitting = 'true';
        
        // Le bouton submit est dans le footer de la modal, pas dans le form
        const modal = form.closest('.modal');
        const submitBtn = modal ? modal.querySelector('button[type="submit"]') : form.querySelector('button[type="submit"]');
        
        if (!submitBtn) {
            console.error('🔧 Bouton submit non trouvé');
            showToast('Erreur: bouton submit non trouvé', 'error');
            form.dataset.submitting = 'false';
            return;
        }
        
        const originalBtnContent = submitBtn.innerHTML;
        
        // Validation côté client
        if (!this.validateForm(form)) {
            showToast('Veuillez corriger les erreurs du formulaire', 'warning');
            form.dataset.submitting = 'false';
            return;
        }

        try {
            // État de chargement
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Création...';

            const formData = new FormData(form);
            
            const response = await fetch('/lieu/create-ajax', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                showToast('Lieu créé avec succès !', 'success');
                
                // Mettre à jour tous les selects de lieu dans les formulaires sortie
                this.updateLieuSelects(data.lieu);
                
                // Fermer la modal
                this.closeModal();
                
            } else {
                // Afficher les erreurs
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        showToast(error, 'error');
                    });
                } else {
                    showToast(data.message || 'Erreur lors de la création du lieu', 'error');
                }
            }
        } catch (error) {
            devLog('❌ Erreur soumission lieu:', error);
            console.error('🔧 Erreur soumission lieu:', error);
            showToast('Erreur lors de la création du lieu', 'error');
        } finally {
            // Restaurer le bouton et permettre de nouvelles soumissions
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnContent;
            form.dataset.submitting = 'false';
        }
    }

    /**
     * Met à jour tous les selects de lieu avec le nouveau lieu créé
     */
    updateLieuSelects(nouveauLieu) {
        console.log('🔧 updateLieuSelects appelé avec:', nouveauLieu);
        
        // Chercher tous les selects de lieu dans la page
        const lieuSelects = document.querySelectorAll('select[name*="lieu"]:not([form="lieu-form"])');
        console.log('🔧 Selects de lieu trouvés:', lieuSelects.length);
        
        lieuSelects.forEach((select, index) => {
            console.log(`🔧 Traitement select ${index}:`, select);
            
            // Vérifier si le lieu appartient à la ville actuellement sélectionnée
            const villeSelect = this.findVilleSelectForLieuSelect(select);
            console.log('🔧 Ville select trouvé:', villeSelect, 'value:', villeSelect?.value);
            
            if (villeSelect && villeSelect.value == nouveauLieu.ville.id) {
                console.log('🔧 Ajout du lieu au select car ville correspond');
                
                // Vérifier si le lieu n'existe pas déjà dans le select
                const existingOption = select.querySelector(`option[value="${nouveauLieu.id}"]`);
                if (existingOption) {
                    console.log('🔧 Lieu déjà présent, sélection seulement');
                    existingOption.selected = true;
                } else {
                    console.log('🔧 Ajout du nouveau lieu');
                    // Ajouter le nouveau lieu au select
                    const option = document.createElement('option');
                    option.value = nouveauLieu.id;
                    option.textContent = `${nouveauLieu.nom}${nouveauLieu.rue ? ' - ' + nouveauLieu.rue : ''}`;
                    option.selected = true;
                    
                    select.appendChild(option);
                }
                
                select.disabled = false;
                
                // Supprimer les messages d'erreur de validation
                this.clearFieldError(select);
                
                // Supprimer aussi les messages d'erreur du conteneur parent (div.mb-3)
                const fieldContainer = select.closest('.mb-3');
                if (fieldContainer) {
                    const errorElements = fieldContainer.querySelectorAll('.invalid-feedback, .text-danger');
                    errorElements.forEach(errorEl => errorEl.remove());
                }
                
                // Marquer le champ comme valide
                select.classList.remove('is-invalid');
                select.classList.add('is-valid');
                
                // Déclencher l'événement change pour mettre à jour l'interface
                select.dispatchEvent(new Event('change'));
                
                devLog('✅ Lieu ajouté au select:', nouveauLieu.nom);
                console.log('🔧 Lieu ajouté avec succès');
            } else {
                console.log('🔧 Ville ne correspond pas ou select ville non trouvé');
            }
        });
    }

    /**
     * Trouve le select de ville correspondant à un select de lieu
     */
    findVilleSelectForLieuSelect(lieuSelect) {
        const form = lieuSelect.closest('form');
        if (form) {
            return form.querySelector('select[name*="ville"]');
        }
        return null;
    }

    /**
     * Valide l'ensemble du formulaire
     */
    validateForm(form) {
        let isValid = true;
        
        const requiredFields = form.querySelectorAll('input[required], select[required]');
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Ferme spécifiquement la modal de lieu (pas toutes les modales)
     */
    closeModal() {
        // Méthode 1 : Chercher par ID spécifique
        let lieuModal = document.querySelector('#lieuCreateModal');
        
        // Méthode 2 : Chercher la modal qui contient le formulaire de lieu
        if (!lieuModal) {
            const lieuForm = document.querySelector('#lieu-form');
            if (lieuForm) {
                lieuModal = lieuForm.closest('.modal');
            }
        }
        
        // Méthode 3 : Chercher dans toutes les modales ouvertes celle qui a un formulaire de lieu
        if (!lieuModal) {
            const openModals = document.querySelectorAll('.modal.show');
            for (const modal of openModals) {
                if (modal.querySelector('#lieu-form')) {
                    lieuModal = modal;
                    break;
                }
            }
        }
        
        if (lieuModal && lieuModal.classList.contains('show')) {
            const bootstrapModal = bootstrap.Modal.getInstance(lieuModal);
            if (bootstrapModal) {
                console.log('🔧 Fermeture de la modal de lieu');
                bootstrapModal.hide();
            }
        } else {
            console.log('🔧 Modal de lieu non trouvée pour fermeture');
        }
    }

    /**
     * Valide un champ
     */
    validateField(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'Ce champ est requis');
            return false;
        }

        // Validation spécifique pour latitude
        if (field.name.includes('latitude') && value) {
            const lat = parseFloat(value);
            if (isNaN(lat) || lat < -90 || lat > 90) {
                this.showFieldError(field, 'La latitude doit être entre -90 et 90');
                return false;
            }
        }

        // Validation spécifique pour longitude
        if (field.name.includes('longitude') && value) {
            const lon = parseFloat(value);
            if (isNaN(lon) || lon < -180 || lon > 180) {
                this.showFieldError(field, 'La longitude doit être entre -180 et 180');
                return false;
            }
        }

        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        return true;
    }

    /**
     * Affiche une erreur de champ
     */
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    /**
     * Supprime l'erreur d'un champ
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid', 'is-valid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Ouvre la modal de création de lieu
     */
    openLieuCreateModal() {
        console.log('🔧 openLieuCreateModal appelé - DEBUT');
        devLog('openLieuCreateModal appelé');
        
        // Récupérer la ville sélectionnée dans le formulaire de sortie
        const villeSelectInSortieForm = document.querySelector('select[name*="ville"]:not([form="lieu-form"])');
        const selectedVilleId = villeSelectInSortieForm ? villeSelectInSortieForm.value : null;
        
        let url = '/lieu/form-modal';
        if (selectedVilleId) {
            url += `?ville_id=${selectedVilleId}`;
            devLog('Ville pré-sélectionnée:', selectedVilleId);
        }
        
        console.log('🔧 URL générée:', url);
        devLog('URL générée:', url);
        
        // Vérifier si showModal existe
        if (typeof showModal === 'function') {
            console.log('🔧 showModal existe, appel en cours...');
            showModal(url, 'lieuCreateModal', 'lg');
        } else {
            console.error('🔧 showModal n\'existe pas !');
            alert('Erreur: fonction showModal non trouvée');
        }
    }
}

// Initialisation
let lieuModal;

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeLieuModal);
} else {
    initializeLieuModal();
}

function initializeLieuModal() {
    console.log('🔧 initializeLieuModal appelé');
    lieuModal = new LieuModal();
    
    // Fonction globale pour compatibilité
    window.openLieuCreateModal = () => {
        console.log('🔧 window.openLieuCreateModal appelé');
        return lieuModal.openLieuCreateModal();
    };
    
    console.log('🔧 openLieuCreateModal défini sur window:', typeof window.openLieuCreateModal);
}