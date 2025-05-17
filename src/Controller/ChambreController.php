<?php
namespace App\Controller;

use App\Document\Chambre;
use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Document\Comment;

#[Route('/chambre')]
class ChambreController extends AbstractController
{
    #[Route('/{hotelId}/new', name: 'chambre_new', methods: ['GET', 'POST'])]
    public function addChambre(string $id, Request $request, DocumentManager $dm): Response
    {
        $hotel = $dm->getRepository(Hotel::class)->find($id);
    
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }
    
        $numero = $request->request->get('numero');
    
        // Vérifiez si une chambre avec le même numéro existe déjà pour cet hôtel
        $existingChambre = $dm->getRepository(Chambre::class)->findOneBy([
            'hotel' => $hotel,
            'numero' => $numero,
        ]);
    
        if ($existingChambre) {
            $this->addFlash('error', 'Une chambre avec ce numéro existe déjà pour cet hôtel.');
            return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
        }
    
        $chambre = new Chambre();
        $chambre->setNumero($numero);
        $chambre->setType($request->request->get('type'));
        $chambre->setPrix($request->request->get('prix'));
        $chambre->setCapacite((int) $request->request->get('capacite'));
        $chambre->setHotel($hotel);
    
        $dm->persist($chambre);
        $dm->flush();
    
        return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
    }

    #[Route('/{id}/edit', name: 'chambre_edit', methods: ['GET', 'POST'])]
    public function editChambre(string $id, Request $request, DocumentManager $dm): Response
    {
        $chambre = $dm->getRepository(Chambre::class)->find($id);
    
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée.');
        }
    
        if ($request->isMethod('POST')) {
            $numero = $request->request->get('numero');
            $hotel = $chambre->getHotel();
    
            // Vérifiez si une autre chambre avec le même numéro existe pour cet hôtel
            $existingChambre = $dm->getRepository(Chambre::class)->findOneBy([
                'hotel' => $hotel,
                'numero' => $numero,
            ]);
    
            if ($existingChambre && $existingChambre->getId() !== $chambre->getId()) {
                $this->addFlash('error', 'Une chambre avec ce numéro existe déjà pour cet hôtel.');
                return $this->redirectToRoute('chambre_edit', ['id' => $chambre->getId()]);
            }
    
            $chambre->setNumero($numero);
            $chambre->setType($request->request->get('type'));
            $chambre->setPrix($request->request->get('prix'));
            $chambre->setCapacite((int) $request->request->get('capacite'));
    
            $dm->flush();
    
            return $this->redirectToRoute('hotel_show', ['id' => $hotel->getId()]);
        }
    
        return $this->render('chambre/edit.html.twig', [
            'chambre' => $chambre,
        ]);
    
    }
#[Route('/chambre/{id}/comments', name: 'chambre_comments', methods: ['GET'])]
public function comments(string $id, DocumentManager $dm): Response
{
    $chambre = $dm->getRepository(Chambre::class)->find($id);

    if (!$chambre) {
        throw $this->createNotFoundException('Chambre non trouvée.');
    }

    $comments = $dm->getRepository(Comment::class)->findBy(['chambre' => $chambre]);

    return $this->render('chambre/comments.html.twig', [
        'chambre' => $chambre,
        'comments' => $comments,
    ]);
}
    #[Route('/{id}/delete', name: 'chambre_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm): Response
    {
        $chambre = $dm->getRepository(Chambre::class)->find($id);

        if ($chambre) {
            $dm->remove($chambre);
            $dm->flush();
        }

        return $this->redirectToRoute('hotel_show', ['id' => $chambre->getHotel()->getId()]);
    }
}