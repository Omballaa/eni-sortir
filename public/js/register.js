/**
 * Script pour la validation en temps réel du formulaire d'enregistrement
 */

// Variables pour le debouncing
let usernameTimeout;
let emailTimeout;

/**
 * Initialise la validation en temps réel
 */
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('registration_form_pseudo');
    const emailInput = document.getElementById('registration_form_mail');
    const passwordInput = document.getElementById('registration_form_plainPassword');
    const confirmPasswordInput = document.getElementById('registration_form_confirmPassword');
    const submitButton = document.querySelector('button[type="submit"]');

    if (usernameInput) {
        usernameInput.addEventListener('input', debounceValidateUsername);
    }

    if (emailInput) {
        emailInput.addEventListener('input', debounceValidateEmail);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validatePassword);
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);
    }

    // Validation initiale du bouton de soumission
    updateSubmitButton();
});

/**
 * Debounce de la validation du nom d'utilisateur
 */
function debounceValidateUsername() {
    clearTimeout(usernameTimeout);
    usernameTimeout = setTimeout(validateUsername, 500);
}

/**
 * Debounce de la validation de l'email
 */
function debounceValidateEmail() {
    clearTimeout(emailTimeout);
    emailTimeout = setTimeout(validateEmail, 500);
}

/**
 * Valide le nom d'utilisateur
 */
function validateUsername() {
    const usernameInput = document.getElementById('registration_form_pseudo');
    const feedbackDiv = getOrCreateFeedback(usernameInput);
    const username = usernameInput.value.trim();

    if (username.length < 3) {
        showValidationFeedback(usernameInput, feedbackDiv, 'invalid', 'Le nom d\'utilisateur doit contenir au moins 3 caractères');
        return;
    }

    // Afficher le loading
    showValidationFeedback(usernameInput, feedbackDiv, 'loading', 'Vérification de la disponibilité...');

    // Requête AJAX
    fetch('/check-username', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'username=' + encodeURIComponent(username)
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            showValidationFeedback(usernameInput, feedbackDiv, 'valid', data.message);
        } else {
            showValidationFeedback(usernameInput, feedbackDiv, 'invalid', data.message);
        }
        updateSubmitButton();
    })
    .catch(error => {
        logger.error('Erreur validation username:', error);
        showValidationFeedback(usernameInput, feedbackDiv, 'invalid', 'Erreur lors de la vérification');
        updateSubmitButton();
    });
}

/**
 * Valide l'email
 */
function validateEmail() {
    const emailInput = document.getElementById('registration_form_mail');
    const feedbackDiv = getOrCreateFeedback(emailInput);
    const email = emailInput.value.trim();

    // Validation basique de l'email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showValidationFeedback(emailInput, feedbackDiv, 'invalid', 'Format d\'email invalide');
        return;
    }

    // Afficher le loading
    showValidationFeedback(emailInput, feedbackDiv, 'loading', 'Vérification de la disponibilité...');

    // Requête AJAX
    fetch('/check-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            showValidationFeedback(emailInput, feedbackDiv, 'valid', data.message);
        } else {
            showValidationFeedback(emailInput, feedbackDiv, 'invalid', data.message);
        }
        updateSubmitButton();
    })
    .catch(error => {
        logger.error('Erreur validation email:', error);
        showValidationFeedback(emailInput, feedbackDiv, 'invalid', 'Erreur lors de la vérification');
        updateSubmitButton();
    });
}

/**
 * Valide le mot de passe
 */
function validatePassword() {
    const passwordInput = document.getElementById('registration_form_plainPassword');
    const feedbackDiv = getOrCreateFeedback(passwordInput);
    const password = passwordInput.value;

    // Critères de validation du mot de passe
    const minLength = password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

    let messages = [];
    let isValid = true;

    if (!minLength) {
        messages.push('Au moins 8 caractères');
        isValid = false;
    }
    if (!hasUpperCase) {
        messages.push('Une majuscule');
        isValid = false;
    }
    if (!hasLowerCase) {
        messages.push('Une minuscule');
        isValid = false;
    }
    if (!hasNumber) {
        messages.push('Un chiffre');
        isValid = false;
    }
    if (!hasSpecialChar) {
        messages.push('Un caractère spécial');
        isValid = false;
    }

    const message = isValid ? 
        'Mot de passe fort ✓' : 
        'Requis : ' + messages.join(', ');

    showValidationFeedback(
        passwordInput, 
        feedbackDiv, 
        isValid ? 'valid' : 'invalid', 
        message
    );

    // Valider aussi la confirmation si elle existe
    validatePasswordConfirmation();
    updateSubmitButton();
}

/**
 * Valide la confirmation du mot de passe
 */
function validatePasswordConfirmation() {
    const passwordInput = document.getElementById('registration_form_plainPassword');
    const confirmPasswordInput = document.getElementById('registration_form_confirmPassword');
    
    if (!confirmPasswordInput || !confirmPasswordInput.value) return;
    
    const feedbackDiv = getOrCreateFeedback(confirmPasswordInput);
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (password === confirmPassword && password.length > 0) {
        showValidationFeedback(confirmPasswordInput, feedbackDiv, 'valid', 'Les mots de passe correspondent ✓');
    } else {
        showValidationFeedback(confirmPasswordInput, feedbackDiv, 'invalid', 'Les mots de passe ne correspondent pas');
    }

    updateSubmitButton();
}

/**
 * Crée ou récupère l'élément de feedback pour un input
 */
