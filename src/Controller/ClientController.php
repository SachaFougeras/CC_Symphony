<?php

namespace App\Controller;

use App\Document\Client;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Document\Reservation;

class ClientController extends AbstractController
{
    

    #[Route('/clients', name: 'client_index', methods: ['GET'])]
public function index(Request $request, DocumentManager $dm): Response
{
    $search = $request->query->get('search', ''); // Récupérer le terme de recherche
    $repository = $dm->getRepository(Client::class);

    if ($search) {
        // Rechercher par nom ou email
        $clients = $repository->createQueryBuilder()
            ->field('nom')->equals(new \MongoDB\BSON\Regex($search, 'i'))
            ->field('email')->equals(new \MongoDB\BSON\Regex($search, 'i'))
            ->getQuery()
            ->execute();
    } else {
        // Récupérer tous les clients
        $clients = $repository->findAll();
    }

    return $this->render('client/index.html.twig', [
        'clients' => $clients,
    ]);
}
    #[Route('/clients/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DocumentManager $dm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
    
            // Vérifiez si un utilisateur avec le même email existe déjà
            $existingEmail = $dm->getRepository(Client::class)->findOneBy(['email' => $email]);
            if ($existingEmail) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->redirectToRoute('client_new');
            }
    
            // Vérifiez si un utilisateur avec le même numéro de téléphone existe déjà
            $existingTelephone = $dm->getRepository(Client::class)->findOneBy(['telephone' => $telephone]);
            if ($existingTelephone) {
                $this->addFlash('error', 'Un utilisateur avec ce numéro de téléphone existe déjà.');
                return $this->redirectToRoute('client_new');
            }
    
            $client = new Client();
            $client->setNom($request->request->get('nom'));
            $client->setEmail($email);
            $client->setTelephone($telephone);
    
            // Récupérer les rôles depuis le formulaire
            $roles = $request->request->all('roles');
            $client->setRoles((array) $roles);
    
            $dm->persist($client);
            $dm->flush();
    
            $this->addFlash('success', 'Utilisateur ajouté avec succès.');
            return $this->redirectToRoute('client_index');
        }
    
        return $this->render('client/new.html.twig');
    }

//Récupère un client en fonction de son id
    #[Route('/clients/{id}', name: 'client_show', methods: ['GET'])]
    public function show(string $id, DocumentManager $dm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $client = $dm->getRepository(Client::class)->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }
        $reservations = $dm->getRepository(Reservation::class)->findBy(['client' => $client]);
        return $this->render('client/show.html.twig', [
            'client' => $client,
            'reservations' => $reservations,
        ]);
    }


    #[Route('/client/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, DocumentManager $dm): Response
    {
        if ($request->isMethod('POST')) {
            $autoIncrementId = (int) $request->request->get('autoIncrementId');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $roles = $request->request->all('roles'); // Récupère les rôles sous forme de tableau
    
            // Vérifier si l'ID auto-incrémenté est unique
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
    
            $this->addFlash('success', 'Client modifié avec succès.');
            return $this->redirectToRoute('client_index');
        }
    
        return $this->render('client/edit.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/clients/{id}/delete', name: 'client_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm): Response
    {
        $client = $dm->getRepository(Client::class)->find($id);
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $client = $dm->getRepository(Client::class)->find($id);
        
        if ($client) {
            $reservations = $dm->getRepository(Reservation::class)->findBy(['client' => $client]);
            foreach ($reservations as $reservation) {
                $dm->remove($reservation);
            }
            $dm->remove($client);
            $dm->flush();
        }

        return $this->redirectToRoute('client_index');
    }
}