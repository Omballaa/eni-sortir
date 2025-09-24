/**
 * Sortie Modal Manager
 * Gère toutes les modales liées aux sorties (affichage, création, édition, annulation)
 * Utilise utils.js pour les fonctionnalités de base
 */

class SortieModal {
    constructor() {
        this.currentModal = null;
        this.init();
    }

    init() {
        console.log('🚀 Initialisation SortieModal');
        
        // Gestion des formulaires de sortie
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'sortie-create-form') {
                e.preventDefault();
                this.handleSortieCreate(e.target);
            } else if (e.target.id === 'sortie-edit-form') {
                e.preventDefault();
                this.handleSortieEdit(e.target);
            } else if (e.target.id === 'sortie-cancel-form') {
                e.preventDefault();
                this.handleSortieCancel(e.target);
            }
        });

        // Écouter l'événement personnalisé de chargement de modale
        document.addEventListener('modalContentLoaded', (e) => {
            console.log('📡 Événement modalContentLoaded reçu:', e.detail);
            const modal = e.detail.modal;
            
            // Vérifier quel formulaire a été chargé et l'initialiser
            if (modal.querySelector('#sortie-create-form')) {
                console.log('🆕 Formulaire de création détecté');
                this.initCreateForm();
            } else if (modal.querySelector('#sortie-edit-form')) {
                console.log('📝 Formulaire d\'édition détecté');  
                this.initEditForm();
            } else if (modal.querySelector('#sortie-cancel-form')) {
                console.log('❌ Formulaire d\'annulation détecté');
                this.initCancelForm();
            }
        });

        // Fallback avec MutationObserver pour d'autres cas
        this.setupMutationObserver();
    }

    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Formulaire de création
                        if (node.querySelector && node.querySelector('#sortie-create-form')) {
                            console.log('👁️ MutationObserver: Formulaire création détecté');
                            this.initCreateForm();
                        }
                        // Formulaire d'édition
                        if (node.querySelector && node.querySelector('#sortie-edit-form')) {
                            console.log('👁️ MutationObserver: Formulaire édition détecté');
                            this.initEditForm();
                        }
                        // Formulaire d'annulation
                        if (node.querySelector && node.querySelector('#sortie-cancel-form')) {
                            console.log('👁️ MutationObserver: Formulaire annulation détecté');
                            this.initCancelForm();
                        }
                    }
                });
            });
        });

        // Observer seulement si document.body existe
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            // Si le body n'existe pas encore, attendre le DOMContentLoaded
            document.addEventListener('DOMContentLoaded', () => {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        }
    }

    /**
     * Ouvre la modale d'affichage d'une sortie
     */
    openSortieModal(sortieId) {
        console.log('openSortieModal appelé avec sortieId:', sortieId);
        const url = `/sortie/${sortieId}/modal`;
        console.log('URL générée:', url);
        
        showModal(url, 'sortieModal', 'xl');
    }

    /**
     * Ouvre la modale de création d'une sortie
     */
    openSortieCreateModal() {
        console.log('openSortieCreateModal appelé');
        const url = '/sortie/nouvelle/modal';
        console.log('URL générée:', url);
        
        showModal(url, 'sortieCreateModal', 'lg');
    }

    /**
     * Ouvre la modale d'édition d'une sortie
     */
    openSortieEditModal(sortieId) {
        console.log('openSortieEditModal appelé avec sortieId:', sortieId);
        
        // Fermer la modale actuelle si elle existe
        const currentModal = document.querySelector('.modal.show');
        if (currentModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(currentModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
                
                // Attendre que la modale soit fermée avant d'ouvrir la nouvelle
                currentModal.addEventListener('hidden.bs.modal', () => {
                    this.doOpenSortieEditModal(sortieId);
                }, { once: true });
                return;
            }
        }
        
        // Si pas de modale actuelle, ouvrir directement
        this.doOpenSortieEditModal(sortieId);
    }

    /**
     * Ouvre réellement la modale d'édition
     */
    doOpenSortieEditModal(sortieId) {
        const url = `/sortie/${sortieId}/modifier/modal`;
        console.log('URL générée:', url);
        
        showModal(url, 'sortieEditModal', 'lg');
    }

    /**
     * Ouvre la modale d'annulation d'une sortie
     */
    openSortieCancelModal(sortieId) {
        console.log('openSortieCancelModal appelé avec sortieId:', sortieId);
        
        // Fermer la modale actuelle si elle existe
        const currentModal = document.querySelector('.modal.show');
        if (currentModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(currentModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
                
                // Attendre que la modale soit fermée avant d'ouvrir la nouvelle
                currentModal.addEventListener('hidden.bs.modal', () => {
                    this.doOpenSortieCancelModal(sortieId);
                }, { once: true });
                return;
            }
        }
        
        // Si pas de modale actuelle, ouvrir directement
        this.doOpenSortieCancelModal(sortieId);
    }

    /**
     * Ouvre réellement la modale d'annulation
     */
    doOpenSortieCancelModal(sortieId) {
        const url = `/sortie/${sortieId}/annuler/modal`;
        console.log('URL générée:', url);
        
        showModal(url, 'sortieCancelModal', 'md');
    }

    /**
     * Initialise le formulaire de création
     */
    initCreateForm() {
        console.log('🆕 === DÉBUT initCreateForm ===');
        const form = document.getElementById('sortie-create-form');
        if (!form) {
            console.log('❌ Formulaire de création non trouvé');
            return;
        }

        console.log('✅ Formulaire création trouvé:', form.id);
        this.initCommonFormFeatures(form);
        this.setupDateTimeValidation(form);
        this.setupLieuxLoading(form);
        console.log('🆕 === FIN initCreateForm ===');
    }

    /**
     * Initialise le formulaire d'édition
     */
    initEditForm() {
        console.log('📝 === DÉBUT initEditForm ===');
        const form = document.getElementById('sortie-edit-form');
        if (!form) {
            console.log('❌ Formulaire d\'édition non trouvé');
            return;
        }

        console.log('✅ Formulaire édition trouvé:', form.id);
        
        // 1. Charger les données originales AVANT tout le reste
        this.loadOriginalSortieData(form);
        
        // 2. Initialiser les fonctionnalités communes
        this.initCommonFormFeatures(form);
        
        // 3. Configurer la validation des dates
        this.setupDateTimeValidation(form);
        
        // 4. Configurer le chargement des lieux (après les données originales)
        this.setupLieuxLoading(form);
        
        console.log('📝 === FIN initEditForm ===');
    }

    /**
     * Initialise le formulaire d'annulation
     */
    initCancelForm() {
        console.log('❌ === DÉBUT initCancelForm ===');
        const form = document.getElementById('sortie-cancel-form');
        if (!form) {
            console.log('❌ Formulaire d\'annulation non trouvé');
            return;
        }

        console.log('✅ Formulaire annulation trouvé:', form.id);

        // Chercher le textarea par différents sélecteurs
        const motifField = form.querySelector('textarea[name*="motifAnnulation"]') || 
                          form.querySelector('textarea[id*="motifAnnulation"]') || 
                          form.querySelector('textarea');

        console.log('Champ motif trouvé:', motifField);
        
        if (motifField) {
            console.log('✅ Initialisation validation motif');
            motifField.addEventListener('input', () => {
                const charCount = motifField.value.length;
                let feedback = motifField.parentNode.querySelector('.char-feedback');
                if (!feedback) {
                    feedback = document.createElement('small');
                    feedback.className = 'char-feedback text-muted';
                    motifField.parentNode.appendChild(feedback);
                }
                feedback.textContent = `${charCount} caractères (minimum 10)`;
                
                if (charCount >= 10) {
                    motifField.classList.remove('is-invalid');
                    motifField.classList.add('is-valid');
                } else if (charCount > 0) {
                    motifField.classList.add('is-invalid');
                }
            });
        } else {
            console.log('❌ Champ motif non trouvé');
        }
        console.log('❌ === FIN initCancelForm ===');
    }

    /**
     * Fonctionnalités communes aux formulaires
     */
    initCommonFormFeatures(form) {
        // Validation en temps réel
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }

    /**
     * Configure le chargement dynamique des lieux
     */
    setupLieuxLoading(form) {
        console.log('=== DÉBUT setupLieuxLoading ===');
        console.log('Form ID:', form.id);
        
        // Essayer différents sélecteurs pour trouver les champs ville et lieu
        const villeSelect = form.querySelector('select[name*="ville"]') || 
                           form.querySelector('[id*="ville"]') ||
                           form.querySelector('#sortie_ville');
        
        const lieuSelect = form.querySelector('select[name*="lieu"]') || 
                          form.querySelector('[id*="lieu"]') ||
                          form.querySelector('#sortie_lieu');
        
        console.log('Sélecteurs testés:');
        console.log('- select[name*="ville"]:', form.querySelector('select[name*="ville"]'));
        console.log('- [id*="ville"]:', form.querySelector('[id*="ville"]'));
        console.log('- #sortie_ville:', form.querySelector('#sortie_ville'));
        console.log('- select[name*="lieu"]:', form.querySelector('select[name*="lieu"]'));
        console.log('- [id*="lieu"]:', form.querySelector('[id*="lieu"]'));
        console.log('- #sortie_lieu:', form.querySelector('#sortie_lieu'));
        
        console.log('Éléments trouvés:', {
            villeSelect: villeSelect?.id || villeSelect?.name,
            lieuSelect: lieuSelect?.id || lieuSelect?.name,
            formId: form.id,
            villeValue: villeSelect?.value,
            lieuValue: lieuSelect?.value,
            villeDisabled: villeSelect?.disabled,
            lieuDisabled: lieuSelect?.disabled
        });
        
        if (villeSelect && lieuSelect) {
            console.log('✓ Configuration chargement lieux pour:', form.id);
            
            // Écouter les changements de ville
            villeSelect.addEventListener('change', (e) => {
                const villeId = e.target.value;
                console.log('🔄 CHANGEMENT VILLE détecté:', villeId);
                this.loadLieuxForVille(villeId, lieuSelect);
            });

            // Chargement initial pour l'édition ou si une ville est déjà sélectionnée
            if (villeSelect.value) {
                console.log('📋 Ville présélectionnée:', villeSelect.value);
                if (form.id === 'sortie-edit-form') {
                    // Pour l'édition, récupérer les données originales
                    const originalData = this.getOriginalSortieData();
                    console.log('📝 Données originales édition:', originalData);
                    this.loadLieuxForVille(villeSelect.value, lieuSelect, originalData?.lieuId);
                } else {
                    // Pour la création, juste charger les lieux sans sélection
                    console.log('🆕 Mode création - chargement lieux');
                    this.loadLieuxForVille(villeSelect.value, lieuSelect);
                }
            } else {
                console.log('❌ Pas de ville sélectionnée - désactivation lieu');
                // Pas de ville sélectionnée : s'assurer que le lieu est désactivé
                lieuSelect.disabled = true;
                lieuSelect.innerHTML = '<option value="">Sélectionner d\'abord une ville</option>';
            }
        } else {
            console.error('❌ Éléments ville ou lieu non trouvés dans le formulaire', form.id);
            console.log('Tous les selects dans le form:');
            const allSelects = form.querySelectorAll('select');
            allSelects.forEach((select, index) => {
                console.log(`Select ${index}:`, {
                    id: select.id,
                    name: select.name,
                    classes: select.className
                });
            });
        }
        console.log('=== FIN setupLieuxLoading ===');
    }

    /**
     * Charge les lieux pour une ville donnée
     */
    loadLieuxForVille(villeId, lieuSelect, selectLieuId = null) {
        console.log('🏙️ === DÉBUT loadLieuxForVille ===');
        console.log('Paramètres:', {
            villeId,
            lieuSelectId: lieuSelect?.id,
            selectLieuId,
            lieuSelectDisabled: lieuSelect?.disabled
        });
        
        // Reset du select lieu
        lieuSelect.innerHTML = '<option value="">Chargement...</option>';
        lieuSelect.disabled = true;
        
        if (!villeId) {
            console.log('❌ Pas de villeId - arrêt');
            lieuSelect.innerHTML = '<option value="">Sélectionner d\'abord une ville</option>';
            lieuSelect.disabled = true;
            return;
        }
        
        // URL relative pour éviter les problèmes de base URL
        const url = `/lieu/by-ville?ville=${villeId}`;
        console.log('🌐 URL de chargement:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📡 Réponse reçue:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(lieux => {
            console.log('🏢 Lieux reçus:', lieux);
            console.log('Nombre de lieux:', lieux.length);
            
            lieuSelect.innerHTML = '<option value="">Sélectionner un lieu...</option>';
            
            lieux.forEach((lieu, index) => {
                console.log(`Lieu ${index}:`, lieu);
                const option = document.createElement('option');
                option.value = lieu.id;
                option.textContent = `${lieu.nomLieu} - ${lieu.rue}`;
                
                // Sélectionner le lieu spécifique si fourni
                if (selectLieuId && lieu.id == selectLieuId) {
                    option.selected = true;
                    console.log('✅ Lieu sélectionné:', lieu.nomLieu);
                }
                
                lieuSelect.appendChild(option);
            });
            
            // IMPORTANT: Réactiver le champ lieu après le chargement
            lieuSelect.disabled = false;
            console.log('✅ Champ lieu réactivé avec', lieux.length, 'options');
            console.log('État final lieu select:', {
                disabled: lieuSelect.disabled,
                optionsCount: lieuSelect.options.length,
                selectedIndex: lieuSelect.selectedIndex
            });
        })
        .catch(error => {
            console.error('❌ Erreur chargement lieux:', error);
            lieuSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            lieuSelect.disabled = true;
            if (typeof showToast === 'function') {
                showToast('Erreur lors du chargement des lieux', 'error');
            }
        })
        .finally(() => {
            console.log('🏙️ === FIN loadLieuxForVille ===');
        });
    }

    /**
     * Récupère les données originales de la sortie (pour l'édition)
     */
    getOriginalSortieData() {
        const dataScript = document.getElementById('original-sortie-data');
        if (dataScript) {
            try {
                return JSON.parse(dataScript.textContent);
            } catch (e) {
                console.error('Erreur parsing données originales:', e);
            }
        }
        return null;
    }

    /**
     * Charge les données originales pour la restauration
     */
    loadOriginalSortieData(form) {
        console.log('📋 === DÉBUT loadOriginalSortieData ===');
        const originalData = this.getOriginalSortieData();
        if (originalData) {
            console.log('✅ Données originales chargées:', originalData);
            
            // Stocker sur le formulaire pour usage ultérieur
            form.originalData = originalData;
            
            // Forcer la sélection de la ville pour l'édition
            this.setInitialEditValues(form, originalData);
            
            // Exposer la fonction de restauration globalement
            window.loadOriginalData = () => this.restoreOriginalData(form);
        } else {
            console.log('❌ Pas de données originales trouvées');
        }
        console.log('📋 === FIN loadOriginalSortieData ===');
    }

    /**
     * Force les valeurs initiales pour l'édition
     */
    setInitialEditValues(form, originalData) {
        console.log('🔧 === DÉBUT setInitialEditValues ===');
        console.log('Données à appliquer:', originalData);
        
        // Trouver le select ville
        const villeSelect = form.querySelector('select[name*="ville"]') || 
                           form.querySelector('[id*="ville"]') ||
                           form.querySelector('#sortie_ville');
        
        if (villeSelect && originalData.villeId) {
            console.log('🏙️ Forçage sélection ville:', originalData.villeId);
            console.log('Options ville disponibles:');
            Array.from(villeSelect.options).forEach((option, index) => {
                console.log(`  Option ${index}: value="${option.value}" text="${option.text}"`);
            });
            
            // Forcer la valeur
            villeSelect.value = originalData.villeId;
            console.log('Ville après forçage:', villeSelect.value);
            
            // Déclencher l'événement change si la valeur a bien été définie
            if (villeSelect.value == originalData.villeId) {
                console.log('🎯 Déclenchement événement change ville');
                villeSelect.dispatchEvent(new Event('change'));
            } else {
                console.log('❌ Impossible de définir la ville - valeur non trouvée');
            }
        } else {
            console.log('❌ Select ville non trouvé ou villeId manquant');
        }
        console.log('🔧 === FIN setInitialEditValues ===');
    }

    /**
     * Restaure les données originales
     */
    restoreOriginalData(form) {
        const originalData = form.originalData;
        if (!originalData) return;

        console.log('Restauration des données originales');
        
        // Restaurer les champs texte
        const fields = {
            nom: form.querySelector('input[name*="nom"]'),
            dateHeureDebut: form.querySelector('input[name*="dateHeureDebut"]'),
            duree: form.querySelector('input[name*="duree"]'),
            dateLimiteInscription: form.querySelector('input[name*="dateLimiteInscription"]'),
            nbInscriptionsMax: form.querySelector('input[name*="nbInscriptionsMax"]'),
            infosSortie: form.querySelector('textarea[name*="infosSortie"]'),
            ville: form.querySelector('select[name*="ville"]'),
            lieu: form.querySelector('select[name*="lieu"]')
        };
        
        // Restaurer les valeurs
        if (fields.nom) fields.nom.value = originalData.nom || '';
        if (fields.dateHeureDebut) fields.dateHeureDebut.value = originalData.dateHeureDebut || '';
        if (fields.duree) fields.duree.value = originalData.duree || 0;
        if (fields.dateLimiteInscription) fields.dateLimiteInscription.value = originalData.dateLimiteInscription || '';
        if (fields.nbInscriptionsMax) fields.nbInscriptionsMax.value = originalData.nbInscriptionsMax || '';
        if (fields.infosSortie) fields.infosSortie.value = originalData.infosSortie || '';
        
        // Restaurer ville et lieux
        if (fields.ville && fields.lieu) {
            fields.ville.value = originalData.villeId || '';
            this.loadLieuxForVille(originalData.villeId, fields.lieu, originalData.lieuId);
        }
        
        // Clear results
        const resultDiv = document.getElementById('form-result');
        if (resultDiv) {
            resultDiv.innerHTML = '';
        }
        
        if (typeof showToast === 'function') {
            showToast('Valeurs originales restaurées', 'info');
        }
    }
    setupDateTimeValidation(form) {
        const dateDebutInput = form.querySelector('input[name*="dateHeureDebut"]');
        const dateLimiteInput = form.querySelector('input[name*="dateLimiteInscription"]');
        
        if (dateDebutInput && dateLimiteInput) {
            [dateDebutInput, dateLimiteInput].forEach(input => {
                input.addEventListener('change', () => {
                    this.validateDates(dateDebutInput, dateLimiteInput);
                });
            });
        }
    }

    /**
     * Validation des dates
     */
    validateDates(dateDebutInput, dateLimiteInput) {
        const dateDebut = new Date(dateDebutInput.value);
        const dateLimite = new Date(dateLimiteInput.value);
        const now = new Date();

        let isValid = true;

        // Date de début dans le futur
        if (dateDebut <= now) {
            this.showFieldError(dateDebutInput, 'La date doit être dans le futur');
            isValid = false;
        }

        // Date limite avant date de début
        if (dateLimite >= dateDebut) {
            this.showFieldError(dateLimiteInput, 'La date limite doit être avant la date de début');
            isValid = false;
        }

        if (isValid) {
            dateDebutInput.classList.add('is-valid');
            dateLimiteInput.classList.add('is-valid');
        }

        return isValid;
    }

    /**
     * Gère la création d'une sortie
     */
    handleSortieCreate(form) {
        if (!this.validateSortieForm(form)) {
            return;
        }

        const formData = new FormData(form);
        const resultDiv = document.getElementById('form-result');
        
        // Déterminer quelle action (save ou publish)
        const clickedButton = document.activeElement;
        const action = clickedButton.name === 'publish' ? 'publish' : 'save';
        formData.append('action', action);
        
        this.submitSortieForm(form, formData, 'Création de la sortie...')
            .then(data => {
                if (data.success) {
                    showToast(`Sortie ${action === 'publish' ? 'publiée' : 'créée'} avec succès !`, 'success');
                    this.closeModalAndRefresh();
                }
            });
    }

    /**
     * Gère l'édition d'une sortie
     */
    handleSortieEdit(form) {
        if (!this.validateSortieForm(form)) {
            return;
        }

        const formData = new FormData(form);
        
        // Déterminer quelle action (save ou publish) - même logique que création
        const clickedButton = document.activeElement;
        const action = clickedButton.name === 'publish' ? 'publish' : 'save';
        formData.append('action', action);
        
        this.submitSortieForm(form, formData, 'Modification de la sortie...')
            .then(data => {
                if (data.success) {
                    const message = action === 'publish' ? 'Sortie modifiée et publiée avec succès !' : 'Sortie modifiée avec succès !';
                    showToast(message, 'success');
                    this.closeModalAndRefresh();
                }
            });
    }

    /**
     * Gère l'annulation d'une sortie
     */
    handleSortieCancel(form) {
        // Chercher le textarea par différents sélecteurs
        const motifField = form.querySelector('textarea[name*="motifAnnulation"]') || 
                          form.querySelector('textarea[id*="motifAnnulation"]') || 
                          form.querySelector('textarea');
        const confirmCheckbox = form.querySelector('#confirm-cancel');
        
        console.log('Éléments trouvés:', {
            motifField: motifField,
            motifFieldValue: motifField ? motifField.value : 'N/A',
            confirmCheckbox: confirmCheckbox,
            confirmChecked: confirmCheckbox ? confirmCheckbox.checked : 'N/A'
        });
        
        if (!motifField) {
            console.error('❌ Champ motif non trouvé');
            showToast('Erreur: champ motif non trouvé', 'error');
            return;
        }
        
        if (!confirmCheckbox) {
            console.error('❌ Checkbox de confirmation non trouvée');
            showToast('Erreur: checkbox de confirmation non trouvée', 'error');
            return;
        }
        
        if (!confirmCheckbox.checked || !motifField.value.trim() || motifField.value.trim().length < 10) {
            showToast('Veuillez remplir tous les champs requis', 'warning');
            return;
        }

        const formData = new FormData(form);
        
        this.submitSortieForm(form, formData, 'Annulation de la sortie...')
            .then(data => {
                if (data.success) {
                    showToast('Sortie annulée avec succès', 'success');
                    this.closeModalAndRefresh();
                }
            });
    }

    /**
     * Soumission générique des formulaires de sortie
     */
    async submitSortieForm(form, formData, loadingMessage) {
        const resultDiv = document.getElementById('form-result');
        const submitBtns = form.querySelectorAll('button[type="submit"]');
        
        // État de chargement
        submitBtns.forEach(btn => this.setLoadingState(btn, true));
        resultDiv.innerHTML = `<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> ${loadingMessage}</div>`;

        try {
            const response = await fetch(form.dataset.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle"></i> ${data.message}</div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${data.message}</div>`;
                showToast('Erreur lors de l\'opération', 'error');
                
                if (data.errors) {
                    this.displayFormErrors(form, data.errors);
                }
            }

            return data;
        } catch (error) {
            console.error('Erreur:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Erreur de connexion</div>';
            showToast('Erreur de connexion', 'error');
            throw error;
        } finally {
            submitBtns.forEach(btn => this.setLoadingState(btn, false));
        }
    }

    /**
     * Validation spécifique aux formulaires de sortie
     */
    validateSortieForm(form) {
        let isValid = true;

        // Validation des champs requis
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Validation des dates
        const dateDebutInput = form.querySelector('input[name*="dateHeureDebut"]');
        const dateLimiteInput = form.querySelector('input[name*="dateLimiteInscription"]');
        if (dateDebutInput && dateLimiteInput) {
            if (!this.validateDates(dateDebutInput, dateLimiteInput)) {
                isValid = false;
            }
        }

        // Validation du nombre max d'inscriptions
        const nbMaxInput = form.querySelector('input[name*="nbInscriptionsMax"]');
        if (nbMaxInput && nbMaxInput.value) {
            const value = parseInt(nbMaxInput.value);
            if (value < 1 || value > 100) {
                this.showFieldError(nbMaxInput, 'Entre 1 et 100 participants');
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Actions sur les inscriptions
     */
    inscriptionAction(sortieId, action) {
        const url = `/sortie/${sortieId}/${action}`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Rafraîchir le contenu de la modale
                this.refreshModalContent();
                
                // Rafraîchir la liste des sorties si on est sur le dashboard
                if (document.getElementById('sorties-container')) {
                    setTimeout(() => {
                        refreshSortiesList();
                    }, 300);
                }
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors de l\'inscription/désinscription', 'error');
        });
    }

    /**
     * Publication d'une sortie
     */
    publishSortie(sortieId) {
        if (!confirm('Voulez-vous publier cette sortie ? Elle sera visible par tous les participants.')) {
            return;
        }

        fetch(`/sortie/${sortieId}/publier`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Sortie publiée avec succès !', 'success');
                this.refreshModalContent();
                
                // Rafraîchir la liste des sorties si on est sur le dashboard
                if (document.getElementById('sorties-container')) {
                    setTimeout(() => {
                        refreshSortiesList();
                    }, 300);
                }
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors de la publication', 'error');
        });
    }

    /**
     * Rafraîchit le contenu de la modale ouverte
     */
    refreshModalContent() {
        // Cette méthode n'est plus utilisée - nous utilisons refreshSortiesList() maintenant
        console.log('refreshModalContent: méthode dépréciée, utilisez refreshSortiesList()');
    }

    /**
     * Ferme la modale et rafraîchit la page
     */
    /**
     * Ferme la modale et rafraîchit la page
     */
    closeModalAndRefresh() {
        setTimeout(() => {
            // Fermer toute modale actuellement ouverte
            const currentModal = document.querySelector('.modal.show');
            if (currentModal) {
                const bootstrapModal = bootstrap.Modal.getInstance(currentModal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
            // Rafraîchir la liste des sorties si on est sur le dashboard
            if (document.getElementById('sorties-container')) {
                refreshSortiesList();
            }
        }, 1000); // Réduit le délai à 1 seconde
    }

    // Méthodes utilitaires partagées
    validateField(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'Ce champ est requis');
            return false;
        }

        field.classList.add('is-valid');
        return true;
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid', 'is-valid');
    }

    displayFormErrors(form, errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name*="${fieldName}"]`);
            if (field) {
                this.showFieldError(field, errors[fieldName]);
            }
        });
    }

    setLoadingState(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Traitement...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.textContent;
        }
    }

    /**
     * Remet à zéro le formulaire
     */
    resetForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        console.log('Reset formulaire:', formId);
        
        // Reset natif du formulaire
        form.reset();
        
        // Reset spécifique pour les selects dynamiques
        const lieuSelect = form.querySelector('select[name*="lieu"]');
        if (lieuSelect) {
            lieuSelect.innerHTML = '<option value="">Sélectionner d\'abord une ville</option>';
            lieuSelect.disabled = true;
        }
        
        // Clear results
        const resultDiv = document.getElementById('form-result');
        if (resultDiv) {
            resultDiv.innerHTML = '';
        }
        
        // Supprimer les classes de validation
        const inputs = form.querySelectorAll('.is-valid, .is-invalid');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        if (typeof showToast === 'function') {
            showToast('Formulaire réinitialisé', 'info');
        }
    }
}

// Initialisation
let sortieModal;

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSortieModal);
} else {
    initializeSortieModal();
}

function initializeSortieModal() {
    sortieModal = new SortieModal();
    
    // Fonctions globales pour compatibilité
    window.openSortieModal = (sortieId) => sortieModal.openSortieModal(sortieId);
    window.openSortieCreateModal = () => sortieModal.openSortieCreateModal();
    window.openSortieEditModal = (sortieId) => sortieModal.openSortieEditModal(sortieId);
    window.openSortieCancelModal = (sortieId) => sortieModal.openSortieCancelModal(sortieId);
    window.inscriptionAction = (sortieId, action) => sortieModal.inscriptionAction(sortieId, action);
    window.publishSortie = (sortieId) => sortieModal.publishSortie(sortieId);
}
window.resetForm = (formId) => sortieModal.resetForm(formId);