<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Récupère la recette la plus vue
     */
    public function findMostViewedRecipe(): ?Recipe
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.views', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère la recette la mieux notée avec au moins 10 votes
     */
    public function findBestRatedRecipe(): ?Recipe
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.ratings', 'ra')
            ->addSelect('AVG(ra.value) as HIDDEN avgRating')
            ->addSelect('COUNT(ra.id) as HIDDEN ratingCount')
            ->groupBy('r.id')
            ->having('COUNT(ra.id) >= 10')
            ->orderBy('avgRating', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère la recette la plus ajoutée en favoris
     */
    public function findMostFavoritedRecipe(): ?Recipe
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.favorites', 'f')
            ->addSelect('COUNT(f.id) as HIDDEN favCount')
            ->groupBy('r.id')
            ->orderBy('favCount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
