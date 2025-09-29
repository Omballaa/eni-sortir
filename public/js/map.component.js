/**
 * Map Component
 * Gère l'affichage de cartes avec marqueurs pour les lieux
 */

class MapComponent {
    constructor() {
        this.maps = new Map(); // Stockage des instances de cartes
        this.init();
    }

    init() {
        devLog('🗺️ Initialisation MapComponent');
        
        // Observer les éléments carte qui pourraient être ajoutés dynamiquement
        this.setupMutationObserver();
        
        // Initialiser les cartes déjà présentes
        this.initExistingMaps();
    }

    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        // Chercher les éléments carte dans le nouveau contenu
                        const mapElements = node.querySelectorAll ? node.querySelectorAll('.lieu-map') : [];
                        mapElements.forEach(mapElement => {
                            this.initMap(mapElement);
                        });
                        
                        // Vérifier si le node lui-même est une carte
                        if (node.classList && node.classList.contains('lieu-map')) {
                            this.initMap(node);
                        }
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

    initExistingMaps() {
        const mapElements = document.querySelectorAll('.lieu-map');
        mapElements.forEach(mapElement => {
            this.initMap(mapElement);
        });
    }

    /**
     * Initialise une carte pour un élément donné
     */
    initMap(mapElement) {
        const mapId = mapElement.id;
        if (!mapId) {
            devLog('❌ Élément carte sans ID');
            return;
        }

        // Éviter la double initialisation
        if (this.maps.has(mapId)) {
            devLog('⚠️ Carte déjà initialisée:', mapId);
            return;
        }

        const latitude = parseFloat(mapElement.dataset.latitude);
        const longitude = parseFloat(mapElement.dataset.longitude);
        const lieuNom = mapElement.dataset.lieuNom || 'Lieu';
        const lieuAdresse = mapElement.dataset.lieuAdresse || '';

        if (isNaN(latitude) || isNaN(longitude)) {
            devLog('❌ Coordonnées invalides pour la carte:', { latitude, longitude });
            mapElement.innerHTML = `
                <div class="alert alert-warning text-center">
                    <i class="bi bi-geo-alt"></i>
                    <p class="mb-0">Coordonnées GPS non disponibles</p>
                    <small class="text-muted">${lieuNom}${lieuAdresse ? ' - ' + lieuAdresse : ''}</small>
                </div>
            `;
            return;
        }

        try {
            // Créer la carte Leaflet
            const map = L.map(mapId, {
                center: [latitude, longitude],
                zoom: 15,
                zoomControl: true,
                scrollWheelZoom: false, // Désactiver le zoom à la molette par défaut
                doubleClickZoom: true
            });

            // Ajouter les tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Créer un marqueur personnalisé
            const marker = L.marker([latitude, longitude], {
                title: lieuNom
            }).addTo(map);

            // Popup avec informations du lieu
            const popupContent = `
                <div class="text-center">
                    <h6 class="mb-1"><i class="bi bi-geo-alt-fill text-danger"></i> ${lieuNom}</h6>
                    ${lieuAdresse ? `<p class="mb-1 text-muted small">${lieuAdresse}</p>` : ''}
                    <small class="text-muted">
                        <i class="bi bi-pin-map"></i> ${latitude.toFixed(6)}, ${longitude.toFixed(6)}
                    </small>
                </div>
            `;
            
            marker.bindPopup(popupContent);

            // Stocker l'instance de la carte
            this.maps.set(mapId, {
                map: map,
                marker: marker,
                coordinates: { latitude, longitude }
            });

            // Activer le zoom à la molette sur clic/hover
            this.setupZoomControl(mapElement, map);

            devLog('✅ Carte initialisée:', { mapId, latitude, longitude, lieuNom });

        } catch (error) {
            devLog('❌ Erreur initialisation carte:', error);
            mapElement.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p class="mb-0">Erreur de chargement de la carte</p>
                </div>
            `;
        }
    }

    /**
     * Configure le contrôle du zoom
     */
    setupZoomControl(mapElement, map) {
        let isMapFocused = false;

        // Activer le zoom à la molette quand la carte est cliquée ou survolée
        mapElement.addEventListener('mouseenter', () => {
            map.scrollWheelZoom.enable();
            isMapFocused = true;
        });

        mapElement.addEventListener('mouseleave', () => {
            map.scrollWheelZoom.disable();
            isMapFocused = false;
        });

        mapElement.addEventListener('click', () => {
            if (!isMapFocused) {
                map.scrollWheelZoom.enable();
                isMapFocused = true;
            }
        });

        // Indicateur visuel
        const scrollHint = document.createElement('div');
        scrollHint.className = 'map-scroll-hint';
        scrollHint.innerHTML = '<small><i class="bi bi-mouse"></i> Cliquez pour zoomer</small>';
        scrollHint.style.cssText = `
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(255,255,255,0.8);
            padding: 2px 6px;
            border-radius: 3px;
            pointer-events: none;
            z-index: 1000;
            font-size: 11px;
        `;
        mapElement.style.position = 'relative';
        mapElement.appendChild(scrollHint);
    }

    /**
     * Met à jour une carte existante avec de nouvelles coordonnées
     */
    updateMap(mapId, latitude, longitude, lieuNom = '', lieuAdresse = '') {
        const mapInstance = this.maps.get(mapId);
        if (!mapInstance) {
            devLog('❌ Carte non trouvée pour mise à jour:', mapId);
            return;
        }

        const { map, marker } = mapInstance;

        // Mettre à jour la position
        const newLatLng = [latitude, longitude];
        map.setView(newLatLng, 15);
        marker.setLatLng(newLatLng);

        // Mettre à jour le popup
        const popupContent = `
            <div class="text-center">
                <h6 class="mb-1"><i class="bi bi-geo-alt-fill text-danger"></i> ${lieuNom}</h6>
                ${lieuAdresse ? `<p class="mb-1 text-muted small">${lieuAdresse}</p>` : ''}
                <small class="text-muted">
                    <i class="bi bi-pin-map"></i> ${latitude.toFixed(6)}, ${longitude.toFixed(6)}
                </small>
            </div>
        `;
        marker.bindPopup(popupContent);

        // Mettre à jour les données stockées
        mapInstance.coordinates = { latitude, longitude };

        devLog('✅ Carte mise à jour:', { mapId, latitude, longitude });
    }

    /**
     * Supprime une carte
     */
    removeMap(mapId) {
        const mapInstance = this.maps.get(mapId);
        if (mapInstance) {
            mapInstance.map.remove();
            this.maps.delete(mapId);
            devLog('✅ Carte supprimée:', mapId);
        }
    }

    /**
     * Crée un élément carte avec les données fournies
     */
    createMapElement(containerId, latitude, longitude, lieuNom = '', lieuAdresse = '', height = '300px') {
        const container = document.getElementById(containerId);
        if (!container) {
            devLog('❌ Conteneur non trouvé:', containerId);
            return null;
        }

        const mapId = `map-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        const mapElement = document.createElement('div');
        mapElement.id = mapId;
        mapElement.className = 'lieu-map';
        mapElement.style.height = height;
        mapElement.style.width = '100%';
        mapElement.dataset.latitude = latitude;
        mapElement.dataset.longitude = longitude;
        mapElement.dataset.lieuNom = lieuNom;
        mapElement.dataset.lieuAdresse = lieuAdresse;

        container.appendChild(mapElement);

        // Initialiser la carte après un court délai
        setTimeout(() => {
            this.initMap(mapElement);
        }, 100);

        return mapElement;
    }
}

// Fonction utilitaire pour créer une carte facilement
function createLieuMap(containerId, latitude, longitude, lieuNom = '', lieuAdresse = '', height = '300px') {
    if (window.mapComponent) {
        return window.mapComponent.createMapElement(containerId, latitude, longitude, lieuNom, lieuAdresse, height);
    }
    devLog('❌ MapComponent non initialisé');
    return null;
}

// Initialisation
let mapComponent;

// Vérifier si Leaflet est chargé
function initMapComponent() {
    if (typeof L !== 'undefined') {
        mapComponent = new MapComponent();
        window.mapComponent = mapComponent;
        window.createLieuMap = createLieuMap;
        devLog('✅ MapComponent initialisé avec Leaflet');
    } else {
        devLog('⚠️ Leaflet non chargé, MapComponent en attente...');
        // Réessayer dans 100ms
        setTimeout(initMapComponent, 100);
    }
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMapComponent);
} else {
    initMapComponent();
}