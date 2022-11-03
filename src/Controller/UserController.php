<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher): Response
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


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users/{page}', name: 'app_admin_users', requirements: ['page' => '\d+'])]
    public function userManagement(Request $request, UserRepository $userRepository, int $page = 1): Response
    {
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $search = $request->request->get('search');
        $selectedUser = $request->get('user');
        if($selectedUser !== null){
            $user = $userRepository->find($selectedUser);
            return $this->render('admin/users/userManagementById.html.twig', [
                'user' => $user,
                'is_admin' => $userRepository->userHasRole($selectedUser, 'ROLE_ADMIN')
            ]);
        }

        if($request->getMethod() === "POST" && $search !== null){
            $users = $userRepository->findByName($search, $limit, $offset);
            $countUsers = $userRepository->findByNameTotal($search);
        }
        else{
            $users = $userRepository->findBy([], [], $limit, $offset);
            $countUsers = $userRepository->findTotal();
        }

        return $this->render('admin/users/userManagement.html.twig', [
            'users' => $users,
            'actual_page' => $page,
            'pages' => ceil($countUsers / $limit)
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users/delete', name: 'app_admin_delete_user')]
    public function userManagementDelete(
        Request $request,
        UserRepository $userRepository,
        ManagerRegistry $doctrine): Response
    {
        $user = $userRepository->find($request->get('user'));
        $entityManager = $doctrine->getManager();
        if($user !== null){
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_users', []);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users/update', name: 'app_admin_update_user')]
    public function userManagementUpdate(
        Request $request,
        UserRepository $userRepository,
        ManagerRegistry $doctrine): Response
    {
        $userForm = $request->request->all();
        $entityManager = $doctrine->getManager();
        if($userForm !== null && count($userForm) > 0){
            $user = $userRepository->find($userForm['userId']);
            if(strlen($userForm['name']) > 0 && strlen($userForm['firstName']) > 0){
                $user->setName($userForm['name']);
                $user->setFirstName($userForm['firstName']);
            }

            if(strlen($userForm['email']) > 0){
                $user->setEmail($userForm['email']);
            }
            if(array_key_exists('admin', $userForm) && $userForm['admin'] == "on"){
                $user->setRoles(['ROLE_ADMIN']);
            }else{
                $user->setRoles([]);
            }
            $user->setUpdatedAt(new \DateTime);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_admin_users', []);
    }
}
