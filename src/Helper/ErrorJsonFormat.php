<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/30/20
 * Time: 10:43 AM
 */

namespace Amare53\HelperBundle\Helper;


use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class ErrorJsonFormat
{

    public static function getErrors(ConstraintViolationListInterface $list): array
    {
        $errors = [];
        foreach ($list as $item) {
            $errors[$item->getPropertyPath()] = $item->getMessage();
        }

        return $errors;
    }
}
