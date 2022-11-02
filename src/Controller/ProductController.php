<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\TypeProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/products/{page}', name: 'app_admin_products', requirements: ['page' => '\d+'])]
    public function productManagement(Request $request, ProductRepository $productRepository, int $page = 1): Response
    {
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $search = $request->request->get('search');
        $productId = $request->get('product');
        if($productId !== null){
            $product = $productRepository->find($productId);
            return $this->render('admin/products/productManagementById.html.twig', [
                'product' => $product
            ]);
        }
        if($request->getMethod() === "POST" && $search !== null){
            $products = $productRepository->findByName($search, $limit, $offset);
            $countProducts = $productRepository->findByNameTotal($search);
        }
        else{
            $products = $productRepository->findBy([], ['productType' => 'ASC'], $limit, $offset);
            $countProducts = $productRepository->findTotal();
        }

        return $this->render('admin/products/productManagement.html.twig', [
            'products' => $products,
            'actual_page' => $page,
            'last_search' => $search,
            'pages' => ceil($countProducts / $limit) > 0 ? ceil($countProducts / $limit) : 1
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/products/create', name: 'app_admin_product_create')]
    public function createProduct(
        Request $request,
        TypeProductRepository $typeProductRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger): Response
    {
        $form = $request->request->all();
        $formFile = $request->files->get('file');
        $errors = [];
        if($request->getMethod() === "POST" && $form !== null){
            $product = new Product();
            if(strlen($form['name']) < 1){
                array_push($errors, ['name' => 'Le nom est requis']);
            }
            if(strlen($form['price']) < 1 || $form['price'] > 100){
                array_push($errors, ['price' => 'Le prix est requis et ne doit pas dépasser 100€']);
            }
            if(strlen($form['type']) < 1){
                array_push($errors, ['type' => 'Le type de produit est requis !']);
            }
            $product->setName($form['name']);
            $product->setPrice(floatval($form['price']) * 100);
            $product->setProductType($typeProductRepository->find(intval($form['type'])));
            if(array_key_exists('availability', $form)){
                $product->setAvailability(true);
            }else{
                $product->setAvailability(false);
            }
            if($formFile !== null){
                $safeFilename = $slugger->slug(pathinfo($formFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$formFile->guessExtension();

                try {
                    $formFile->move(
                        $this->getParameter('directory_img'),
                        $newFilename
                    );
                    $product->setImg($newFilename);
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        "L'article \"".$product->getName()."\" n'as pas pu être créer !"
                    );
                    return $this->redirectToRoute('app_admin_product_create');
                }
            }
            if(count($errors) === 0){
                $entityManager->persist($product);
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    "L'article \"".$product->getName()."\" à bien été créer !"
                );
                return $this->redirectToRoute('app_admin_products');
            }
            dd($newFilename);
        }

        $types = $typeProductRepository->findAll();

        return $this->render('admin/products/createProduct.html.twig', [
            'types' => $types
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/products/update', name: 'app_admin_product_update')]
    public function updateProduct(
        Request $request,
        ProductRepository $productRepository,
        TypeProductRepository $typeProductRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger): Response
    {
        $product = $productRepository->find($request->get('product'));
        $types = $typeProductRepository->findAll();
        $form = $request->request->all();
        $formFile = $request->files->get('file');
        $errors = [];
        if($request->getMethod() === "POST" && $form !== null){
            if(strlen($form['name']) < 1){
                array_push($errors, ['name' => 'Le nom est requis']);
            }
            if(strlen($form['price']) < 1 || $form['price'] > 100){
                array_push($errors, ['price' => 'Le prix est requis et ne doit pas dépasser 100€']);
            }
            if(strlen($form['type']) < 1){
                array_push($errors, ['type' => 'Le type de produit est requis !']);
            }
            $product->setName($form['name']);
            $product->setPrice(floatval($form['price']) * 100);
            $product->setProductType($typeProductRepository->find(intval($form['type'])));
            if(array_key_exists('availability', $form)){
                $product->setAvailability(true);
            }else{
                $product->setAvailability(false);
            }
            if($formFile !== null){
                $safeFilename = $slugger->slug(pathinfo($formFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$formFile->guessExtension();

                try {
                    $formFile->move(
                        $this->getParameter('directory_img'),
                        $newFilename
                    );
                    $product->setImg($newFilename);
                } catch (FileException $e) {
                    return $this->redirectToRoute('app_admin_product_create');
                }
            }

            if(count($errors) === 0){
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    "L'article \"".$product->getName()."\" à bien été mis à jour !"
                );
                return $this->redirectToRoute('app_admin_products');
            }
        }

        return $this->render('admin/products/productManagementById.html.twig', [
            'product' => $product,
            'types' => $types,
            'errors' => $errors
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/products/delete', name: 'app_admin_delete_product')]
    public function userManagementDelete(Request $request, ProductRepository $productRepository, ManagerRegistry $doctrine): Response
    {
        $product = $productRepository->find($request->get('product'));
        $name = $product->getName();
        $entityManager = $doctrine->getManager();
        if($product !== null){
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash(
                'success',
                "L'article \"".$name."\" à bien été supprimer !"
            );
        }

        return $this->redirectToRoute('app_admin_products', []);
    }

    #[Route('/menu', name: 'app_menu')]
    public function showMenu(SessionInterface $session, TypeProductRepository $typeProductRepository): Response
    {
        $types = $typeProductRepository->findAll();
        $productsTypes = [];
        for($i = 0; $i < count($types); $i++){
            $productsTypes[$types[$i]->getName()] = $types[$i]->getProducts()->toArray();
        }

        $cart = $session->get('cart', []);

        return $this->render('product/index.html.twig', [
            'productsTypes' => $productsTypes,
            'cart' => $cart
        ]);
    }
}
