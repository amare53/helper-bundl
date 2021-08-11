<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 8/8/21
 * Time: 10:42 PM
 */

namespace Amare53\HelperBundle\Contracts;


interface EntityParamsInterface
{
    public function convert(array $params, mixed $entity): mixed;
}
