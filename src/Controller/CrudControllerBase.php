<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 12:04 PM
 */

namespace Amare53\HelperBundle\Controller;

use Amare53\HelperBundle\Contracts\ArrayToEntityInterface;
use Amare53\HelperBundle\Contracts\EntityToJsonInterface;
use Amare53\HelperBundle\Contracts\PaginatorInterface;
use Amare53\HelperBundle\Helper\ErrorJsonFormat;
use Amare53\HelperBundle\Dto\EntityDto;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class CrudControllerBase extends AbstractController
{

    protected mixed $entity;
    protected array $files = [];
    protected string|null $groupsIndex = null;
    protected string|null $groupsShow = null;
    protected string|null $groupsCreate = null;
    protected string|null $groupsUpdate = null;
    protected string $sortBy = 'createdAt';
    protected string $sortDirection = 'DESC';


    #[Route(path: '', methods: ['GET', 'HEAD'])]
    public function index(
        EntityToJsonInterface $entityToJson,
        PaginatorInterface    $paginator,
        Request               $request
    ): JsonResponse
    {

        $query = $this->getBuilder('b', $request);

        $params = $request->query->all();
        $t_search = null;

        if (array_key_exists('type_search',$params)){
            $t_search = $params['type_search'];
            unset($params['type_search']);
        }

        foreach ($params as $key => $value) {
            if ($t_search && $t_search === 'like'){
                $query->andWhere("b.{$key} like :{$key}")
                    ->setParameter($key,"%{$value}%");
            }else{
                $query->andWhere("b.{$key} = :{$key}")
                    ->setParameter($key,$value);
            }
        }

        $paginate = $paginator->paginate($query->getQuery());
        return $this->DTO($paginate, $entityToJson);
    }

    #[Route(path: '/count', methods: ['GET', 'HEAD'])]
    final public function count(): JsonResponse
    {
        return new JsonResponse($this->getRepository()->count([]));
    }

    #[Route(path: '/{id}', methods: ['GET', 'HEAD'])]
    public function show(string $id, EntityToJsonInterface $entityToJson): JsonResponse
    {
        return new JsonResponse($entityToJson->toJson($this->getRepository()->find($id), $this->groupsShow));
    }

    #[Route(path: '', methods: ['POST'])]
    public function create(
        Request                $request,
        ValidatorInterface     $validator,
        EntityToJsonInterface  $entityToJson,
        ArrayToEntityInterface $array_to_Entity
    ): JsonResponse
    {

        $files = $this->extractFiles($request);
        $entity_dto = new EntityDto();
        $entity_dto->setEntity($this->entity);
        $entity_conver = $array_to_Entity->convert(array_merge($request->request->all(), $files), $entity_dto);
        $entity = $entity_conver->getEntity();
        if ($entity_conver->hasError()) {
            return new JsonResponse($entity_conver->getErrors(), 400);
        }
        $errors = $validator->validate(value: $entity, groups: $this->groupsCreate);


        if (count($errors) > 0) {
            return new JsonResponse(ErrorJsonFormat::getErrors($errors), 400);
        }

        $this->saveEntity($entity);
        return new JsonResponse($entityToJson->toJson($entity, $this->groupsShow), 201);
    }


    #[Route('/{id}', methods: ['PUT'])]
    public function edit(
        string                         $id,
        Request                        $request,
        ValidatorInterface             $validator,
        ArrayToEntityInterface         $array_to_Entity,
        EntityToJsonInterface          $entityToJson,
        PasswordHasherFactoryInterface $passwordEncoder
    ): JsonResponse
    {

        $entity = $this->getRepository()->find($id);

        if ($entity) {

            $files = $this->extractFiles($request);
            $entity_dto = new EntityDto();
            $entity_dto->setEntity($entity);
            $entity_conver = $array_to_Entity->convert(array_merge($request->request->all(), $files), $entity_dto);
            $entity = $entity_conver->getEntity();

            if ($entity_conver->hasError()) {
                return new JsonResponse($entity_conver->getErrors(), 400);
            }
            $errors = $validator->validate(value: $entity, groups: $this->groupsUpdate);

            if (count($errors) > 0) {
                return new JsonResponse(ErrorJsonFormat::getErrors($errors), 400);
            }

            $this->saveEntity($entity);

            return new JsonResponse($entityToJson->toJson($entity, $this->groupsShow), 201);
        }

        return new JsonResponse(['not_exist' => "id {$id} does not exist"], 400);
    }


    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id, EntityManagerInterface $em): JsonResponse
    {
        $entity = $this->getRepository()->find($id);
        if ($entity) {

            try {
                $entityManager = $em;
                $entityManager->remove($entity);
                $entityManager->flush();
                return new JsonResponse();
            } catch (\Exception $e) {
            }
        }

        return new JsonResponse(['not_exist' => "id {$id} does not exist"], 400);
    }

    public function extractFiles(Request $request): array
    {
        $files = [];

        foreach ($this->files as $file) {
            $myFile = $request->files->get($file);
            $files[$file] = $myFile;
        }

        return $files;
    }

    public function saveEntity(mixed $entity): void
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($entity);
        $entityManager->flush();
    }

    public function getRepository(): EntityRepository
    {
        /** @var EntityRepository $repository */
        $repository = $this->getDoctrine()->getRepository($this->entity::class);
        return $repository;
    }

    public function getBuilder(string $alias, Request $request): QueryBuilder
    {
        return $this->getRepository()
            ->createQueryBuilder($alias)
            ->orderBy("{$alias}.{$this->sortBy}", $this->sortDirection)
            ->setMaxResults($request->query->getInt('limit', 5));
    }

    public function DTO(PaginationInterface $paginate, EntityToJsonInterface $entityToJson): JsonResponse
    {
        $rows = $entityToJson->toJson($paginate->getItems(), $this->groupsIndex);
        return new JsonResponse([
            'items' => $rows,
            'currentPageNumber' => $paginate->getCurrentPageNumber(),
            'numItemsPerPage' => $paginate->getItemNumberPerPage(),
            'totalCount' => $paginate->getTotalItemCount(),
            'pagesInRange' => $paginate->getPaginationData()['pagesInRange'] ?? null,
            "previous" => $paginate->getPaginationData()['previous'] ?? null,
            "next" => $paginate->getPaginationData()['next'] ?? null
        ]);
    }
}
