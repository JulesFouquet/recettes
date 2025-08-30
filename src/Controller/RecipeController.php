<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/recipe')]
class RecipeController extends AbstractController
{
    #[Route('/', name: 'recipe_index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): Response
    {
        $recipes = $recipeRepository->findAll();

        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/new', name: 'recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une recette.');
            }
            $recipe->setUser($user);
            $recipe->setCreatedAt(new \DateTimeImmutable());

            // Gestion de l'image uploadée
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'_'.time().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('recipes_directory'),
                        $newFilename
                    );
                    $recipe->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                }
            }

            $em->persist($recipe);
            $em->flush();

            $this->addFlash('success', 'Recette ajoutée !');

            return $this->redirectToRoute('profile');
        }

        return $this->render('recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user || $recipe->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres recettes.');
        }

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setUpdatedAt(new \DateTime());

            // Gestion de l'image uploadée
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'_'.time().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('recipes_directory'),
                        $newFilename
                    );
                    $recipe->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Recette modifiée !');

            return $this->redirectToRoute('profile');
        }

        return $this->render('recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user || $recipe->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres recettes.');
        }

        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            $em->remove($recipe);
            $em->flush();
            $this->addFlash('success', 'Recette supprimée !');
        }

        return $this->redirectToRoute('profile');
    }

    #[Route('/{id}/increment-view', name: 'recipe_increment_view', methods: ['POST'])]
    public function incrementView(Recipe $recipe, EntityManagerInterface $em): JsonResponse
    {
        $recipe->setViews($recipe->getViews() + 1);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'views' => $recipe->getViews(),
        ]);
    }
}
