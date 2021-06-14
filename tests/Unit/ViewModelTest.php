<?php

namespace Rareloop\Lumberjack\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\ViewModel;

class ViewModelTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testPublicMethodsAreSerialisedByToArray()
    {
        $viewModel = new TestViewModel();
        $data = $viewModel->toArray();

        $this->assertSame('bar', $data['foo']);
        $this->assertFalse(isset($data['toArray']));
        $this->assertFalse(isset($data['protectedFoo']));
        $this->assertFalse(isset($data['privateFoo']));
    }

    public function testPublicMethodsWithParamsAreNotSerialisedByToArray()
    {
        $viewModel = new TestMethodWithParamsViewModel();
        $data = $viewModel->toArray();

        $this->assertFalse(isset($data['foo']));
    }

    public function testStaticPublicMethodsAreNotSerialisedByToArray()
    {
        $viewModel = new TestStaticMethodViewModel();
        $data = $viewModel->toArray();

        $this->assertSame('bar', $data['foo']);
        $this->assertFalse(isset($data['staticFoo']));
    }

    public function testPublicPropertiesAreSerialisedByToArray()
    {
        $viewModel = new TestPropertiesViewModel();
        $data = $viewModel->toArray();

        $this->assertSame('bar', $data['foo']);
        $this->assertFalse(isset($data['toArray']));
        $this->assertFalse(isset($data['protectedFoo']));
        $this->assertFalse(isset($data['privateFoo']));
    }

    public function testStaticPublicPropertiesAreNotSerialisedByToArray()
    {
        $viewModel = new TestStaticPropertiesViewModel();
        $data = $viewModel->toArray();

        $this->assertSame('bar', $data['foo']);
        $this->assertFalse(isset($data['staticFoo']));
    }
}

class TestViewModel extends ViewModel
{
    public function foo()
    {
        return 'bar';
    }

    protected function protectedFoo()
    {
        return 'protected-bar';
    }

    private function privateFoo()
    {
        return 'private-bar';
    }
}

class TestMethodWithParamsViewModel extends ViewModel
{
    public function foo($param)
    {
        return 'bar';
    }
}

class TestStaticMethodViewModel extends ViewModel
{
    public function foo()
    {
        return 'bar';
    }

    public static function staticFoo()
    {
        return 'static-bar';
    }
}

class TestPropertiesViewModel extends ViewModel
{
    public $foo = 'bar';

    protected $protectedFoo = 'protected-foo';

    private $privateFoo = 'private-foo';
}

class TestStaticPropertiesViewModel extends ViewModel
{
    public $foo = 'bar';

    public static $staticFoo = 'static-bar';
}
