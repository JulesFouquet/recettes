<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorite')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Recipe $recipe, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'Non connecté'], 403);
        }

        $favoriteRepo = $em->getRepository(Favorite::class);
        $existing = $favoriteRepo->findOneBy([
            'owner' => $user,
            'recipe' => $recipe
        ]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return new JsonResponse(['status' => 'removed']);
        }

        $favorite = new Favorite();
        $favorite->setOwner($user);
        $favorite->setRecipe($recipe);
        $favorite->setCreatedAt(new \DateTimeImmutable());

        $em->persist($favorite);
        $em->flush();

        return new JsonResponse(['status' => 'added']);
    }
}
