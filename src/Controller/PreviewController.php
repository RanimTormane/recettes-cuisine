<?php

namespace App\Controller;

// TEMPORAIRE : données fictives pour voir les pages. À supprimer quand les vrais CRUD existent.


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class PreviewController extends AbstractController
{
   
    // ===== Recettes =====
#[Route('/recipe/', name: 'app_recipe_index')]
public function reciepeIndex():Response{
    return $this->render('recipe/index.html.twig');
}
    #[Route('/recipe/new', name: 'app_recipe_new')]
    public function recipeNew(Request $request): Response
    {
        return $this->submit($request, $this->recipeForm(), 'recipe/new.html.twig', 'app_recipe_index');
    }
/*
    #[Route('/recipe/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'])]
    public function recipeShow(int $id): Response
    {
        return $this->render('recipe/show.html.twig', ['recipe' => $this->find(self::RECIPES, $id)]);
    }

    /*#[Route('/recipe/{id}/edit', name: 'app_recipe_edit', requirements: ['id' => '\d+'])]
    public function recipeEdit(Request $request, int $id): Response
    {
        $r = $this->find(self::RECIPES, $id);
        $form = $this->recipeForm([
            'name' => $r['name'],
            'description' => $r['description'],
            'steps' => $r['steps'],
            'category' => $r['category']['id'],
            'ingredients' => array_column($r['ingredients'], 'id'),
        ]);

        return $this->submit($request, $form, 'recipe/edit.html.twig', 'app_recipe_index');
    }*/

    // ===== Catégories et ingrédients (même logique, seul "kind" change) =====

    #[Route('/category/', name: 'app_category_index')]
    public function categoryIndex(): Response
    {
        return $this->render('category/index.html.twig');
    }

    #[Route('/ingredient/', name: 'app_ingredient_index')]
    public function ingredientIndex(): Response
    {
        return $this->render('ingredient/index.html.twig');
    }

    #[Route('/category/new', name: 'app_category_new', defaults: ['kind' => 'category'])]
    #[Route('/ingredient/new', name: 'app_ingredient_new', defaults: ['kind' => 'ingredient'])]
    public function simpleNew(Request $request, string $kind): Response
    {
        return $this->submit($request, $this->nameForm(), $kind.'/new.html.twig', 'app_'.$kind.'_index');
    }

    #[Route('/category/{id}/edit', name: 'app_category_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'category'])]
    #[Route('/ingredient/{id}/edit', name: 'app_ingredient_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'ingredient'])]
    public function simpleEdit(Request $request, string $kind, int $id): Response
    {
        $item = $this->find($kind === 'category' ? self::CATEGORIES : self::INGREDIENTS, $id);

        return $this->submit($request, $this->nameForm(['name' => $item['name']]), $kind.'/edit.html.twig', 'app_'.$kind.'_index');
    }
/*
    // ===== Suppression (les trois entités) =====

    #[Route('/recipe/{id}/delete', name: 'app_recipe_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'recipe'])]
    #[Route('/category/{id}/delete', name: 'app_category_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'category'])]
    #[Route('/ingredient/{id}/delete', name: 'app_ingredient_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'ingredient'])]
    public function delete(Request $request, string $kind, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('success', 'Aperçu : rien n’est supprimé pour le moment.');
        }

        return $this->redirectToRoute('app_'.$kind.'_index');
    }

    // ===== Outils =====

    private function find(array $list, int $id): array
    {
        foreach ($list as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        throw $this->createNotFoundException();
    }

    private function nameForm(array $data = []): FormInterface
    {
        return $this->createFormBuilder($data)
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->getForm();
    }

    private function recipeForm(array $data = []): FormInterface
    {
        return $this->createFormBuilder($data)
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('steps', TextareaType::class, ['label' => 'Étapes de préparation (une par ligne)', 'attr' => ['rows' => 6]])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => array_column(self::CATEGORIES, 'id', 'name'),
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('ingredients', ChoiceType::class, [
                'label' => 'Ingrédients',
                'choices' => array_column(self::INGREDIENTS, 'id', 'name'),
                'multiple' => true,
                'expanded' => true,
            ])
            ->getForm();
    }

    private function submit(Request $request, FormInterface $form, string $template, string $redirect): Response
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Aperçu : rien n’est enregistré pour le moment.');

            return $this->redirectToRoute($redirect);
        }

        return $this->render($template, ['form' => $form]);
    }*/
}
