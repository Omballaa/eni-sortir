/**
 * Profil Modal Manager
 * Gère les modales de profil (affichage et édition)
 * Utilise utils.js pour les fonctionnalités de base
 */

class ProfilModal {
    constructor() {
        this.currentModal = null;
        this.init();
    }

    init() {
        // Gestion des formulaires de profil
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'profil-edit-form') {
                e.preventDefault();
                this.handleProfilEdit(e.target);
            }
        });
    }

    /**
     * Ouvre la modale d'affichage du profil
     */
    openProfilModal(userId) {
        console.log('openProfilModal appelé avec userId:', userId);
        const url = `/profil/${userId}/modal`;
        console.log('URL générée:', url);
        
        showModal(url, 'profilModal', 'lg');
    }

    /**
     * Ouvre la modale d'édition du profil
     */
    openProfilEditModal(userId) {
        console.log('openProfilEditModal appelé avec userId:', userId);
        const url = `/profil/${userId}/edit/modal`;
        console.log('URL générée:', url);
        
        showModal(url, 'profilEditModal', 'lg');
    }

    /**
     * Initialise le formulaire d'édition
     */
    initEditForm() {
        // Validation en temps réel
        const form = document.getElementById('profil-edit-form');
        if (!form) return;

        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        // Validation des mots de passe
        const passwordFields = form.querySelectorAll('input[type="password"]');
        if (passwordFields.length === 2) {
            passwordFields.forEach(field => {
                field.addEventListener('input', () => this.validatePasswords(passwordFields));
            });
        }

        // Preview de l'image
        const photoInput = form.querySelector('input[type="file"]');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => this.previewPhoto(e));
        }
    }

    /**
     * Gère la soumission du formulaire d'édition
     */
    handleProfilEdit(form) {
        if (!this.validateForm(form)) {
            return;
        }

        const formData = new FormData(form);
        const resultDiv = document.getElementById('form-result');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // État de chargement
        this.setLoadingState(submitBtn, true);
        resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Mise à jour en cours...</div>';

        fetch(form.dataset.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle"></i> ${data.message}</div>`;
                showToast('Profil mis à jour avec succès !', 'success');
                
                // Fermer la modale après 2 secondes
                setTimeout(() => {
                    if (this.currentModal) {
                        this.currentModal.hide();
                    }
                    // Rafraîchir la page ou le profil affiché
                    this.refreshProfilData();
                }, 2000);
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${data.message}</div>`;
                showToast('Erreur lors de la mise à jour', 'error');
                
                // Afficher les erreurs de validation
                if (data.errors) {
                    this.displayFormErrors(form, data.errors);
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Erreur de connexion</div>';
            showToast('Erreur de connexion', 'error');
        })
        .finally(() => {
            this.setLoadingState(submitBtn, false);
        });
    }

    /**
     * Validation du formulaire
     */
    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Validation spécifique des mots de passe
        const passwordFields = form.querySelectorAll('input[type="password"]');
        if (passwordFields.length === 2) {
            if (!this.validatePasswords(passwordFields)) {
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Valide un champ individuel
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Champ requis
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Ce champ est requis';
        }

        // Validation email
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Format d\'email invalide';
            }
        }

        // Validation téléphone
        if (field.name === 'telephone' && value) {
            const phoneRegex = /^(?:\+33|0)[1-9](?:[0-9]{8})$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                message = 'Format de téléphone invalide';
            }
        }

        // Affichage visuel
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            
            // Afficher le message d'erreur
            let errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        }

        return isValid;
    }

    /**
     * Valide la concordance des mots de passe
     */
    validatePasswords(passwordFields) {
        const [password1, password2] = passwordFields;
        
        if (password1.value && password2.value && password1.value !== password2.value) {
            password2.classList.add('is-invalid');
            let errorDiv = password2.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                password2.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = 'Les mots de passe ne correspondent pas';
            return false;
        }

        if (password1.value === password2.value) {
            password2.classList.remove('is-invalid');
            password2.classList.add('is-valid');
        }

        return true;
    }

    /**
     * Efface les erreurs d'un champ
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid', 'is-valid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Preview de la photo uploadée
     */
    previewPhoto(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validation du fichier
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showToast('Type de fichier non supporté', 'warning');
            event.target.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) { // 2MB
            showToast('Fichier trop volumineux (max 2MB)', 'warning');
            event.target.value = '';
            return;
        }

        // Créer le preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.querySelector('.img-thumbnail');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }

    /**
     * Affiche les erreurs de validation du serveur
     */
    displayFormErrors(form, errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');
                let errorDiv = field.parentNode.querySelector('.invalid-feedback');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    field.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = errors[fieldName];
            }
        });
    }

    /**
     * Gère l'état de chargement des boutons
     */
    setLoadingState(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Mise à jour...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Enregistrer';
        }
    }

    /**
     * Rafraîchit les données du profil affichées
     */
    refreshProfilData() {
        // Cette méthode n'est plus utilisée avec le système de modales
        console.log('refreshProfilData: méthode dépréciée pour les modales');
        
        // Ou recharger complètement si nécessaire
        if (window.location.pathname.includes('/profil')) {
            window.location.reload();
        }
    }
}

// Initialisation
const profilModal = new ProfilModal();

// Fonctions globales pour compatibilité
window.openProfilModal = (userId) => profilModal.openProfilModal(userId);
window.openProfilEditModal = (userId) => profilModal.openProfilEditModal(userId);