<?php

/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 8/19/21
 * Time: 3:16 PM
 */
namespace Amare53\HelperBundle\Dto;
use \Amare53\HelperBundle\Contracts\EntityDtoInteface;

class EntityDto implements EntityDtoInteface
{

    private array $errors = [];
    private mixed $entity = null;

    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setError(string $key, string $value): self
    {
        $this->errors[$key] = $value;
        return $this;
    }

    public function getEntity(): mixed
    {
        return $this->entity;
    }

    public function setEntity(mixed $entity): self
    {
        $this->entity = $entity;
        return $this;
    }
}
