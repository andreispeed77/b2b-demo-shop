<?php

namespace Pyz\Yves\Application\Communication;

use Generated\Yves\Factory;
use ProjectA\Shared\Application\Business\Application;
use ProjectA\Shared\Application\Business\Bootstrap;
use ProjectA\Shared\Library\Config;
use ProjectA\Shared\System\SystemConfig;
use ProjectA\Shared\Yves\YvesConfig;
use ProjectA\Yves\Customer\Business\Model\Security\SecurityServiceProvider;
use ProjectA\Yves\Library\Asset\AssetManager;
use ProjectA\Yves\Application\Business\Twig\YvesExtension;
use Pyz\Yves\Library\Silex\Provider\TrackingServiceProvider;
use ProjectA\Yves\Catalog\Business\Model\Category;
use ProjectA\Yves\Application\Business\ServiceProvider\CookieServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\MonologServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\SessionServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\StorageServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\ExceptionServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\TranslationServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\TwigServiceProvider;
use ProjectA\Yves\Application\Business\ServiceProvider\YvesLoggingServiceProvider;
use ProjectA\Shared\Application\Business\Routing\SilexRouter;
use Pyz\Yves\Application\Communication\ControllerProvider as ApplicationProvider;
use ProjectA\Yves\Cart\Communication\ControllerProvider as CartProvider;
use ProjectA\Yves\Checkout\Communication\ControllerProvider as CheckoutProvider;
use ProjectA\Yves\Customer\Communication\ControllerProvider as CustomerProvider;
use ProjectA\Yves\Newsletter\Communication\ControllerProvider as NewsletterProvider;
use ProjectA\Yves\Library\Tracking\Tracking;
use ProjectA\Shared\Application\Business\ServiceProvider\UrlGeneratorServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use ProjectA\Shared\Application\Business\ServiceProvider\RoutingServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class YvesBootstrap extends Bootstrap
{

    /**
     * @return Application
     */
    protected function getBaseApplication()
    {
        return new \ProjectA\Yves\Application\Business\Application();
    }

    /**
     * @param Application $app
     *
     * @return \Twig_Extension[]
     */
    protected function getTwigExtensions(Application $app)
    {
        $assetManager = new AssetManager($app['request_stack']);
        $yvesExtension = new YvesExtension($assetManager);

        return [
            $yvesExtension
        ];
    }

    /**
     * @param Application $app
     */
    protected function beforeBoot(Application $app)
    {
        $app['locale'] = \ProjectA_Shared_Library_Store::getInstance()->getCurrentLocale();
        if (\ProjectA_Shared_Library_Environment::isDevelopment()) {
            $app['profiler.cache_dir'] = \ProjectA_Shared_Library_Data::getLocalStoreSpecificPath('cache/profiler');
        }

        $proxies = Config::get(YvesConfig::YVES_TRUSTED_PROXIES);

        Request::setTrustedProxies($proxies);
    }

    /**
     * @param Application $app
     */
    protected function afterBoot(Application $app)
    {
        $app['monolog.level'] = Config::get(SystemConfig::LOG_LEVEL);
    }

    /**
     * @return \Silex\ServiceProviderInterface[]
     */
    protected function getServiceProviders()
    {
        $providers = [
            new ExceptionServiceProvider('\Pyz\Yves\Library\Controller\ExceptionController'),
            new YvesLoggingServiceProvider(),
            new MonologServiceProvider(),
            new CookieServiceProvider(),
            new SessionServiceProvider(),
            new UrlGeneratorServiceProvider(),
            new ServiceControllerServiceProvider(),
            new SecurityServiceProvider(),
            new RememberMeServiceProvider(),
            new RoutingServiceProvider(),
            new StorageServiceProvider(),
            new TranslationServiceProvider('ProjectA\Shared\Glossary\Code\Storage\StorageKeyGenerator'),
            new ValidatorServiceProvider(),
            new FormServiceProvider(),
            new TwigServiceProvider(),
            new TrackingServiceProvider()
        ];

        if (\ProjectA_Shared_Library_Environment::isDevelopment()) {
            $providers[] = new WebProfilerServiceProvider();
        }

        return $providers;
    }

    /**
     * @return \Silex\ControllerProviderInterface[]
     */
    protected function getControllerProviders()
    {
        $ssl = Config::get(YvesConfig::YVES_SSL_ENABLED);

        return [
            new ApplicationProvider(false),
            new CartProvider(false),
            new CheckoutProvider($ssl),
            new CustomerProvider($ssl),
            new NewsletterProvider(),
        ];
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\Routing\RouterInterface[]
     */
    protected function getRouters(Application $app)
    {
        return [
            Factory::getInstance()->createSetupModelRouterMonitoringRouter($app),
            Factory::getInstance()->createCmsModelRouterRedirectRouter($app),
            Factory::getInstance()->createCatalogModelRouterCatalogRouter($app),
            Factory::getInstance()->createProductExporterDependencyContainer()->createProductDetailRouter($app),
            Factory::getInstance()->createCmsModelRouterCmsRouter($app),
            /*
             * SilexRouter should come last, as it is not the fastest one if it can
             * not find a matching route (lots of magic)
             */
            new SilexRouter($app),
        ];
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function globalTemplateVariables(Application $app)
    {
        return [
            'categories' => Category::getCategoryTree($app->getStorageKeyValue()),
            'cartItemCount' => Factory::getInstance()->createCartModelSessionCartCount($app->getSession())->getCount(),
            'tracking' => Tracking::getInstance(),
        ];
    }
}
