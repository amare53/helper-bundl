<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 11:43 AM
 */

namespace Amare53\HelperBundle\Helper;

use Amare53\HelperBundle\Contracts\EntityParamsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;


class ArrayToEntity implements EntityParamsInterface
{
    private Type|null $types = null;

    public function __construct(private EntityManagerInterface $manager)
    {
    }

    final public function convert(array $params, mixed $entity): mixed
    {

        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpDocExtractor, $reflectionExtractor];
        $descriptionExtractors = [$phpDocExtractor];
        $accessExtractors = [$reflectionExtractor];
        $propertyInitializableExtractors = [$reflectionExtractor];

        $propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );

        $class = get_class($entity);
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        foreach ($params as $key => $value) {

            if (property_exists($entity, $key)) {
                $types = $propertyInfo->getTypes($class, $key);

                if ($types) {
                    $this->types = $types[0];
                }

                if ($this->types && $this->types->isCollection()) {

                    $items = $value;
                    try {
                        if (is_string($value)) $items = json_decode($value);
                    } catch (\Exception $e) {
                    }

                    if (
                        null !== ($collectionValueType = $this->types->getCollectionValueTypes()) &&
                        \count($collectionValueType) > 0
                    ) {

                        $final_items = [];
                        foreach ($items as $item) {
                            if ($entity_get = $this->manager
                                ->getRepository($collectionValueType[0]->getClassName())
                                ->find($item)){
                                $final_items[] = $entity_get;
                            }
                        }

                        $value = $final_items;
                    }

                } else {
                    if ($this->types && $this->types->getClassName()) {
                        if (
                            str_contains($this->types->getClassName(), 'Date') ||
                            str_contains($this->types->getClassName(), 'Time')
                        ) {
                            try {
                                $value = new \DateTimeImmutable($value);
                            } catch (\Exception $exception) {
                            }
                        } else {
                            if ($value_one = $this->manager->getRepository($this->types->getClassName())->find($value)){
                                $value = $value_one;
                            }
                        }
                    } else {
                        if (
                            str_contains($this->types->getClassName(), 'Date') ||
                            str_contains($this->types->getClassName(), 'Time')
                        ) {
                            try {
                                $value = new \DateTimeImmutable($value);
                            } catch (\Exception $exception) {
                            }
                        }
                    }
                }

                try {
                    $propertyAccessor->setValue($entity, $key, $value);
                }catch (\Exception $exception){}
            }
        }

        return $entity;
    }
}
