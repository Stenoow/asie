<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserLoginType;
use App\Form\UserRegisterType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->renderForm('user/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $formRegister = $this->createForm(UserRegisterType::class);

        $formRegister->handleRequest($request);
        if ($formRegister->isSubmitted() && $formRegister->isValid()) {
            $user = $formRegister->getData();
            // verif if user with this email exist in database
            $userExist = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if($userExist !== null)
            {
                // if user exist return error
                $errors = ['email' => 'L\'email est déjà utilisé !'];
                return $this->renderForm('user/register.html.twig', [
                    'form' => $formRegister,
                    'errors' => $errors
                ]);
            }
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $passwordText = $user->getPassword();
            $user->setPassword($hashedPassword);
            $user->setCreatedAt(new \DateTime);
            $user->setUpdatedAt(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
//            dd($passwordHasher->isPasswordValid($user, $passwordText));

//            return $this->redirectToRoute('task_success');
        }

        return $this->renderForm('user/register.html.twig', [
            'form' => $formRegister,
        ]);
    }
}
