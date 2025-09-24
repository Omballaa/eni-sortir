/**
 * Utilitaires JavaScript pour l'application ENI Sortir
 */

/**
 * Affiche un toast de notification
 * @param {string} message - Message à afficher
 * @param {string} type - Type de toast: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Durée d'affichage en ms (défaut: 5000)
 */
function showToast(message, type = 'info', duration = 5000) {
    // Créer le conteneur de toasts s'il n'existe pas
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                    type === 'error' ? 'fa-exclamation-circle' : 
                    type === 'warning' ? 'fa-exclamation-triangle' : 
                    'fa-info-circle';
    
    const bgClass = type === 'success' ? 'text-bg-success' : 
                  type === 'error' ? 'text-bg-danger' : 
                  type === 'warning' ? 'text-bg-warning' : 
                  'text-bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas ${iconClass} me-2"></i>
                <strong class="me-auto">
                    ${type === 'success' ? 'Succès' : 
                      type === 'error' ? 'Erreur' : 
                      type === 'warning' ? 'Attention' : 'Information'}
                </strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    
    // Supprimer l'élément du DOM après fermeture
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
    
    toast.show();
}

/**
 * Effectue un refresh AJAX d'une partie de la page
 * @param {string} url - URL à charger
 * @param {string} targetSelector - Sélecteur de l'élément à remplacer
 * @param {Function} callback - Fonction de callback optionnelle
 */
function refreshAjax(url, targetSelector, callback = null) {
    const targetElement = document.querySelector(targetSelector);
    
    if (!targetElement) {
        console.error('Élément cible non trouvé:', targetSelector);
        showToast('Erreur de refresh AJAX', 'error');
        return;
    }
    
    // Afficher un spinner de chargement
    const originalContent = targetElement.innerHTML;
    targetElement.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2 text-muted">Actualisation...</p>
        </div>
    `;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.text();
    })
    .then(html => {
        targetElement.innerHTML = html;
        
        // Exécuter le callback si fourni
        if (callback && typeof callback === 'function') {
            callback();
        }
    })
    .catch(error => {
        console.error('Erreur lors du refresh AJAX:', error);
        targetElement.innerHTML = originalContent;
        showToast('Erreur lors de l\'actualisation', 'error');
    });
}

/**
 * Affiche une modale avec du contenu AJAX
 * @param {string} url - URL à charger dans la modale
 * @param {string} modalId - ID de la modale (défaut: 'universalModal')
 * @param {string} size - Taille de la modale: 'sm', 'lg', 'xl' (défaut: 'lg')
 */
function showModal(url, modalId = 'universalModal', size = 'lg') {
    console.log('showModal appelé avec URL:', url, 'modalId:', modalId, 'size:', size);
    
    // Créer ou récupérer la modale universelle
    let modal = document.getElementById(modalId);
    if (!modal) {
        console.log('Création d\'une nouvelle modale avec ID:', modalId);
        modal = createModal(modalId, size);
    } else {
        console.log('Réutilisation de la modale existante:', modalId);
    }
    
    const modalContent = modal.querySelector('.modal-content');
    
    // Afficher un spinner de chargement
    modalContent.innerHTML = `
        <div class="modal-body text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3 text-muted">Chargement...</p>
        </div>
    `;
    
    // Ouvrir la modale
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    console.log('Modale affichée, début du chargement AJAX');
    
    // Charger le contenu via AJAX
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Réponse AJAX reçue, status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.text();
    })
    .then(html => {
        console.log('Contenu HTML reçu, longueur:', html.length);
        modalContent.innerHTML = html;
        
        // Initialiser les formulaires AJAX dans la modale
        initModalForms(modal);
        
        // Émettre un événement personnalisé pour notifier que le contenu est chargé
        // Petit délai pour s'assurer que le DOM est complètement mis à jour
        setTimeout(() => {
            const event = new CustomEvent('modalContentLoaded', {
                detail: {
                    modal: modal,
                    modalId: modal.id,
                    content: html
                }
            });
            document.dispatchEvent(event);
            console.log('🎯 Événement modalContentLoaded émis pour:', modal.id);
        }, 10);
        
        console.log('Contenu de la modale chargé avec succès');
    })
    .catch(error => {
        console.error('Erreur lors du chargement de la modale:', error);
        modalContent.innerHTML = `
            <div class="modal-body text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-danger"></i>
                <h4 class="text-danger mt-3">Erreur</h4>
                <p class="text-muted">Impossible de charger le contenu.</p>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        `;
    });
}

/**
 * Crée une modale universelle
 * @param {string} modalId - ID de la modale
 * @param {string} size - Taille de la modale
 * @returns {HTMLElement} - Élément de la modale créée
 */
function createModal(modalId, size = 'lg') {
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `
        <div class="modal-dialog modal-${size}">
            <div class="modal-content">
                <!-- Le contenu sera chargé ici -->
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
}

/**
 * Initialise les formulaires AJAX dans une modale
 * @param {HTMLElement} modal - Élément de la modale
 */
function initModalForms(modal) {
    const forms = modal.querySelectorAll('form[data-ajax-form]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitModalForm(form);
        });
    });
}

/**
 * Soumet un formulaire AJAX dans une modale
 * @param {HTMLFormElement} form - Formulaire à soumettre
 */
function submitModalForm(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    
    // Désactiver le bouton et afficher un spinner
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
    }
    
    // Préparer les données du formulaire
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Succès : fermer la modale et afficher le message
            const modalElement = form.closest('.modal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
            
            // Afficher une notification de succès
            showToast(data.message, 'success');
            
            // Rediriger si nécessaire
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else if (data.refresh) {
                // Refresh d'une partie de la page
                setTimeout(() => {
                    refreshAjax(data.refresh.url, data.refresh.target);
                }, 500);
            } else {
                // Recharger la page pour voir les changements
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            // Erreur : afficher les erreurs dans la modale
            if (data.html) {
                const modalContent = form.closest('.modal-content');
                modalContent.innerHTML = data.html;
                initModalForms(form.closest('.modal'));
            } else if (data.message) {
                showToast(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Erreur lors de la soumission:', error);
        showToast('Une erreur est survenue lors de l\'enregistrement.', 'error');
    })
    .finally(() => {
        // Réactiver le bouton
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// Initialisation automatique au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour les liens qui ouvrent des modales
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-modal-url]');
        if (!link) return;
        
        e.preventDefault();
        
        const url = link.getAttribute('data-modal-url');
        const modalId = link.getAttribute('data-modal-id') || 'universalModal';
        const modalSize = link.getAttribute('data-modal-size') || 'lg';
        
        showModal(url, modalId, modalSize);
    });
    
    // Gestionnaire pour les liens de refresh AJAX
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-refresh-url]');
        if (!link) return;
        
        e.preventDefault();
        
        const url = link.getAttribute('data-refresh-url');
        const target = link.getAttribute('data-refresh-target');
        
        if (target) {
            refreshAjax(url, target);
        }
    });
});