<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 8/9/21
 * Time: 12:06 AM
 */

namespace Amare53\HelperBundle\Contracts;


interface EntityToJsonInterface
{
    public function toJson(mixed $entity, string|null $groups = null): mixed;
}
