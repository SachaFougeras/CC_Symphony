<?php
namespace App\Controller;

use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Chambre;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Document\Reservation;

#[Route('/hotel')]
class HotelController extends AbstractController
{
#[Route('/accueil', name: 'home', methods: ['GET'])]
public function home(Request $request, DocumentManager $dm): Response
{
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $repository = $dm->getRepository(Hotel::class);
    $hotels = $repository->findBy([], null, $limit, $offset);

    // Compter le nombre total d'hôtels
    $totalHotels = $repository->createQueryBuilder()
        ->count()
        ->getQuery()
        ->execute();

    // Récupérer tous les noms d'hôtels pour le datalist
    $allHotels = $repository->findAll();
    $noms = array_map(fn($h) => $h->getNom(), $allHotels);

    return $this->render('home/index.html.twig', [
        'hotels' => $hotels,
        'currentPage' => $page,
        'totalPages' => $totalHotels,
        'noms' => $noms, 
    ]);
}
    // Calculer le nombre total de pages
 
    #[Route('/hotel/{id}/reserve', name: 'hotel_reserve', methods: ['POST'])]
    public function reserveRoom(string $id, Request $request, DocumentManager $dm): Response
    {
        $hotel = $dm->getRepository(Hotel::class)->find($id);
    
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }
    
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'Vous devez être connecté pour réserver une chambre.');
            return $this->redirectToRoute('app_login');
        }
    
        $chambreId = $request->request->get('chambre_id');
        $chambre = $dm->getRepository(Chambre::class)->find($chambreId);
    
        if (!$chambre) {
            $this->addFlash('error', 'Chambre non trouvée.');
            return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
        }
    
        $dateDebut = new \DateTime($request->request->get('date_debut'));
        $dateFin = new \DateTime($request->request->get('date_fin'));
    
        // Vérifiez si la chambre est déjà réservée pour la période sélectionnée
        $existingReservations = $dm->getRepository(Reservation::class)->findBy([
            'chambre' => $chambre,
        ]);
    
        foreach ($existingReservations as $reservation) {
            if (
                ($dateDebut >= $reservation->getDateDebut() && $dateDebut <= $reservation->getDateFin()) ||
                ($dateFin >= $reservation->getDateDebut() && $dateFin <= $reservation->getDateFin()) ||
                ($dateDebut <= $reservation->getDateDebut() && $dateFin >= $reservation->getDateFin())
            ) {
                $this->addFlash('error', 'Cette chambre est déjà réservée pour la période sélectionnée.');
                return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
            }
        }
    
        // Si la chambre est disponible, créer une réservation
        $reservation = new Reservation();
        $reservation->setChambre($chambre);
        $reservation->setClient($this->getUser());
        $reservation->setDateDebut($dateDebut);
        $reservation->setDateFin($dateFin);
    
        $dm->persist($reservation);
        $dm->flush();
    
        $this->addFlash('success', 'Réservation effectuée avec succès.');
        return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
    }
#[Route('/search', name: 'hotel_search', methods: ['GET'])]
public function search(Request $request, DocumentManager $dm): Response
{
    $query = $request->query->get('q', '');

    // Rechercher les hôtels correspondant au nom ou à la ville
    $qb = $dm->getRepository(Hotel::class)->createQueryBuilder();
    $qb->addOr($qb->expr()->field('nom')->equals(new \MongoDB\BSON\Regex($query, 'i')))
       ->addOr($qb->expr()->field('ville')->equals(new \MongoDB\BSON\Regex($query, 'i')));

    $hotels = $qb->getQuery()->execute();

    // Récupérer tous les noms d'hôtels pour le datalist
    $allHotels = $dm->getRepository(Hotel::class)->findAll();
    $noms = array_map(fn($h) => $h->getNom(), $allHotels);

    return $this->render('home/index.html.twig', [
        'hotels' => $hotels,
        'query' => $query,
        'noms' => $noms,
        'currentPage' => 1,    // <-- Ajoute ceci
        'totalPages' => 1,     // <-- Et ceci si utilisé dans le template
]);
}
#[Route('/formulaire', name: 'hotel_form', methods: ['GET'])]
public function form(DocumentManager $dm): Response
{
    $hotels = $dm->getRepository(Hotel::class)->findAll();
    $noms = array_map(fn($h) => $h->getNom(), $hotels);

    return $this->render('hotel/form.html.twig', [
        'noms' => $noms,
    ]);
}
#[Route('/reservations', name: 'user_reservations', methods: ['GET'])]
public function userReservations(DocumentManager $dm): Response
{
    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour accéder à vos réservations.');
        return $this->redirectToRoute('app_login');
    }

    $reservations = $dm->getRepository(Reservation::class)->findBy(['client' => $user]);

    return $this->render('reservation/index.html.twig', [
        'reservations' => $reservations,
    ]);
}
    #[Route('/new', name: 'hotel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DocumentManager $dm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
    
        if ($request->isMethod('POST')) {
            $categorie = (int) $request->request->get('categorie');
            if ($categorie < 1 || $categorie > 5) {
                $this->addFlash('error', 'La catégorie doit être comprise entre 1 et 5.');
                return $this->redirectToRoute('hotel_new');
            }
    
            $hotel = new Hotel();
            $hotel->setNom($request->request->get('nom'));
            $hotel->setAdresse($request->request->get('adresse'));
            $hotel->setVille($request->request->get('ville'));
            $hotel->setTelephone($request->request->get('telephone'));
            $hotel->setCategorie($categorie);
    
            $dm->persist($hotel);
            $dm->flush();
    
            return $this->redirectToRoute('home');
        }
    
        return $this->render('hotel/hotel_new.html.twig');
    }

    #[Route('/{id}', name: 'hotel_show', methods: ['GET', 'POST'])]
    public function show(string $id, Request $request, DocumentManager $dm): Response
    {
        $hotel = $dm->getRepository(Hotel::class)->find($id);
    
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }
    
        // Si l'utilisateur soumet le formulaire pour ajouter une chambre
        if ($request->isMethod('POST') && $this->isGranted('ROLE_ADMIN')) {
            $chambre = new Chambre();
            $chambre->setNumero($request->request->get('numero'));
            $chambre->setType($request->request->get('type'));
            $chambre->setPrix($request->request->get('prix'));
            $chambre->setHotel($hotel);
    
            $dm->persist($chambre);
            $dm->flush();
    
            return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
        }
    
        return $this->render('hotel/hotel_show.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/edit', name: 'hotel_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, DocumentManager $dm): Response
    {
        
        $hotel = $dm->getRepository(Hotel::class)->find($id);
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }

        if ($request->isMethod('POST')) {
            $categorie = (int) $request->request->get('categorie');
            if ($categorie < 1 || $categorie > 5) {
                $this->addFlash('error', 'La catégorie doit être comprise entre 1 et 5.');
                return $this->redirectToRoute('hotel_new');
            }
            $hotel->setNom($request->request->get('nom'));
            $hotel->setAdresse($request->request->get('adresse'));
            $hotel->setVille($request->request->get('ville'));
            $hotel->setTelephone($request->request->get('telephone'));
            $hotel->setCategorie($request->request->get('categorie'));

            $dm->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('hotel/hotel_edit.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/delete', name: 'hotel_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $hotel = $dm->getRepository(Hotel::class)->find($id);

        if ($hotel) {
            $dm->remove($hotel);
            $dm->flush();
        }

        return $this->redirectToRoute('home');
    }
    
}