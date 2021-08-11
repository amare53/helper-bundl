<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/9/21
 * Time: 11:43 AM
 */
namespace Amare53\HelperBundle\Paginator;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PageOutOfBoundException extends BadRequestHttpException
{
}
