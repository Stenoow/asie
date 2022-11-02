<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/orderSuccess/{id}/{token}', name: 'app_order_success')]
    public function success_paiement(
        int $id,
        string $token,
        OrderRepository $orderRepository,
        ManagerRegistry $doctrine,
        SessionInterface $session): Response
    {
        if ($this->isCsrfTokenValid('stripe_token', $token)) {
            $order = $orderRepository->find($id);
            $order->setStatus('PAYMENT_ACCEPTED');
            $order->setUpdatedAt(new \DateTime());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($order);
            $entityManager->flush();
            $session->set('cart', []);
            $this->addFlash(
                'success',
                'La commande à bien été valider et le paiement également ! Merci !'
            );
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_home');
    }

    #[Route('/orderCanceled', name: 'app_order_cancel')]
    public function cancel_paiement(
        OrderRepository $orderRepository,
        ManagerRegistry $doctrine,
        SessionInterface $session): Response
    {
        $this->addFlash(
            'error',
            'Le paiement de la commande à échoué ! Merci de repasser commande ! 
            les détails de la commande sont disponible sur votre espace commandes !'
        );
        return $this->redirectToRoute('app_home');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/orders', name: 'app_orders')]
    public function show_orders(OrderRepository $orderRepository): Response
    {
        $userId = $this->getUser()->getId();
        $ordersDB = $orderRepository->findBy(['userId' => $userId]);

        $orders = [];
        foreach($ordersDB as $order){
            $price = 0;
            foreach($order->getProductsOrders()->getValues() as $productOrder){
                $price += $productOrder->getPrice();
            }
            array_push($orders, [$price => $order]);
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/order/{orderId}', name: 'app_order_detail')]
    public function show_order_detail(int $orderId, OrderRepository $orderRepository, ProductRepository $productRepository): Response
    {
        $userId = $this->getUser()->getId();
        $orderDB = $orderRepository->find($orderId);
        if($orderDB->getUserId()->getId() !== $userId){
            $this->addFlash(
                'error',
                'La commande n\'existe pas !'
            );
            return $this->redirectToRoute('app_orders');
        }

        $products = [];
        $productsOrder = [];
        $price = 0;
        foreach($orderDB->getProductsOrders()->getValues() as $productOrder){
            $price += $productOrder->getPrice();
            array_push($productsOrder, $productOrder);
            array_push($products, $productOrder->getProduct());
        }
//        dd($products, $productsOrder);

        return $this->render('order/orderDetail.html.twig', [
            'productsOrder' => $productsOrder,
            'products' => $products,
            'totalPrice' => $price
        ]);
    }
}
