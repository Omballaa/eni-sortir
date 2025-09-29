<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeocodingService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $nominatimUrl = 'https://nominatim.openstreetmap.org/search';

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Récupère les coordonnées GPS à partir d'une adresse
     * 
     * @param string $address Adresse complète (rue + ville + code postal)
     * @param string $city Nom de la ville (optionnel pour affiner)
     * @param string $postalCode Code postal (optionnel pour affiner)
     * @return array|null ['latitude' => float, 'longitude' => float] ou null si non trouvé
     */
    public function geocodeAddress(string $address, ?string $city = null, ?string $postalCode = null): ?array
    {
        try {
            // Construire l'adresse complète
            $fullAddress = $address;
            if ($city) {
                $fullAddress .= ', ' . $city;
            }
            if ($postalCode) {
                $fullAddress .= ', ' . $postalCode;
            }
            $fullAddress .= ', France'; // Ajouter le pays pour affiner

            $this->logger->info('Géocodage de l\'adresse: ' . $fullAddress);

            // Appel à l'API Nominatim (OpenStreetMap)
            $response = $this->httpClient->request('GET', $this->nominatimUrl, [
                'query' => [
                    'q' => $fullAddress,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'fr', // Limiter à la France
                    'addressdetails' => 1
                ],
                'headers' => [
                    'User-Agent' => 'SortirApp/1.0 (contact@sortir.local)' // Requis par Nominatim
                ],
                'timeout' => 10
            ]);

            $data = $response->toArray();

            if (empty($data)) {
                $this->logger->warning('Aucun résultat trouvé pour l\'adresse: ' . $fullAddress);
                return null;
            }

            $result = $data[0];
            
            if (!isset($result['lat']) || !isset($result['lon'])) {
                $this->logger->warning('Coordonnées manquantes dans la réponse de géocodage');
                return null;
            }

            $coordinates = [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'display_name' => $result['display_name'] ?? '',
                'confidence' => $this->calculateConfidence($result, $city, $postalCode)
            ];

            $this->logger->info('Géocodage réussi', $coordinates);
            
            return $coordinates;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du géocodage: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcule un score de confiance basé sur la correspondance des données
     */
    private function calculateConfidence(array $result, ?string $expectedCity, ?string $expectedPostalCode): float
    {
        $confidence = 0.5; // Base de confiance

        $address = $result['address'] ?? [];

        // Vérifier la correspondance de la ville
        if ($expectedCity && isset($address['city'])) {
            $cityMatch = stripos($address['city'], $expectedCity) !== false || 
                        stripos($expectedCity, $address['city']) !== false;
            if ($cityMatch) {
                $confidence += 0.3;
            }
        }

        // Vérifier la correspondance du code postal
        if ($expectedPostalCode && isset($address['postcode'])) {
            if ($address['postcode'] === $expectedPostalCode) {
                $confidence += 0.2;
            }
        }

        return min(1.0, $confidence);
    }

    /**
     * Géocode une adresse simple (rue + ville)
     */
    public function geocodeSimpleAddress(string $street, string $city, ?string $postalCode = null): ?array
    {
        $address = $street;
        return $this->geocodeAddress($address, $city, $postalCode);
    }
}