function getOrCreateFeedback(input) {
    let feedbackDiv = input.parentNode.querySelector('.validation-feedback');
    
    if (!feedbackDiv) {
        feedbackDiv = document.createElement('div');
        feedbackDiv.className = 'validation-feedback';
        input.parentNode.appendChild(feedbackDiv);
    }
    
    return feedbackDiv;
}

/**
 * Affiche le feedback de validation
 */
function showValidationFeedback(input, feedbackDiv, type, message) {
    // Nettoyer les classes précédentes
    input.classList.remove('is-valid', 'is-invalid', 'is-validating');
    feedbackDiv.classList.remove('valid-feedback', 'invalid-feedback', 'loading-feedback');
    
    // Appliquer les nouvelles classes
    switch (type) {
        case 'valid':
            input.classList.add('is-valid');
            feedbackDiv.classList.add('valid-feedback');
            feedbackDiv.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + message;
            break;
        case 'invalid':
            input.classList.add('is-invalid');
            feedbackDiv.classList.add('invalid-feedback');
            feedbackDiv.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> ' + message;
            break;
        case 'loading':
            input.classList.add('is-validating');
            feedbackDiv.classList.add('loading-feedback');
            feedbackDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + message;
            break;
    }
    
    feedbackDiv.style.display = 'block';
}

/**
 * Met à jour l'état du bouton de soumission
 */
function updateSubmitButton() {
    const submitButton = document.querySelector('button[type="submit"]');
    if (!submitButton) return;

    const inputs = document.querySelectorAll('#registration_form_pseudo, #registration_form_mail, #registration_form_plainPassword, #registration_form_confirmPassword');
    let allValid = true;

    inputs.forEach(input => {
        if (input && (!input.classList.contains('is-valid') || input.value.trim() === '')) {
            allValid = false;
        }
    });

    submitButton.disabled = !allValid;
    
    if (allValid) {
        submitButton.classList.remove('btn-secondary');
        submitButton.classList.add('btn-primary');
        submitButton.innerHTML = '<i class="bi bi-person-plus"></i> Créer mon compte';
    } else {
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-secondary');
        submitButton.innerHTML = '<i class="bi bi-hourglass"></i> Complétez le formulaire';
    }
}

/**
 * Gère la soumission AJAX du formulaire d'enregistrement
 */
function handleFormSubmit() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Empêcher la soumission normale

        const submitButton = document.querySelector('button[type="submit"]');
        const originalButtonContent = submitButton.innerHTML;
        
        // Désactiver le bouton et afficher le loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Création en cours...';
        
        // Préparer les données du formulaire
        const formData = new FormData(form);
        
        // Envoyer en AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès - afficher message et rediriger
                showRegistrationMessage(data.message, 'success');
                
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            } else {
                // Erreur - afficher le message d'erreur principal
                let errorMessage = data.message || 'Une erreur est survenue';
                
                // Si on a des erreurs détaillées, les afficher
                if (data.errors && data.errors.length > 0) {
                    errorMessage += '<ul class="mt-2 mb-0">';
                    data.errors.forEach(error => {
                        errorMessage += `<li>${error}</li>`;
                    });
                    errorMessage += '</ul>';
                }
                
                showRegistrationMessage(errorMessage, 'error');
                
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
                
                // Nettoyer les anciennes erreurs visuelles
                clearFieldErrors();
                
                // Afficher les erreurs spécifiques à chaque champ
                if (data.fieldErrors) {
                    displayFieldErrors(data.fieldErrors);
                }
                
                // Si c'est une erreur de champ spécifique (pour les erreurs business)
                if (data.field) {
                    const fieldInput = document.getElementById(`registration_form_${data.field}`);
                    if (fieldInput) {
                        fieldInput.classList.add('is-invalid');
                        const feedback = getOrCreateFeedback(fieldInput);
                        showValidationFeedback(fieldInput, feedback, 'invalid', data.message);
                    }
                }
            }
        })
        .catch(error => {
            logger.error('Erreur lors de l\'enregistrement:', error);
            showRegistrationMessage('Une erreur est survenue lors de la création du compte.', 'error');
            
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
        });
    });
}

/**
 * Nettoie toutes les erreurs visuelles des champs
 */
function clearFieldErrors() {
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
        const feedback = input.parentNode.querySelector('.validation-feedback');
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    });
}

/**
 * Affiche les erreurs spécifiques à chaque champ
 */
function displayFieldErrors(fieldErrors) {
    for (const [fieldName, errors] of Object.entries(fieldErrors)) {
        const fieldInput = document.getElementById(`registration_form_${fieldName}`);
        if (fieldInput && errors.length > 0) {
            fieldInput.classList.add('is-invalid');
            
            // Créer ou mettre à jour le feedback
            const feedback = getOrCreateFeedback(fieldInput);
            const errorMessage = errors.join(', ');
            showValidationFeedback(fieldInput, feedback, 'invalid', errorMessage);
        }
    }
}

/**
 * Affiche un message de résultat d'enregistrement
 */
function showRegistrationMessage(message, type) {
    // Supprimer les anciens messages
    const existingMessages = document.querySelectorAll('.registration-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Créer le nouveau message
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show registration-message`;
    messageDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer le message au début du card-body
    const cardBody = document.querySelector('.card-body');
    if (cardBody) {
        cardBody.insertBefore(messageDiv, cardBody.firstChild);
        
        // Scroll vers le message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Initialiser la gestion de la soumission AJAX après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    handleFormSubmit();
});
