<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/{id}', name: 'user_show')]
    public function show(User $user, RecipeRepository $recipeRepository): Response
    {
        // Récupérer les recettes créées par cet utilisateur
        $recipes = $recipeRepository->findBy(['user' => $user]);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'recipes' => $recipes,
        ]);
    }
}
