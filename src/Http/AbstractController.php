<?php

namespace Rareloop\Lumberjack\Http;

use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Middleware\MiddlewareAwareInterface;
use League\Route\Middleware\MiddlewareAwareTrait;
use LogicException;
use Middlewares\BasicAuthentication;
use Middlewares\Minifier;
use Psr\Container\ContainerInterface;
use Psr\Link\LinkInterface;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Lumberjack\Router\Router;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use WP_Query;

abstract class AbstractController implements MiddlewareAwareInterface
{
    use MiddlewareAwareTrait;

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $disableMinification = $_ENV['DISABLE_HTML_MIN'] ?? false;
        if (\in_array(\getenv('WP_ENV'), ['production', 'staging'], true) && !$disableMinification) {
            $this->middleware(Minifier::html());
        }

        if (!empty(\getenv('BASIC_AUTH_USER')) && !empty(\getenv('BASIC_AUTH_PASSWORD'))) {
            $this->middleware(new BasicAuthentication([
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
    protected function render($templates, array $context = [], int $status = 200, array $headers = []): TimberResponse
    {
        return new TimberResponse($templates, $context, $status, $headers);
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
     * Returns a RedirectResponse to the given URL.
     */
    protected function redirectSafe(string $url, int $status = 302): RedirectResponse
    {
        $url = \wp_sanitize_redirect($url);

        $url = \wp_validate_redirect($url, \apply_filters('wp_safe_redirect_fallback', \admin_url(), $status));

        return new RedirectResponse($url, $status);
    }

    /**
     * Add flash message.
     */
    protected function addFlash(string $type, string $message = null)
    {
        if (!$this->container->has('session')) {
            throw new LogicException('You can not use the addFlash method if sessions are disabled.');
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
    protected function generateUrl(string $route, array $parameters = [], int|bool $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        if (!$this->container->has('router.generator')) {
            throw new LogicException('You can not use the generateUrl method if the router is not installed.');
        }
        $generator = $this->container->get('router.generator');
        if ($generator instanceof UrlGeneratorInterface) {
            return $generator->generate($route, $parameters, $referenceType);
        } elseif ($generator instanceof Router) {
            return $generator->generate($route, $parameters, $referenceType);
        }
        throw new LogicException('You can not use the generateUrl method if the router is not installed.');
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
