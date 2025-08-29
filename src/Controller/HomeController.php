<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        // Récupération des 3 recettes populaires
        $mostViewed = $recipeRepository->findMostViewedRecipe();
        $bestRated = $recipeRepository->findBestRatedRecipe();
        $mostFavorited = $recipeRepository->findMostFavoritedRecipe();

        return $this->render('home/index.html.twig', [
            'mostViewed' => $mostViewed,
            'bestRated' => $bestRated,
            'mostFavorited' => $mostFavorited,
        ]);
    }
}
