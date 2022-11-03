<?php

namespace App\Controller;

use App\Repository\OrderRepository;
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
    #[Route('/admin/orders', name: 'app_admin_orders')]
    public function orders(Request $request, OrderRepository $orderRepository): Response
    {
        $status = [
            0 => 'PAYMENT_WAITING',
            1 => 'PAYMENT_ACCEPTED',
            2 => 'OUT_FOR_DELIVERY',
            3 => 'DELIVER',
        ];
        if($request->get('status') !== null){
            $currentStatus = $request->get('status');
        }else{
            $currentStatus = 2;
        }

        $orders = $orderRepository->findBy(['status' => $status[$currentStatus - 1]]);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'status' => $status,
            'currentStatus' => $currentStatus
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/order/{id}', name: 'app_admin_order_detail')]
    public function order_detail(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        $products = [];
        $customsBox = [];
        $productsOrder = [];
        $price = 0;
        foreach($order->getProductsOrders()->getValues() as $productOrder){
            $price += $productOrder->getPrice();
            array_push($productsOrder, $productOrder);
            if($productOrder->getProduct() === null){
                array_push($customsBox, $productOrder->getCustomBox());
            }else{
                array_push($products, $productOrder->getProduct());
            }
        }

        return $this->render('admin/order/orderDetails.html.twig', [
            'productsOrder' => $productsOrder,
            'products' => $products,
            'customsBox' => $customsBox,
            'price' => $price
        ]);
    }
}
