<?php
/**
 * @author Aime Nzolo
 * Created by IntelliJ IDEA.
 * Date: 8/9/21
 * Time: 12:06 AM
 */
namespace Amare53\HelperBundle\DependencyInjection;


use Amare53\HelperBundle\Doctrine\DQLDate;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class Amare53HelperExtension extends Extension implements PrependExtensionInterface
{

    public function load(array $configs, ContainerBuilder $container)
    {

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        try {
            $loader->load('services.php');
        } catch (\Exception $e) {
        }

    }

    public function prepend(ContainerBuilder $container)
    {
        $container->loadFromExtension('doctrine', array(
            'orm' => array(
                // ...
                'dql' => array(
                    'datetime_functions' => array(
                        'date' => DQLDate::class,
                        'DATE' => DQLDate::class,
                    ),
                ),
            ),
        ));
    }
}
