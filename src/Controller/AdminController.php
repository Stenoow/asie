<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', []);
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
    public function userManagementDelete(Request $request, UserRepository $userRepository, ManagerRegistry $doctrine): Response
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
    public function userManagementUpdate(Request $request, UserRepository $userRepository, ManagerRegistry $doctrine): Response
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
