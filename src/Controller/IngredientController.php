<?php

namespace App\Controller;


use App\Entity\Ingredient;
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

class IngredientController extends AbstractController
{
    // Quelle entité correspond à quel "kind"
    private const ENTITIES = [
      
        'ingredient' => Ingredient::class,
    ];

    // Symfony fournit l'EntityManager : c'est lui qui écrit dans la base
    public function __construct(private EntityManagerInterface $em)
    {
    }

   
    // ===== ingrédients (même logique, seul "kind" change) =====

   

    #[Route('/ingredient/', name: 'app_ingredient_index')]
    public function ingredientIndex(): Response
    {
        return $this->render('ingredient/index.html.twig', [
            'ingredients' => $this->em->getRepository(Ingredient::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/ingredient/new', name: 'app_ingredient_new', defaults: ['kind' => 'ingredient'])]
    public function simpleNew(Request $request, string $kind): Response
    {
        $class = self::ENTITIES[$kind];
        $item = new $class();   // new Category() ou new Ingredient()

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/new.html.twig', 'app_'.$kind.'_index');
    }

    #[Route('/ingredient/{id}/edit', name: 'app_ingredient_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'ingredient'])]
    public function simpleEdit(Request $request, string $kind, int $id): Response
    {
        $item = $this->find(self::ENTITIES[$kind], $id);

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/edit.html.twig', 'app_'.$kind.'_index');
    }

    // ===== Suppression (les trois entités) =====

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