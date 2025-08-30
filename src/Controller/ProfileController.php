<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(Request $request, EntityManagerInterface $em, RecipeRepository $recipeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à votre profil.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        // Gestion de la présentation
        if ($request->isMethod('POST') && $request->request->has('presentation')) {
            $presentation = trim($request->request->get('presentation'));
            $user->setPresentation($presentation);
            $em->flush();
            $this->addFlash('success', 'Présentation mise à jour !');

            return $this->redirectToRoute('profile');
        }

        // Gestion de l'avatar via le formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                $newFilename = uniqid().'_'.time().'.'.$avatarFile->guessExtension();
                try {
                    $avatarFile->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );
                    // Supprimer l’ancienne image si elle existe
                    if ($user->getAvatar()) {
                        $oldAvatarPath = $this->getParameter('avatars_directory').'/'.$user->getAvatar();
                        if (file_exists($oldAvatarPath)) {
                            @unlink($oldAvatarPath);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                }
                $user->setAvatar($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour !');
            return $this->redirectToRoute('profile');
        }

        // Récupérer les recettes de l'utilisateur connecté
        $recipes = $recipeRepository->findBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'recipes' => $recipes,
        ]);
    }

    #[Route('/profile/upload-avatar', name: 'profile_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre avatar.');
        }

        /** @var UploadedFile|null $avatarFile */
        $avatarFile = $request->files->get('avatar');

        if ($avatarFile) {
            $newFilename = uniqid().'_'.time().'.'.$avatarFile->guessExtension();
            try {
                $avatarFile->move(
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );

                // Supprimer l’ancienne image si elle existe
                if ($user->getAvatar()) {
                    $oldAvatarPath = $this->getParameter('avatars_directory').'/'.$user->getAvatar();
                    if (file_exists($oldAvatarPath)) {
                        @unlink($oldAvatarPath);
                    }
                }

            } catch (FileException $e) {
                $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                return $this->redirectToRoute('profile');
            }

            $user->setAvatar($newFilename);
            $em->flush();
            $this->addFlash('success', 'Avatar mis à jour !');
        }

        return $this->redirectToRoute('profile');
    }
}
