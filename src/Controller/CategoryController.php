<?php

namespace App\Controller;
use App\Entity\Category;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

final class CategoryController extends AbstractController
{
        // Quelle entité correspond à quel "kind"
    private const ENTITIES = [
       
        'category'   => Category::class,
      
    ];
     // Symfony fournit l'EntityManager : c'est lui qui écrit dans la base
    public function __construct(private EntityManagerInterface $em)
    {
    }
    

 // ===== Catégories et ingrédients (même logique, seul "kind" change) =====

    #[Route('/category/', name: 'app_category_index')]
    public function categoryIndex(): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $this->em->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
        ]);
    }



#[Route('/category/new', name: 'app_category_new', defaults: ['kind' => 'category'], methods: ['GET', 'POST'])]
 
    public function simpleNew(Request $request, string $kind): Response
    {
        $class = self::ENTITIES[$kind];
        $item = new $class();   // new Category()

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/new.html.twig', 'app_'.$kind.'_index');
    }

    #[Route('/category/{id}/edit', name: 'app_category_edit', requirements: ['id' => '\d+'], defaults: ['kind' => 'category'])]
    public function simpleEdit(Request $request, string $kind, int $id): Response
    {
        $item = $this->find(self::ENTITIES[$kind], $id);

        return $this->submit($request, $this->nameForm($item), $item, $kind.'/edit.html.twig', 'app_'.$kind.'_index');
    }

    // ===== Suppression (les trois entités) =====

    #[Route('/category/{id}/delete', name: 'app_category_delete', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['kind' => 'category'])]
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




    
  
  