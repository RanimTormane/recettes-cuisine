<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class PreviewController extends AbstractController
{
    // Quelle entité correspond à quel "kind"
    private const ENTITIES = [
        'recipe'     => Recipe::class,
        'category'   => Category::class,
        'ingredient' => Ingredient::class,
    ];

    // Symfony fournit l'EntityManager : c'est lui qui écrit dans la base
    public function __construct(private EntityManagerInterface $em)
    {
    }

    // ===== Recettes =====

    #[Route('/recipe/', name: 'app_recipe_index')]
    public function recipeIndex(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $categoryId = (int) $request->query->get('category', 0);

        // Recherche par nom et filtre par catégorie
        $qb = $this->em->createQueryBuilder()
            ->select('r', 'c')
            ->from(Recipe::class, 'r')
            ->leftJoin('r.category', 'c')
            ->orderBy('r.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('r.name LIKE :q')->setParameter('q', '%'.$q.'%');
        }
        if ($categoryId > 0) {
            $qb->andWhere('c.id = :cat')->setParameter('cat', $categoryId);
        }

        return $this->render('recipe/index.html.twig', [
            'recipes' => $qb->getQuery()->getResult(),
            'categories' => $this->em->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
            'q' => $q,
            'category_id' => $categoryId,
        ]);
    }

    #[Route('/recipe/new', name: 'app_recipe_new')]
    public function recipeNew(Request $request): Response
    {
        $recipe = new Recipe();

        return $this->submit($request, $this->recipeForm($recipe), $recipe, 'recipe/new.html.twig', 'app_recipe_index');
    }

    #[Route('/recipe/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'])]
    public function recipeShow(int $id): Response
    {
        return $this->render('recipe/show.html.twig', ['recipe' => $this->find(Recipe::class, $id)]);
    }

    #[Route('/recipe/{id}/edit', name: 'app_recipe_edit', requirements: ['id' => '\d+'])]
    public function recipeEdit(Request $request, int $id): Response
    {
        $recipe = $this->find(Recipe::class, $id);

        return $this->submit($request, $this->recipeForm($recipe), $recipe, 'recipe/edit.html.twig', 'app_recipe_index', ['recipe' => $recipe]);
    }

    // ===== Catégories et ingrédients (même logique, seul "kind" change) =====

    #[Route('/category/', name: 'app_category_index')]
    public function categoryIndex(): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $this->em->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/ingredient/', name: 'app_ingredient_index')]
    public function ingredientIndex(): Response
    {
        return $this->render('ingredient/index.html.twig', [
            'ingredients' => $this->em->getRepository(Ingredient::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

#[Route('/category/new', name: 'app_category_new', defaults: ['kind' => 'category'], methods: ['GET', 'POST'])]
    #[Route('/ingredient/new', name: 'app_ingredient_new', defaults: ['kind' => 'ingredient'])]
    public function simpleNew(Request $request, string $kind): Response
    {
        $class = self::ENTITIES[$kind];
        $item = new $class();   // new Category() ou new Ingredient()

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/new.html.twig', 'app_'.$kind.'_index');
    }

    #[Route('/category/{id}/edit', name: 'app_category_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'category'])]
    #[Route('/ingredient/{id}/edit', name: 'app_ingredient_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'ingredient'])]
    public function simpleEdit(Request $request, string $kind, int $id): Response
    {
        $item = $this->find(self::ENTITIES[$kind], $id);

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/edit.html.twig', 'app_'.$kind.'_index');
    }

    // ===== Suppression (les trois entités) =====

    #[Route('/recipe/{id}/delete', name: 'app_recipe_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'recipe'])]
    #[Route('/category/{id}/delete', name: 'app_category_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'category'])]
    #[Route('/ingredient/{id}/delete', name: 'app_ingredient_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'ingredient'])]
    public function delete(Request $request, string $kind, int $id): Response
    {
        $item = $this->find(self::ENTITIES[$kind], $id);

        if ($this->isCsrfTokenValid('delete'.$id, (string) $request->request->get('_token'))) {
            try {
                $this->em->remove($item);
                $this->em->flush();
                $this->addFlash('success', 'Suppression effectuée.');
            } catch (ForeignKeyConstraintViolationException) {
                // Une catégorie ou un ingrédient encore utilisé par une recette
                $this->addFlash('danger', 'Suppression impossible : cet élément est utilisé par des recettes.');
            }
        }

        return $this->redirectToRoute('app_'.$kind.'_index');
    }

    // ===== Outils =====

    // Cherche une ligne par son id, sinon erreur 404
    private function find(string $class, int $id): object
    {
        return $this->em->find($class, $id) ?? throw $this->createNotFoundException();
    }

    // Règles communes : champ obligatoire (et texte vide plutôt que null)
    private function rules(): array
    {
        return ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')]];
    }

    // Formulaire à un champ (Category et Ingredient)
    private function nameForm(object $entity): FormInterface
    {
        return $this->createFormBuilder($entity)
            ->add('name', TextType::class, array_merge(['label' => 'Nom'], $this->rules()))
            ->getForm();
    }

    // Formulaire de recette : les champs correspondent aux propriétés de l'entité Recipe
    private function recipeForm(Recipe $recipe): FormInterface
    {
        return $this->createFormBuilder($recipe)
            ->add('name', TextType::class, array_merge(['label' => 'Nom'], $this->rules()))
            ->add('description', TextareaType::class, array_merge(['label' => 'Description', 'attr' => ['rows' => 3]], $this->rules()))
            ->add('steps', TextareaType::class, array_merge(['label' => 'Étapes de préparation (une par ligne)', 'attr' => ['rows' => 6]], $this->rules()))
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'constraints' => [new NotNull(message: 'Choisissez une catégorie.')],
            ])
            ->add('ingredients', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'label' => 'Ingrédients',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->getForm();
    }

    // Traite le formulaire : si valide, INSERT (ou UPDATE) dans la base, puis redirection
    private function submit(Request $request, FormInterface $form, object $entity, string $template, string $redirect, array $params = []): Response
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entity);   // "suis cet objet" (sans effet s'il vient déjà de la base)
            $this->em->flush();            // exécute vraiment le INSERT / UPDATE SQL
            $this->addFlash('success', 'Enregistrement effectué.');

            return $this->redirectToRoute($redirect);
        }

        return $this->render($template, ['form' => $form] + $params);
    }
}