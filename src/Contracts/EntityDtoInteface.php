<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 8/19/21
 * Time: 3:11 PM
 */

namespace Amare53\HelperBundle\Contracts;

interface EntityDtoInteface
{
    public function hasError(): bool;
    public function getErrors(): array;
    public function setError(string $key,string $value): void;
    public function getEntity(): mixed;
    public function setEntity(mixed $entity): void;
}
