<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 12:04 PM
 */

namespace Amare53\HelperBundle\Controller;

use Amare53\HelperBundle\Contracts\EntityParamsInterface;
use Amare53\HelperBundle\Contracts\EntityToJsonInterface;
use Amare53\HelperBundle\Contracts\PaginatorInterface;
use Amare53\HelperBundle\Helper\ErrorJsonFormat;
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

    /**
     * @Route("",methods={"GET","HEAD"})
     * @param EntityToJsonInterface $entityToJson
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return JsonResponse
     */
    public function index(EntityToJsonInterface $entityToJson, PaginatorInterface $paginator, Request $request): JsonResponse
    {

        $query = $this->getBuilder('b', $request)
            ->getQuery();

        $paginate = $paginator->paginate($query);
        return $this->DTO($paginate, $entityToJson);
    }

    /**
     * @Route("/count/value",methods={"GET","HEAD"})
     */
    final public function count(): JsonResponse
    {
        return new JsonResponse($this->getRepository()->count([]));
    }

    /**
     * @Route("/{id}",methods={"GET","HEAD"})
     * @param string $id
     * @param EntityToJsonInterface $entityToJson
     * @return JsonResponse
     */
    public function show(string $id, EntityToJsonInterface $entityToJson): JsonResponse
    {
        return new JsonResponse($entityToJson->toJson($this->getRepository()->find($id), $this->groupsShow));
    }


    /**
     * @Route("",methods={"POST"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param EntityToJsonInterface $entityToJson
     * @param EntityParamsInterface $array_to_Entity
     * @return JsonResponse
     */
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityToJsonInterface $entityToJson,
        EntityParamsInterface $array_to_Entity
    ): JsonResponse
    {

        $files = $this->extractFiles($request);
        $entity = $array_to_Entity->convert(array_merge($request->request->all(), $files), $this->entity);
        $errors = $validator->validate(value: $entity, groups: $this->groupsCreate);

        if (count($errors) > 0) {
            return new JsonResponse(ErrorJsonFormat::getErrors($errors), 400);
        }

        $this->saveEntity($entity);
        return new JsonResponse($entityToJson->toJson($entity, $this->groupsShow), 201);
    }


    /**
     * @Route("/{id}",methods={"PUT"})
     * @param string $id
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param EntityParamsInterface $array_to_Entity
     * @param EntityToJsonInterface $entityToJson
     * @param PasswordHasherFactoryInterface $passwordEncoder
     * @return JsonResponse
     */
    public function edit(
        string $id,
        Request $request,
        ValidatorInterface $validator,
        EntityParamsInterface $array_to_Entity,
        EntityToJsonInterface $entityToJson,
        PasswordHasherFactoryInterface $passwordEncoder
    ): JsonResponse
    {

        $entity = $this->getRepository()->find($id);

        if ($entity) {

            $entity->setUpdatedAt(new \DateTimeImmutable());

            if ($request->request->get('password') === "") {
                $request->request->remove('password');
            } else if ($request->request->get('password') !== "" && $request->request->get('password') !== null) {
                $hashes = $passwordEncoder->getPasswordHasher($entity);
                $hashedPassword = $hashes->hash($request->request->get('password'));
                $request->request->set('password', $hashedPassword);
            }

            $files = $this->extractFiles($request);

            $entity = $array_to_Entity->convert(array_merge($request->request->all(), $files), $this->entity);
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
    /**
     * @Route("/{id}",methods={"DELETE"})
     * @param string $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
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

    private function extractFiles(Request $request): array
    {
        $files = [];

        foreach ($this->files as $file) {
            $myFile = $request->files->get($file);
            $files[$file] = $myFile;
        }

        return $files;
    }

    private function saveEntity(mixed $entity): void
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($entity);
        $entityManager->flush();
    }

    final public function getRepository(): EntityRepository
    {
        /** @var EntityRepository $repository */
        $repository = $this->getDoctrine()->getRepository($this->entity::class);
        return $repository;
    }

    final public function getBuilder(string $alias, Request $request): QueryBuilder
    {
        return $this->getRepository()
            ->createQueryBuilder($alias)
            ->orderBy("{$alias}.createdAt", 'DESC')
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
