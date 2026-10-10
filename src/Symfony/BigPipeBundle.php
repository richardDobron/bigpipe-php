<?php

namespace dobron\BigPipe\Symfony;

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Symfony\EventListener\BigPipeSubscriber;
use dobron\BigPipe\Symfony\Twig\BigPipeExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Environment;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Integrates BigPipe into Symfony: a context per request (also in worker mode), the CSRF token,
 * responses a controller returns and the Twig functions.
 */
class BigPipeBundle extends AbstractBundle
{
    protected string $extensionAlias = 'bigpipe';

    private static ?RequestStack $requestStack = null;

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('csrf')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('token_id')
                            ->info('The id of the CSRF token sent to the browser, e.g. "bigpipe". Null sends none.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('header')->defaultValue('X-CSRF-TOKEN')->end()
                        ->scalarNode('param')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set(ContextHolder::class)
            ->public()
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set(BigPipeSubscriber::class)
            ->args([$config['csrf'], service('security.csrf.token_manager')->nullOnInvalid()])
            ->tag('kernel.event_subscriber');

        if (class_exists(Environment::class)) {
            $services->set(BigPipeExtension::class)->tag('twig.extension');
        }
    }

    public function boot(): void
    {
        $holder = $this->container->get(ContextHolder::class);
        self::$requestStack = $this->container->get('request_stack');

        BigPipe::setContextResolver(static fn () => $holder->context());
    }

    /**
     * @internal the current request, see SendsSymfonyResponse::isStreamRequested()
     */
    public static function currentRequest(): ?Request
    {
        return self::$requestStack?->getCurrentRequest();
    }
}
