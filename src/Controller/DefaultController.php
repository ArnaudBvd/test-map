<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

// class DefaultController extends AbstractController
// {
//     public function __construct(
//         private readonly HttpClientInterface $httpClient,
//     ) {}

//     #[Route('/', name: 'app_default')]
//     public function index(): Response
//     {
//         $map = (new Map())
//             ->center(new Point(45.750000, 4.850000))
//             ->zoom(12);

//         // Chercher le fichier JSON
//         $fichier = $this->httpClient->request('GET', 'https://data.culture.gouv.fr/api/explore/v2.1/catalog/datasets/liste-et-localisation-des-musees-de-france/exports/json');
//         $points = json_decode($fichier->getContent(), true);

//         // dd($points);

//         // Ajouter les marqueurs sur la carte
//         foreach ($points as $record) {
//             $url = $record['url'];

//             // Vérifier si l'URL commence par "http://" ou "https://"
//             if (!str_starts_with($url, "http://") && !str_starts_with($url, "https://")) {
//                 $url = 'https://' . $url; // Ajoute le préfixe si nécessaire
//             }

//             $map->addMarker(
//                 new Marker(
//                     position: new Point($record['latitude'], $record['longitude']),
//                     title: $record['nom_officiel_du_musee'],
//                     infoWindow: new InfoWindow(
//                         headerContent: '<b>' . $record['nom_officiel_du_musee'] . '</b>',
//                         content: 
//                             '<p>' . $record['adresse'] . '</p>' .
//                             '<p>' . $record['telephone'] . '</p>' .
//                             '<p><a href="' . $url . '" target="_blank">Site officiel</a></p>'

//                     )
//                 )
//             );
//         }

//         return $this->render('default/index.html.twig', [
//             'map' => $map,
//         ]);
//     }
// }

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    #[Route('/', name: 'app_default')]
    public function index(): Response
    {
        $map = (new Map())
            ->center(new Point(45.750000, 4.850000))
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
                        content:
                            '<p>' . $adresse . ', ' . $ville . '</p>' .
                            '<p>' . $telephone . '</p>' .
                            $themes .
                            '<p><a href="' . $url . '" target="_blank">Site officiel</a></p>'
                    )
                )
            );
        }

        return $this->render('default/index.html.twig', [
            'map' => $map,
        ]);
    }
}


