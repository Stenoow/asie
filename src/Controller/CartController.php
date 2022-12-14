<?php

namespace App\Controller;

use App\Entity\CustomBox;
use App\Entity\Order;
use App\Entity\ProductsOrder;
use App\Repository\CustomBoxRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session,
                          ProductRepository $productRepository,
                          CustomBoxRepository $customBoxRepository): Response
    {
        $sessionCart = $session->get('cart', []);
        $sessionCustomBox = $session->get('customBox', []);

        $cart = [];
        foreach($sessionCart as $productId => $quantity){
            $product = $productRepository->find($productId);
            $cart[$productId] = [$quantity => $product];
        }
        $customsBox = [];
        foreach($sessionCustomBox as $customBoxId => $quantity){
            $customBox = $customBoxRepository->find($customBoxId);
            $customsBox[$customBoxId] = [$quantity => $customBox];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'customsBox' => $customsBox
        ]);
    }

    #[Route('/cart/add/{redirect}', name: 'app_add_cart')]
    public function addToCart(Request $request, SessionInterface $session, ProductRepository $productRepository, string $redirect = 'app_menu'): Response
    {
        $productId = $request->get('product');
        $product = null;
        $customBoxId = $request->get('customBox');
        $quantity = $request->get('quantity');
        $cart = $session->get('cart', []);
        $customsBoxCart = $session->get('customBox', []);
        if($productId !== null){
            $product = $productRepository->find($productId);
            $productName = $product->getName();
        }

        // if click on delete button and product is in cart
        if($request->get('del') !== null && array_key_exists($product, $cart)){
            // delete quantity of the product if the quantity in cart is > of quantity he wants delete
            if($quantity < $cart[$product]){
                $cart[$product] -= $quantity;
            }else{
                unset($cart[$product]);
            }
            $this->addFlash(
                'success',
                "L'article \"$productName\" ?? bien ??t?? supprimer du panier !"
            );
        }else if($request->get('del') !== null && array_key_exists($customBoxId, $customsBoxCart)){
            if($quantity < $customsBoxCart[$customBoxId]){
                $customsBoxCart[$customBoxId] -= $quantity;
            }else{
                unset($customsBoxCart[$customBoxId]);
            }

            $this->addFlash(
                'success',
                "L'article \"Bo??te Customiser\" ?? bien ??t?? supprimer du panier !"
            );
        }else if($request->get('add') !== null){
            if(array_key_exists($product, $cart)){
                $cart[$product] += $quantity;
            }else{
                $cart[$product] = intval($quantity);
            }

            $this->addFlash(
                'success',
                "L'article \"$productName\" ?? bien ??t?? ajouter au panier ! ($cart[$product] au total)"
            );
        }

        $session->set('cart', $cart);
        $session->set('customBox', $customsBoxCart);

        return $this->redirectToRoute($redirect);
    }

    #[Route('/cart/customBox/{redirect}', name: 'app_add_cart_custom_box')]
    public function addToCartCustomBox(Request $request,
                                        ManagerRegistry $doctrine,
                                        SessionInterface $session,
                                        UserRepository $userRepository,
                                        ProductRepository $productRepository,
                                        string $redirect = 'app_menu'): Response
    {
        $positions = json_decode($request->request->get('json'), true);

        $customBox = new CustomBox();
        $price = 0;
        foreach($positions as $x => $positionZ){
            foreach ($positionZ as $z => $productId){
                $product = $productRepository->find($productId);
                if($product !== null){
                    $price += $product->getPrice();
                }else{
                    unset($positions[$x][$z]);
                }
            }
        }
        if($price > 0){
            $customBox->setPositions($positions);
            // add 3??? for create a custom box
            $customBox->setPrice($price + 300);
            $user = $userRepository->find($this->getUser()->getId());
            $customBox->setUser($user);
            $em = $doctrine->getManager();
            $em->persist($customBox);
            $em->flush();
            $sessionCustomBox = $session->get('customBox', []);
            $sessionCustomBox[$customBox->getId()] = 1;
            $session->set('customBox', $sessionCustomBox);
        }

        return $this->redirectToRoute($redirect);
    }


    #[Route('/cart/validate', name: 'app_validate_cart')]
    public function validateCart(
        Request $request,
        UserRepository $userRepository,
        SessionInterface $session,
        ProductRepository $productRepository,
        CustomBoxRepository $customBoxRepository,
        ManagerRegistry $doctrine): Response
    {
        $cart = $session->get('cart', []);
        $customsBox = $session->get('customBox', []);
        $userId = $this->getUser()->getId();

        if(count($cart) < 1 && count($customsBox) < 1){
            dd($cart);
        }

        $entityManager = $doctrine->getManager();

        $order = new Order();
        $order->setUserId($userRepository->find($userId));
        $order->setStatus('PAYMENT_WAITING');
        $order->setCreatedAt(new \DateTime());
        $order->setUpdatedAt(new \DateTime());

        $products = [];
        foreach($cart as $productId => $quantity){
            $product = $productRepository->find($productId);
            $products[] =
                ['price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->getName()
                    ],
                    'unit_amount' => $product->getPrice()
                ],
                'quantity' => $quantity];
            $productOrder = new ProductsOrder();
            $productOrder->setProduct($product);
            $productOrder->setOrderId($order);
            $productOrder->setQuantity($quantity);
            $productOrder->setPrice($quantity * $product->getPrice());
            $entityManager->persist($productOrder);
        }
        foreach($customsBox as $customBoxId => $quantity){
            $customBox = $customBoxRepository->find($customBoxId);
            $products[] =
                ['price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Bo??te Customiser par vos soins !'
                    ],
                    'unit_amount' => $customBox->getPrice()
                ],
                'quantity' => $quantity];
            $productOrder = new ProductsOrder();
            $productOrder->setCustomBox($customBox);
            $productOrder->setOrderId($order);
            $productOrder->setQuantity($quantity);
            $productOrder->setPrice($quantity * $customBox->getPrice());
            $entityManager->persist($productOrder);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $tokenProvider = $this->container->get('security.csrf.token_manager');
        $token = $tokenProvider->getToken('stripe_token')->getValue();

        \Stripe\Stripe::setApiKey('sk_test_51LkoPlBf7yzJXVv6cCtLuKV5gLGS2SBzK9dZ6m7bBNrOZf8JS3yr9Qxh8s6iIZgwFR108r41VtlwayBivyOnGYm100cfzP4QNo');

        $YOUR_DOMAIN = 'http://localhost'.$request->getBaseURL();

        $checkout_session = \Stripe\Checkout\Session::create([
            'line_items' => $products,
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/orderSuccess/'.$order->getId().'/'. $token,
            'cancel_url' => $YOUR_DOMAIN . '/orderCanceled',
        ]);

        return $this->redirect($checkout_session->url, 303);
    }


}
