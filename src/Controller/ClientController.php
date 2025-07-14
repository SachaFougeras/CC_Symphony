<?php

namespace App\Controller;

use App\Document\Client;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Document\Reservation;

/**
 * Contrôleur de gestion des clients.
 * Permet d'ajouter, modifier, afficher et supprimer des clients.
 */
class ClientController extends AbstractController
{
    /**
     * Affiche la liste des clients, avec possibilité de recherche.
     *
     * @param Request $request La requête HTTP
     * @param DocumentManager $dm Le gestionnaire de documents MongoDB
     * @return Response
     */
    #[Route('/clients', name: 'client_index', methods: ['GET'])]
    public function index(Request $request, DocumentManager $dm): Response
    {
        try {
            $search = $request->query->get('search', ''); // Récupérer le terme de recherche
            $repository = $dm->getRepository(Client::class);

            if ($search) {
                // Recherche par nom ou email (insensible à la casse)
                $clients = $repository->createQueryBuilder()
                    ->field('nom')->equals(new \MongoDB\BSON\Regex($search, 'i'))
                    ->field('email')->equals(new \MongoDB\BSON\Regex($search, 'i'))
                    ->getQuery()
                    ->execute();
            } else {
                // Récupérer tous les clients
                $clients = $repository->findAll();
            }

            // Affiche la liste des clients
            return $this->render('client/client_all.html.twig', [
                'clients' => $clients,
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('home');
        }
    }

    /**
     * Affiche le détail d'un client et ses réservations.
     *
     * @param string $id L'identifiant du client
     * @param DocumentManager $dm Le gestionnaire de documents MongoDB
     * @return Response
     */
    #[Route('/clients/{id}', name: 'client_show', methods: ['GET'])]
    public function show(string $id, DocumentManager $dm): Response
    {
        try {
            // Vérifie que l'utilisateur a le droit d'accéder à cette page
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
            }
            // Recherche du client par son id
            $client = $dm->getRepository(Client::class)->find($id);

            if (!$client) {
                // Si le client n'existe pas, on lève une exception 404
                throw $this->createNotFoundException('Client non trouvé');
            }
            // Recherche des réservations liées à ce client
            $reservations = $dm->getRepository(Reservation::class)->findBy(['client' => $client]);
            // Affiche la page de détail du client
            return $this->render('client/client_show.html.twig', [
                'client' => $client,
                'reservations' => $reservations,
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('client_index');
        }
    }

    /**
     * Modifie un client existant.
     * Vérifie l'unicité de l'ID auto-incrémenté.
     *
     * @param Request $request La requête HTTP
     * @param Client $client Le client à modifier (injecté automatiquement)
     * @param DocumentManager $dm Le gestionnaire de documents MongoDB
     * @return Response
     */
    #[Route('/client/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, DocumentManager $dm): Response
    {
        try {
            if ($request->isMethod('POST')) {
                $autoIncrementId = (int) $request->request->get('autoIncrementId');
                $email = $request->request->get('email');
                $telephone = $request->request->get('telephone');
                $roles = $request->request->all('roles'); // Récupère les rôles sous forme de tableau

                // Vérifier si l'ID auto-incrémenté est unique pour un autre client
                $existingClient = $dm->getRepository(Client::class)->findOneBy(['autoIncrementId' => $autoIncrementId]);
                if ($existingClient && $existingClient->getId() !== $client->getId()) {
                    $this->addFlash('error', 'Cet ID est déjà utilisé par un autre client.');
                    return $this->redirectToRoute('client_edit', ['id' => $client->getId()]);
                }

                // Mettre à jour les informations du client
                $client->setAutoIncrementId($autoIncrementId);
                $client->setEmail($email);
                $client->setTelephone($telephone);
                $client->setRoles((array) $roles); // S'assurer que $roles est un tableau

                $dm->flush();

                // Message de succès et redirection
                $this->addFlash('success', 'Client modifié avec succès.');
                return $this->redirectToRoute('client_index');
            }

            // Affiche le formulaire d'édition
            return $this->render('client/client_edit.html.twig', [
                'client' => $client,
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('client_index');
        }
    }

    /**
     * Supprime un client et ses réservations associées.
     *
     * @param string $id L'identifiant du client à supprimer
     * @param DocumentManager $dm Le gestionnaire de documents MongoDB
     * @return Response
     */
    #[Route('/clients/{id}/delete', name: 'client_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm): Response
    {
        try {
            // Vérifie que l'utilisateur a le droit d'accéder à cette page
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
            }
            // Recherche du client à supprimer
            $client = $dm->getRepository(Client::class)->find($id);

            if ($client) {
                // Supprime toutes les réservations liées au client
                $reservations = $dm->getRepository(Reservation::class)->findBy(['client' => $client]);
                foreach ($reservations as $reservation) {
                    $dm->remove($reservation);
                }
                // Supprime le client
                $dm->remove($client);
                $dm->flush();
            }

            // Redirection après suppression
            return $this->redirectToRoute('client_index');
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('client_index');
        }
    }
}