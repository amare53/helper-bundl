<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 12:43 AM
 */

namespace Amare53\HelperBundle\Helper;

use Amare53\HelperBundle\Contracts\EntityToJsonInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class EntityToJson implements EntityToJsonInterface
{

    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function toJson(mixed $entity, string|null $groups = null): mixed
    {

        if (is_null($groups)) {
            return json_decode($this->serializer->serialize($entity, 'json'));
        }

        return json_decode($this->serializer->serialize($entity, 'json', ['groups' => $groups]));

    }
}
