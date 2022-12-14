<?php

namespace App\Controller;

use App\Repository\CustomBoxRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
            $session->set('customBox', []);
            $this->addFlash(
                'success',
                'La commande à bien été valider et le paiement également ! Merci !'
            );
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_home');
    }

    #[Route('/orderCanceled', name: 'app_order_cancel')]
    public function cancel_paiement(): Response
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
    public function show_order_detail(int $orderId, OrderRepository $orderRepository): Response
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
        $customsBox = [];
        $productsOrder = [];
        $price = 0;
        foreach($orderDB->getProductsOrders()->getValues() as $productOrder){
            $price += $productOrder->getPrice();
            array_push($productsOrder, $productOrder);
            if($productOrder->getProduct() === null){
                array_push($customsBox, $productOrder->getCustomBox());
            }else{
                array_push($products, $productOrder->getProduct());
            }
        }
//        dd($products, $productsOrder);

        return $this->render('order/orderDetail.html.twig', [
            'productsOrder' => $productsOrder,
            'products' => $products,
            'totalPrice' => $price,
            'customsBox' => $customsBox,
            'price' => $price
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/admin/customBox/{id}', name: 'app_order_detail_custom_box')]
    public function order_detail_custom_box(int $id, Request $request, CustomBoxRepository $customBoxRepository): Response
    {
        $customBox = $customBoxRepository->find($id);


        if($customBox->getUser()->getId() !== $this->getUser()->getId()){
            return $this->redirect($request->headers->get('referer'));
        }

        return $this->render('admin/order/orderDetailsCustomBox.html.twig', [
            'positions' => $customBox->getPositions(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/deliver/order/{id}', name: 'app_admin_deliver_order')]
    public function deliver_order(int $id, OrderRepository $orderRepository, ManagerRegistry $doctrine): Response
    {
        $order = $orderRepository->find($id);
        $em = $doctrine->getManager();

        if($order !== null){
            $order->setStatus('OUT_FOR_DELIVERY');
            $order->setUpdatedAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_orders');
    }
}
