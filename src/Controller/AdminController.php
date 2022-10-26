<?php

namespace App\Controller;

use App\Repository\UserRepository;
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
    #[Route('/admin/users', name: 'app_admin_users')]
    public function userManagement(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->request->get('search');
        $selectedUser = $request->get('user');
        if($selectedUser !== null){
            $user = $userRepository->find($selectedUser);
            return $this->render('admin/userManagementById.html.twig', [
                'user' => $user
            ]);
        }
        if($request->getMethod() === "POST" && $search !== null){

            $users = $userRepository->findByName($search);
        }
        else{
            $users = $userRepository->findAll();
        }

        return $this->render('admin/userManagement.html.twig', [
            'users' => $users
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users/delete', name: 'app_admin_delete_user')]
    public function userManagementDelete(Request $request, UserRepository $userRepository): Response
    {
        $userId = $request->get('user');
        if($userId !== null){
            $user = $userRepository->find($userId);
            return $this->render('admin/userManagementById.html.twig', [
                'user' => $user
            ]);
        }
        return $this->render('admin/userManagement.html.twig', [
            'users' => $userId
        ]);
    }
}
