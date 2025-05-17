<?php
namespace App\Controller;

use App\Document\Client;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\SmsService;

class UserController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, redirigez-le vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // Récupérer les erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }





    #[Route('/set-pin', name: 'set_pin', methods: ['GET', 'POST'])]
    public function setPin(Request $request, DocumentManager $dm): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour définir un code PIN.');
            return $this->redirectToRoute('app_login');
        }
    
        if ($request->isMethod('POST')) {
            $pinCode = $request->request->get('pinCode');
    
            // Vérifiez que le code PIN est un entier à 6 chiffres
            if (!preg_match('/^\d{6}$/', $pinCode)) {
                $this->addFlash('error', 'Le code PIN doit être un entier à 6 chiffres.');
                return $this->redirectToRoute('set_pin');
            }
    
            $user->setPinCode((int) $pinCode);
            $dm->flush();
    
            $this->addFlash('success', 'Code PIN défini avec succès.');
            return $this->redirectToRoute('home');
        }
    
        return $this->render('security/set_pin.html.twig');
    }
    #[Route('/forgot-password-pin', name: 'forgot_password_pin', methods: ['GET', 'POST'])]
    public function forgotPasswordPin(Request $request, DocumentManager $dm, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $pinCode = $request->request->get('pinCode');
            $newPassword = $request->request->get('newPassword');
    
            // Récupérer l'utilisateur par email
            $user = $dm->getRepository(Client::class)->findOneBy(['email' => $email]);
    
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('forgot_password_pin');
            }
    
            // Vérifiez le code PIN
            if ($user->getPinCode() !== (int) $pinCode) {
                $this->addFlash('error', 'Code PIN incorrect.');
                return $this->redirectToRoute('forgot_password_pin');
            }
    
            // Réinitialiser le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $dm->flush();
    
            $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/forgot_password_pin.html.twig');
    }
    #[Route('/reset-password-pin', name: 'reset_password_pin', methods: ['GET', 'POST'])]
    public function resetPasswordWithPin(Request $request, DocumentManager $dm, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $pinCode = $request->request->get('pinCode');
            $newPassword = $request->request->get('newPassword');
    
            // Récupérer l'utilisateur par email
            $user = $dm->getRepository(Client::class)->findOneBy(['email' => $email]);
    
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('reset_password_pin');
            }
    
            // Vérifiez le code PIN
            if ($user->getPinCode() !== (int) $pinCode) {
                $this->addFlash('error', 'Code PIN incorrect.');
                return $this->redirectToRoute('reset_password_pin');
            }
    
            // Réinitialiser le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $dm->flush();
    
            $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/reset_password_pin.html.twig');
    }

    #[Route('/users', name: 'user_management', methods: ['GET'])]
    public function manageUsers(DocumentManager $dm): Response
    {
        // Récupérer tous les utilisateurs (clients)
        $users = $dm->getRepository(Client::class)->findAll();

        return $this->render('client/index.html.twig', [
            'users' => $users,
        ]);
    }


    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Symfony gère automatiquement la déconnexion
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, DocumentManager $dm): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
    
            // Vérifiez que les mots de passe correspondent
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_register');
            }
    
            // Vérifiez l'unicité de l'email
            $existingEmail = $dm->getRepository(Client::class)->findOneBy(['email' => $email]);
            if ($existingEmail) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }
    
            // Vérifiez l'unicité du numéro de téléphone
            $existingTelephone = $dm->getRepository(Client::class)->findOneBy(['telephone' => $telephone]);
            if ($existingTelephone) {
                $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }
    
            // Créez un nouvel utilisateur
            $user = new Client();
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone($telephone);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
    
            // Définir le rôle par défaut
            $user->setRoles(['ROLE_USER']);
    
            // Générer l'ID auto-incrémenté
            $lastUser = $dm->createQueryBuilder(Client::class)
                ->sort('autoIncrementId', 'DESC')
                ->limit(1)
                ->getQuery()
                ->getSingleResult();
    
            $nextId = $lastUser ? $lastUser->getAutoIncrementId() + 1 : 1;
            $user->setAutoIncrementId($nextId);
    
            // Persistez l'utilisateur dans la base de données
            $dm->persist($user);
            $dm->flush();
    
            $this->addFlash('success', 'Inscription réussie. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/register.html.twig');
    }
}