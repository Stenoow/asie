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

        return $this->renderForm('user/login.html.twig', [
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
        $errors = [];
        $email = "";
        $name = "";
        $firstName = "";
        if ($request->getMethod() === "POST"){
            $user = new User();
            $form = $request->request->all();
            // set the last data if error get
            $email = $form['email'];
            $name = $form['name'];
            $firstName = $form['firstName'];
            // verif if user with this email exist in database
            $userExist = $userRepository->findOneBy(['email' => $form['email']]);
            if($userExist !== null){
                // if user exist return error
                $errors['email'] = 'L\'email est déjà utilisé !';
            }
            if(strlen($form['password']) >= 6){
                $hashedPassword = $passwordHasher->hashPassword($user, $form['password']);
                $user->setPassword($hashedPassword);
            }else{
                $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères !';
            }
            $user->setEmail($form['email']);
            // verification of name is correct
            if(strlen($form['name']) >= 3 && strlen($form['name']) <= 64){
                $user->setName($form['name']);
            }else{
                $errors['name'] = 'Le Nom doit être composer d\'au moins 3 caractères et moins de 64 caractères';
            }
            if(strlen($form['firstName']) >= 3 && strlen($form['firstName']) <= 64){
                $user->setFirstName($form['firstName']);
            }else{
                $errors['firstName'] = 'Le Prénom doit être composer d\'au moins 3 caractères et moins de 64 caractères';
            }
            $user->setCreatedAt(new \DateTime);
            $user->setUpdatedAt(new \DateTime);
            // if 0 errors register user in database
            if(count($errors) === 0){
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->renderForm('user/register.html.twig', [
            'errors' => $errors,
            'last_name' => $name,
            'last_email' => $email,
            'last_firstName' => $firstName,
        ]);
    }
}
