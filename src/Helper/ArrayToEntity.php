<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 11:43 AM
 */

namespace Amare53\HelperBundle\Helper;

use Amare53\HelperBundle\Contracts\ArrayToEntityInterface;
use Amare53\HelperBundle\Contracts\EntityDtoInteface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;


class ArrayToEntity implements ArrayToEntityInterface
{
    private Type|null $types = null;

    public function __construct(private EntityManagerInterface $manager)
    {
    }

    private function initPropertyInfo(): PropertyInfoExtractor
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpDocExtractor, $reflectionExtractor];
        $descriptionExtractors = [$phpDocExtractor];
        $accessExtractors = [$reflectionExtractor];
        $propertyInitializableExtractors = [$reflectionExtractor];

        return new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );
    }

    private function initPropertyAccessor(): PropertyAccessor
    {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();
    }

    final public function convert(array $params, EntityDtoInteface $entityDto): EntityDtoInteface
    {

        $propertyInfo = $this->initPropertyInfo();
        $propertyAccessor = $this->initPropertyAccessor();
        $entity = $entityDto->getEntity();

        $class = get_class($entity);

        foreach ($params as $key => $value) {

            if (property_exists($entity, $key)) {

                $types = $propertyInfo->getTypes($class, $key);

                if ($types && count($types) > 0) {
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
                        try {
                            foreach ($items as $item) {
                                if ($entity_get = $this->manager
                                    ->getRepository($collectionValueType[0]->getClassName())
                                    ->find($item)) {
                                    $final_items[] = $entity_get;
                                }

                            }
                            $value = $final_items;
                        } catch (\Exception $e) {
                        }
                    }

                } else {
                    if ($this->types && $this->types->getClassName()) {
                        try {
                            if ($this->isDateOrTime()) {
                                $value = new \DateTimeImmutable($value);
                            } else {
                                if ($value_one = $this->manager->getRepository($this->types->getClassName())->find($value)) {
                                    $value = $value_one;
                                }
                            }
                        } catch (\Exception $exception) {
                        }
                    } else {
                        try {
                            if ($this->isDateOrTime()) {
                                $value = new \DateTimeImmutable($value);
                            }
                        } catch (\Exception $exception) {
                        }
                    }
                }

                try {
                    $propertyAccessor->setValue($entity, $key, $value);
                } catch (\Exception $exception) {
                    $entityDto->setError($key, $exception->getMessage());
                }
            }
        }

        $entityDto->setEntity($entity);

        return $entityDto;
    }

    private function isDateOrTime(): bool
    {
        return str_contains($this->types?->getClassName(), 'Date') ||
            str_contains($this->types?->getClassName(), 'Time');
    }
}
