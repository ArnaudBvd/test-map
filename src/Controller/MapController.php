<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Marker;

class MapController extends AbstractController
{
    #[Route('/map', name: 'app_map')]
    public function index(HttpClientInterface $httpClient): Response
    {
        // Nom de la ville
        $cityName = $_GET['cityName'] ?? 'Paris';

        // Construire l'URL de l'API Nominatim pour obtenir les coordonnées
        $url = 'https://nominatim.openstreetmap.org/search?city=' . urlencode($cityName) . '&format=json&addressdetails=1&country=France';


        // Faire la requête HTTP vers l'API Nominatim
        $response = $httpClient->request('GET', $url);

        // Récupérer les données JSON de la réponse
        $data = $response->toArray();

        // Vérifier si des résultats ont été trouvés
        if (!empty($data)) {
            // Prendre le premier résultat et en extraire les coordonnées
            $latitude = $data[0]['lat'];
            $longitude = $data[0]['lon'];
        } else {
            // Valeurs par défaut en cas d'échec
            $latitude = 48.866667;
            $longitude = 2.333333;
            $this->addFlash('error', 'La ville n\'a pas pu être localisée !');
        }
        
        // Créer la carte avec les coordonnées obtenues
        $map = (new Map())
            ->center(new Point($latitude, $longitude))
            ->zoom(12);

        // Charger le fichier GEOJSON local
        $filePath = __DIR__ . '/../../public/musees-de-france-base-museofile.geojson';
        $geojsonData = json_decode(file_get_contents($filePath), true);

        // Vérifier la structure des données
        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            throw new \Exception('Données GEOJSON invalides');
        }        

        // Ajouter les marqueurs sur la carte
        foreach ($geojsonData['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            // Vérifier les coordonnées
            if ($geometry['type'] !== 'Point' || empty($geometry['coordinates'])) {
                continue;
            }

            $coordinates = $geometry['coordinates'];
            $latitude = $coordinates[1];
            $longitude = $coordinates[0];

            // Extraire les informations principales
            $nom = $properties['nom_officiel'] ?? 'Musée sans nom';
            $adresse = $properties['adresse'] ?? 'Adresse inconnue';
            $ville = $properties['ville'] ?? '';
            $telephone = $properties['telephone'] ?? 'Téléphone non disponible';
            $url = $properties['url'] ?? '#';
            $domaineThematiques = $properties['domaine_thematique'] ?? [];

            // Ajouter un préfixe à l'URL si nécessaire
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            // Préparer le domaine thématique
            $themes = !empty($domaineThematiques)
                ? '<p><b>Thèmes :</b> ' . implode(', ', $domaineThematiques) . '</p>'
                : '';

            // Ajouter le marqueur
            $map->addMarker(
                new Marker(
                    position: new Point($latitude, $longitude),
                    title: $nom,
                    infoWindow: new InfoWindow(
                        headerContent: '<b>' . $nom . '</b>',
                        content: '<p>' . $adresse . ', ' . $ville . '</p>' .
                            '<p>' . $telephone . '</p>' .
                            $themes .
                            '<p><a href="' . $url . '" target="_blank">Site officiel</a></p>'
                    )
                )
            );            
        }

        // Retourner la vue avec la carte
        return $this->render('map/index.html.twig', [
            'map' => $map,        
                      
        ]);
    }
}
