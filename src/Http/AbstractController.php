<?php

namespace Rareloop\Lumberjack\Http;

use Laminas\Diactoros\Response\JsonResponse;
use Middlewares\Minifier;
use Psr\Container\ContainerInterface;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Router\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

abstract class AbstractController extends Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        if (\in_array(\getenv('WP_ENV'), ['production', 'staging'], true)) {
            $this->middleware(Minifier::html());
        }

        if (!empty(\getenv('BASIC_AUTH_USER')) && !empty(\getenv('BASIC_AUTH_PASSWORD'))) {
            $this->middleware(new \Middlewares\BasicAuthentication([
                \getenv('BASIC_AUTH_USER') => \getenv('BASIC_AUTH_PASSWORD'),
            ]));
        }
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param null|mixed $data
     */
    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param null|mixed $data
     */
    protected function createFormBuilder($data = null, array $options = []): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createBuilder(FormType::class, $data, $options);
    }

    protected function render($template, $context, int $status = 200): TimberResponse
    {
        return new TimberResponse($template, $context, $status);
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param mixed $data
     */
    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Add flash message.
     *
     * @param string $message
     */
    protected function addFlash(string $type, string $message = null)
    {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the addFlash method if sessions are disabled.');
        }

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }

    /**
     * Gets a container service by its id.
     *
     * @return object The service
     */
    protected function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * Generate URL.
     *
     * @param bool $relative
     */
    protected function generateUrl(string $route, array $parameters = [], $relative = false): string
    {
        return $this->container->get('router.generator')->generateUrl($route, $parameters, $relative);
    }
}
