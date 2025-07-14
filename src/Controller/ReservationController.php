<?php
namespace App\Controller;

use App\Document\Reservation;
use App\Document\Chambre;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Document\Comment;

/**
 * Contrôleur de gestion des réservations.
 * Permet d'afficher, commenter, annuler et supprimer des réservations.
 */
class ReservationController extends AbstractController
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Affiche les réservations de l'utilisateur connecté.
     */
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

    /**
     * Affiche toutes les réservations (pour l'administration).
     */
    #[Route('/reservations', name: 'reservation_index', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        $reservations = $dm->getRepository(Reservation::class)->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Affiche toutes les réservations pour l'administration avec vérification des clients.
     */
    #[Route('/admin/reservations', name: 'admin_reservations', methods: ['GET'])]
    public function adminReservations(DocumentManager $dm): Response
    {
        $reservations = $dm->getRepository(Reservation::class)->findAll();

        foreach ($reservations as $reservation) {
            if (!$reservation->getClient()) {
                $this->addFlash('warning', 'Une réservation sans client a été trouvée.');
            }
        }

        return $this->render('reservation/admin_index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Affiche toutes les chambres et leurs commentaires pour l'administration.
     */
    #[Route('/admin/chambres', name: 'admin_chambres', methods: ['GET'])]
    public function adminChambres(DocumentManager $dm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        // Récupérer toutes les chambres
        $chambres = $dm->getRepository(Chambre::class)->findAll();

        // Récupérer les commentaires associés à chaque chambre
        $comments = [];
        foreach ($chambres as $chambre) {
            $comments[$chambre->getId()] = $dm->getRepository(Comment::class)->findBy(['chambre' => $chambre]);
        }

        return $this->render('admin/chambres.html.twig', [
            'chambres' => $chambres,
            'comments' => $comments,
        ]);
    }

    /**
     * Permet à un utilisateur de commenter une réservation.
     */
    #[Route('/reservation/{id}/comment', name: 'reservation_comment', methods: ['GET', 'POST'])]
    public function comment(string $id, Request $request, DocumentManager $dm): Response
    {
        // Vérifie que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter cette réservation.');
            return $this->redirectToRoute('app_login');
        }

        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        // Vérifie que l'utilisateur connecté est bien le client associé à la réservation
        if ($reservation->getClient() !== $user) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à commenter cette réservation.');
            return $this->redirectToRoute('reservation_index');
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');

            if (!$content) {
                $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
                return $this->redirectToRoute('reservation_comment', ['id' => $id]);
            }

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTime());
            $comment->setChambre($reservation->getChambre());
            $comment->setAuthor($user);

            $dm->persist($comment);
            $dm->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
            return $this->redirectToRoute('reservation_index');
        }

        // Affiche le formulaire de commentaire
        return $this->render('reservation/comment.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Supprime une réservation (administration).
     */
    #[Route('/reservations/{id}/delete', name: 'reservation_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        $dm->remove($reservation);
        $dm->flush();

        $this->addFlash('success', 'Réservation supprimée avec succès.');
        return $this->redirectToRoute('reservation_index');
    }

    /**
     * Permet à un utilisateur d'annuler sa propre réservation avec vérification CSRF.
     */
    #[Route('/reservations/{id}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancelReservation(string $id, Request $request, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('user_reservations');
        }

        // Vérifie que l'utilisateur connecté est bien le propriétaire de la réservation
        if ($reservation->getClient() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
            return $this->redirectToRoute('user_reservations');
        }

        // Vérifie le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $reservation->getId(), $submittedToken))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('user_reservations');
        }

        $dm->remove($reservation);
        $dm->flush();

        $this->addFlash('success', 'Réservation annulée avec succès.');
        return $this->redirectToRoute('user_reservations');
    }
}