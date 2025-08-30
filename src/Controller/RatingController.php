<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RatingController extends AbstractController
{
    #[Route('/recipe/{id}/rate', name: 'rating_add', methods: ['POST'])]
    public function add(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez être connecté'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $value = $data['rating'] ?? null;

        if (!$value || $value < 1 || $value > 5) {
            return new JsonResponse(['error' => 'Note invalide'], 400);
        }

        // Vérifie si l’utilisateur a déjà noté
        $existing = $em->getRepository(Rating::class)->findOneBy([
            'recipe' => $recipe,
            'user' => $this->getUser(),
        ]);

        if ($existing) {
            $existing->setValue($value);
        } else {
            $rating = new Rating();
            $rating->setRecipe($recipe);
            $rating->setUser($this->getUser());
            $rating->setValue($value);
            $em->persist($rating);
        }

        $em->flush();

        // Calcule la moyenne
        $ratings = $recipe->getRatings();
        $total = count($ratings);
        $avg = $total > 0
            ? array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray())) / $total
            : 0;

        return new JsonResponse([
            'success' => true,
            'newAverage' => round($avg, 1),
            'total' => $total,
        ]);
    }
}
