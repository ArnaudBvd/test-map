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

        // Chercher le fichier JSON
        $fichier = $this->httpClient->request('GET', 'https://data.culture.gouv.fr/api/explore/v2.1/catalog/datasets/liste-et-localisation-des-musees-de-france/exports/json');
        $points = json_decode($fichier->getContent(), true);

        // dd($points);

        // Ajouter les marqueurs sur la carte
        foreach ($points as $record) {
            $url = $record['url'];

            // Vérifier si l'URL commence par "http://" ou "https://"
            if (!str_starts_with($url, "http://") && !str_starts_with($url, "https://")) {
                $url = 'https://' . $url; // Ajoute le préfixe si nécessaire
            }

            $map->addMarker(
                new Marker(
                    position: new Point($record['latitude'], $record['longitude']),
                    title: $record['nom_officiel_du_musee'],
                    infoWindow: new InfoWindow(
                        headerContent: '<b>' . $record['nom_officiel_du_musee'] . '</b>',
                        content: 
                            '<p>' . $record['adresse'] . '</p>' .
                            '<p>' . $record['telephone'] . '</p>' .
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
