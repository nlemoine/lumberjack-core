<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey;
use Mockery;
use phpmock\Mock;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Providers\ServiceProvider;

class ApplicationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        Monkey\setUp();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mock::disableAll();
        parent::tearDown();
    }

    public function testBasePathIsSetInContainerWhenBasepathPassedToConstructor()
    {
        $app = new Application('/base/path');

        $this->assertSame('/base/path', $app->basePath());
        $this->assertSame('/base/path', $app->get('path.base'));
    }

    public function testConfigPathIsSetInContainerWhenBasepathPassedToConstructor()
    {
        $app = new Application('/base/path');

        $this->assertSame('/base/path/config', $app->configPath());
        $this->assertSame('/base/path/config', $app->get('path.config'));
    }

    public function testCanBindAValue()
    {
        $app = new Application();

        $app->bind('app.environment', 'production');

        $this->assertSame('production', $app->get('app.environment'));
    }

    public function testCanDetermineIfSomethingHasBeenBound()
    {
        $app = new Application();

        $this->assertFalse($app->has('app.environment'));
        $app->bind('app.environment', 'production');
        $this->assertTrue($app->has('app.environment'));
    }

    public function testCanBindAnObject()
    {
        $app = new Application();
        $object = new TestInterfaceImplementation();

        $app->bind('test', $object);

        $this->assertSame($object, $app->get('test'));
    }

    public function testCanBindAnObjectAndAlwaysGetTheSameInstanceBack()
    {
        $app = new Application();
        $object = new TestInterfaceImplementation();

        $app->bind(TestInterface::class, $object);

        $this->assertSame($app->get(TestInterface::class), $app->get(TestInterface::class));
    }

    public function testCanBindAConcreteClassToAnInterface()
    {
        $app = new Application();

        $app->bind(TestInterface::class, TestInterfaceImplementation::class);
        $object = $app->get(TestInterface::class);

        $this->assertNotNull($object);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object);
    }

    public function testCanBindUsingClosure()
    {
        $app = new Application();
        $count = 0;

        $app->bind(TestInterface::class, function () use (&$count) {
            $count++;
            return new TestInterfaceImplementation();
        });

        $object = $app->get(TestInterface::class);

        $this->assertSame(1, $count);
        $this->assertNotNull($object);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object);
    }

    public function testCanBindUsingClosureAndGetDependenciesInjected()
    {
        $app = new Application();
        $count = 0;

        $app->bind(TestSubInterface::class, TestSubInterfaceImplementation::class);
        $app->bind(TestInterface::class, function (TestSubInterface $foo) use (&$count) {
            $this->assertInstanceOf(TestSubInterfaceImplementation::class, $foo);
            $count++;
            return new TestInterfaceImplementation();
        });

        $object = $app->get(TestInterface::class);

        $this->assertSame(1, $count);
        $this->assertNotNull($object);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object);
    }

    public function testCanBindASingletonConcreteClassToAnInterface()
    {
        $app = new Application();

        $app->singleton(TestInterface::class, TestInterfaceImplementation::class);

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertSame($object1, $object2);
        $this->assertNotNull($object1);
        $this->assertNotNull($object2);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object1);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object2);
    }

    public function testCanBindASingletonConcreteClassWithConstructorParamsToAnInterface()
    {
        $app = new Application();

        $app->singleton(TestInterface::class, TestInterfaceImplementationWithConstructorParams::class);

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertSame($object1, $object2);
        $this->assertNotNull($object1);
        $this->assertNotNull($object2);
        $this->assertInstanceOf(TestInterfaceImplementationWithConstructorParams::class, $object1);
        $this->assertInstanceOf(TestInterfaceImplementationWithConstructorParams::class, $object2);
    }

    public function testCanBindASingletonWithClosure()
    {
        $app = new Application();
        $count = 0;

        $app->singleton(TestInterface::class, function () use (&$count) {
            $count++;
            return new TestInterfaceImplementation();
        });

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertSame(1, $count);
        $this->assertSame($object1, $object2);
        $this->assertNotNull($object1);
        $this->assertNotNull($object2);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object1);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object2);
    }

    public function testCanBindASingletonAndGetDependenciesInjected()
    {
        $app = new Application();
        $count = 0;

        $app->bind(TestSubInterface::class, TestSubInterfaceImplementation::class);

        $app->singleton(TestInterface::class, function (TestSubInterface $foo) use (&$count) {
            $this->assertInstanceOf(TestSubInterfaceImplementation::class, $foo);
            $count++;
            return new TestInterfaceImplementation();
        });

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertSame(1, $count);
        $this->assertSame($object1, $object2);
        $this->assertNotNull($object1);
        $this->assertNotNull($object2);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object1);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object2);
    }

    public function testAppShouldBeBoundIntoTheContainerOnConstruction()
    {
        $app = new Application();

        $this->assertSame($app, $app->get(Application::class));
    }

    public function testCanCreateAClassThatHasNotBeenRegistered()
    {
        $app = new Application();
        $app->bind(TestInterface::class, TestInterfaceImplementation::class);

        $object = $app->get(NotRegisteredInContainer::class);

        $this->assertInstanceOf(NotRegisteredInContainer::class, $object);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object->param);
    }

    public function testCanMakeAClassWithAdditionalParamsForTheConstructor()
    {
        $app = new Application();
        $app->bind(TestInterface::class, TestInterfaceImplementation::class);

        $object = $app->make(RequiresAdditionalConstructorParams::class, [
            'param1' => 123,
            'param2' => 'abc',
        ]);

        $this->assertInstanceOf(RequiresAdditionalConstructorParams::class, $object);
        $this->assertInstanceOf(TestInterfaceImplementation::class, $object->param);
        $this->assertSame(123, $object->param1);
        $this->assertSame('abc', $object->param2);
    }

    public function testMakeProducesUniqueInstancesOfTheBoundObject()
    {
        $app = new Application();
        $app->bind(TestInterface::class, TestInterfaceImplementation::class);

        $object1 = $app->make(TestInterface::class);
        $object2 = $app->make(TestInterface::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testUsingBindDoesNotProduceASingleton()
    {
        $app = new Application();
        $app->bind(TestInterface::class, TestInterfaceImplementation::class);

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testGetDoesNotProduceASingletonWhenTheKeyHasNotBeenPreviouslyBoundToTheContainer()
    {
        $app = new Application();

        $object1 = $app->get(TestInterfaceImplementation::class);
        $object2 = $app->get(TestInterfaceImplementation::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testUsingBindWithClosureDoesNotProduceASingleton()
    {
        $count = 0;
        $app = new Application();
        $app->bind(TestInterface::class, function () use (&$count) {
            $count++;
            return new TestInterfaceImplementation();
        });

        $object1 = $app->get(TestInterface::class);
        $object2 = $app->get(TestInterface::class);

        $this->assertNotSame($object1, $object2);
        $this->assertSame(2, $count);
    }

    public function testCanRegisterAServiceProvider()
    {
        $app = new Application();
        $app->register(TestServiceProvider::class);

        $providers = $app->getLoadedProviders();

        $this->assertSame(1, \count($providers));
        $this->assertInstanceOf(TestServiceProvider::class, $providers[0]);
    }

    public function testRegisteredServiceProviderIsReturnedByRegister()
    {
        $app = new Application();

        $provider = $app->register(TestServiceProvider::class);

        $this->assertInstanceOf(TestServiceProvider::class, $provider);
    }

    public function testCanRetrieveARegisteredServiceProvider()
    {
        $app = new Application();

        $provider = $app->register(TestServiceProvider::class);

        $this->assertInstanceOf(TestServiceProvider::class, $app->getProvider(TestServiceProvider::class));
        $this->assertSame($provider, $app->getProvider(TestServiceProvider::class));
    }

    public function testCanRetrieveARegisteredServiceProviderByObject()
    {
        $app = new Application();

        $provider = $app->register(TestServiceProvider::class);

        $this->assertInstanceOf(TestServiceProvider::class, $app->getProvider($provider));
        $this->assertSame($provider, $app->getProvider($provider));
    }

    public function testCanNotRegisterTheSameServiceProviderTwice()
    {
        $app = new Application();

        $provider1 = $app->register(TestServiceProvider::class);
        $provider2 = $app->register(TestServiceProvider::class);

        $providers = $app->getLoadedProviders();

        $this->assertSame(1, \count($providers));
        $this->assertInstanceOf(TestServiceProvider::class, $providers[0]);
        $this->assertSame($provider1, $provider2);
    }

    public function testServiceProvidersWithoutRegisterFunctionsDontCauseAnException()
    {
        $app = new Application();
        $app->register(EmptyServiceProvider::class);

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testCanRegisterServiceProviderFromAnObject()
    {
        $app = new Application();
        $app->register(new TestServiceProvider($app));

        $providers = $app->getLoadedProviders();

        $this->assertSame(1, \count($providers));
        $this->assertInstanceOf(TestServiceProvider::class, $providers[0]);
    }

    public function testRegisteredServiceProvidersHaveTheirRegisterFunctionCalled()
    {
        $app = new Application();
        $provider = Mockery::mock(TestServiceProvider::class, [$app]);
        $provider->shouldReceive('register')->once();

        $app->register($provider);
    }

    public function testCallingBootOnAppShouldCallBootOnAllRegisteredServiceProviders()
    {
        $app = new Application();
        $provider = Mockery::mock(TestServiceProvider::class, [$app]);
        $provider->shouldReceive('register');
        $provider->shouldReceive('boot')->once();
        $app->register($provider);

        $app->boot();
    }

    public function testCallingBootMultipleTimesShouldNotFireBootOnServiceProvidersMoreThanOnce()
    {
        $app = new Application();
        $provider = Mockery::mock(TestServiceProvider::class, [$app]);
        $provider->shouldReceive('register');
        $provider->shouldReceive('boot')->once();
        $app->register($provider);

        $app->boot();
        $app->boot();
    }

    public function testBootShouldResolveDependenciesFromContainerOnServiceProviders()
    {
        $app = new Application();
        $app->bind(TestInterface::class, TestInterfaceImplementation::class);
        $provider = new TestBootServiceProvider($app);
        $count = 0;

        $provider->addBootCallback(function (array $args) use (&$count, $app) {
            $count++;
            $this->assertInstanceOf(Application::class, $args[0]);
            $this->assertSame($app, $args[0]);
            $this->assertInstanceOf(TestInterfaceImplementation::class, $args[1]);
        });

        $app->register($provider);

        $app->boot();

        $this->assertSame(1, $count);
    }

    public function testServicesRegisteredAfterBootShouldHaveTheirBootMethodCalledStraightAway()
    {
        $app = new Application();
        $provider = Mockery::mock(TestServiceProvider::class, [$app]);
        $provider->shouldReceive('register');
        $provider->shouldReceive('boot')->once();

        $app->boot();
        $app->register($provider);
    }

    public function testIsBootedReturnsFalseBeforeBootMethodHasBeenCalled()
    {
        $app = new Application();

        $this->assertFalse($app->isBooted());
    }

    public function testIsBootedReturnsTrueAfterBootMethodHasBeenCalled()
    {
        $app = new Application();

        $app->boot();

        $this->assertTrue($app->isBooted());
    }

    public function testCanBootstrapTheAppWithAnArrayOfBootstrappers()
    {
        $app = new Application();
        $count = 0;
        $tester = new BootstrapperBootstrapTester(function () use (&$count) {
            $count++;
        });
        $app->bind(BootstrapperBootstrapTester::class, $tester);

        $app->bootstrapWith([TestBootstrapper1::class, TestBootstrapper2::class]);

        $this->assertSame(2, $count);
    }

    public function testRunningInConsoleReturnsTrueForCli()
    {
        $mock = $this->createPhpSapiNameMock('cli', 'Rareloop\Lumberjack');
        $mock->enable();
        $app = new Application();

        $this->assertTrue($app->runningInConsole());
    }

    public function testRunningInConsoleReturnsTrueForPhpdbg()
    {
        $mock = $this->createPhpSapiNameMock('phpdbg', 'Rareloop\Lumberjack');
        $mock->enable();
        $app = new Application();

        $this->assertTrue($app->runningInConsole());
    }

    public function testCanTestIfRequestHasBeenHandled()
    {
        $app = new Application();

        $this->assertFalse($app->hasRequestBeenHandled());

        $app->requestHasBeenHandled();

        $this->assertTrue($app->hasRequestBeenHandled());
    }

    public function testCallingDetectWhenRequestHasNotBeenHandledAddsActions()
    {
        $app = new Application();

        $app->detectWhenRequestHasNotBeenHandled();

        $this->assertTrue(\has_action('wp_footer'));
        $this->assertTrue(\has_action('shutdown'));
    }

    private function createPhpSapiNameMock($value, $namespace)
    {
        $builder = new MockBuilder();

        $builder->setNamespace($namespace)
            ->setName('php_sapi_name')
            ->setFunction(
                function () use ($value) {
                    return $value;
                }
            );

        return $builder->build();
    }
}

class BootstrapperBootstrapTester
{
    public function __construct($callback)
    {
        $this->callback = $callback;
    }
}

abstract class TestBootstrapperBase
{
    public function __construct(BootstrapperBootstrapTester $tester)
    {
        $this->tester = $tester;
    }

    public function bootstrap(Application $app)
    {
        \call_user_func($this->tester->callback);
    }
}

class TestBootstrapper1 extends TestBootstrapperBase
{
}

class TestBootstrapper2 extends TestBootstrapperBase
{
}

interface TestInterface
{
}

class TestInterfaceImplementation implements TestInterface
{
}

class TestInterfaceImplementationWithConstructorParams implements TestInterface
{
    public function __construct(TestServiceProvider $provider)
    {
    }
}

interface TestSubInterface
{
}

class TestSubInterfaceImplementation implements TestSubInterface
{
}

class TestServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
    }
}

class EmptyServiceProvider extends ServiceProvider
{
}

class TestBootServiceProvider extends ServiceProvider
{
    private $bootCallback;

    public function register()
    {
    }

    public function boot(Application $app, TestInterface $test)
    {
        if (isset($this->bootCallback)) {
            \call_user_func($this->bootCallback, \func_get_args());
        }
    }

    public function addBootCallback(\Closure $callback)
    {
        $this->bootCallback = $callback;
    }
}

class NotRegisteredInContainer
{
    public $param;

    public function __construct(TestInterface $test)
    {
        $this->param = $test;
    }
}

class RequiresAdditionalConstructorParams
{
    public $param;

    public $param1;

    public $param2;

    public function __construct(TestInterface $test, $param1, $param2)
    {
        $this->param = $test;
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}
