<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 6/9/21
 * Time: 11:43 AM
 */
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Amare53\HelperBundle\Contracts\EntityParamsInterface;
use Amare53\HelperBundle\Contracts\EntityToJsonInterface;
use Amare53\HelperBundle\Helper\ArrayToEntity;
use Amare53\HelperBundle\Helper\EntityToJson;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use \Amare53\HelperBundle\Contracts\PaginatorInterface;
use \Amare53\HelperBundle\Paginator\KnpPaginator;
use \Knp\Component\Pager\PaginatorInterface as KnInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults();

    $services->set(PaginatorInterface::class)
        ->class(KnpPaginator::class)
        ->args([
            new Reference(KnInterface::class),
            new Reference(RequestStack::class)
        ]);

    $services
        ->set(EntityToJsonInterface::class)
        ->class(EntityToJson::class)
        ->arg(0, new Reference(SerializerInterface::class));

    $services->set(EntityParamsInterface::class)
        ->class(ArrayToEntity::class)
        ->arg(0, new Reference('doctrine.orm.entity_manager'));
};
