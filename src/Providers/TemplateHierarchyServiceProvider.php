<?php

namespace Rareloop\Lumberjack\Providers;

use Brain\Hierarchy\Finder\CallbackTemplateFinder;
use Brain\Hierarchy\QueryTemplate;
use Jawira\CaseConverter\Convert;

class TemplateHierarchyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('template_redirect', [$this, 'renderController']);
    }

    public function renderController()
    {
        $finder = new CallbackTemplateFinder([$this, 'resolveTemplate']);

        $queryTemplate = new QueryTemplate($finder);

        $controller = $queryTemplate->findTemplate(null);

        \add_filter('lumberjack_controller_namespace', function ($namespace) {
            return 'App\\Http\\Controllers\\';
        });
        \add_filter('lumberjack_controller_name', function ($controllerName) use ($controller) {
            return \pathinfo($controller, PATHINFO_FILENAME);
        });

        echo $queryTemplate->loadTemplate();
        exit();
    }

    public function resolveTemplate(string $template): string
    {
        if ($template === '404') {
            $template = 'error-404';
        }

        $template = new Convert(\pathinfo($template, PATHINFO_FILENAME) . '-controller');

        $controllerPath = \sprintf('%s/app/Http/Controllers/%s.php', $this->app->get('path.theme'), $template->toPascal());

        return \file_exists($controllerPath) ? $controllerPath : '';
    }
}
