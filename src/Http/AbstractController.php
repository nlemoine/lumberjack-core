<?php

namespace Rareloop\Lumberjack\Http;

use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use Middlewares\Minifier;
use Psr\Container\ContainerInterface;
use Psr\Link\LinkInterface;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use WP_Query;

abstract class AbstractController implements MiddlewareAwareInterface
{
    use MiddlewareAwareTrait;

    protected ContainerInterface $container;

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

    public function handleQuery(WP_Query $query)
    {
    }

    public function enqueueAssets()
    {
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

    /**
     * Undocumented function
     *
     * @param array|string $templates
     * @param integer $status
     */
    protected function render($templates, array $context = [], int $status = 200): TimberResponse
    {
        return new TimberResponse($templates, $context, $status);
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
     * Gets a container service by its id.
     *
     * @return object The service
     */
    protected function has(string $id)
    {
        return $this->container->has($id);
    }

    /**
     * Generate URL.
     */
    protected function generateUrl(string $route, array $parameters = [], bool $relative = true): string
    {
        return $this->container->get('router')->generate($route, $parameters, $relative);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like Bundle\BlogBundle\Controller\PostController::indexAction)
     */
    protected function forward(string $controller): TimberResponse
    {
        $controller = $this->container->get($controller);
        return $controller->handle();
    }

    /**
     * Adds a Link HTTP header to the current response.
     *
     * @see https://tools.ietf.org/html/rfc5988
     */
    protected function addLink(LinkInterface $link): void
    {
        if (null === $linkProvider = $request->attributes->get('_links')) {
            $request->attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }

        $request->attributes->set('_links', $linkProvider->withLink($link));
    }
}
