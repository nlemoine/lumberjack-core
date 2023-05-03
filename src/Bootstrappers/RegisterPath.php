<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Rareloop\Lumberjack\Application;

class RegisterPath
{
    public function bootstrap(Application $app)
    {
        // project
        $app->singleton('path.project', function () {
            $abspath = \untrailingslashit(ABSPATH);
            if (\is_file($abspath . '/../index.php')) {
                return \realpath($abspath . '/../..');
            }
            if (\is_file($abspath . '/index.php')) {
                return \dirname($abspath);
            }

            return null;
        });

        // root
        $app->singleton('path.root', function () {
            $abspath = \untrailingslashit(ABSPATH);
            if (\is_file($abspath . '/../index.php')) {
                return \realpath($abspath . '/..');
            }
            if (\is_file($abspath . '/index.php')) {
                return \realpath($abspath);
            }

            return null;
        });

        // cache
        $app->singleton('path.cache', function () use ($app) {
            $cachePath = $app->get('path.project') . '/var/cache';

            return $cachePath;
        });

        // log
        $app->singleton('path.log', function () use ($app) {
            $logPath = $app->get('path.project') . '/var/log';

            return \is_dir($logPath) ? $logPath : null;
        });

        // uploads
        $app->singleton('path.uploads', function () {
            $upload = \wp_get_upload_dir();

            return empty($upload['error']) ? \untrailingslashit($upload['basedir']) : null;
        });

        // theme
        $app->singleton('path.theme', function () {
            return \untrailingslashit(\get_template_directory());
        });

        // views
        $app->singleton('path.views', function () use ($app) {
            return $app->get('path.theme') . '/views';
        });

        // assets
        $app->singleton('path.assets', function () use ($app) {
            return $app->get('path.theme') . '/assets';
        });
        $app->singleton('path.languages', function () use ($app) {
            return $app->get('path.theme') . '/languages';
        });

        // URLS

        // home
        $app->singleton('url.home', function () {
            $homeUrl = \home_url('/');
            $path = \parse_url($homeUrl, PHP_URL_PATH);
            if ($path !== '/' && \is_string($path)) {
                $homeUrl = \str_replace($path, '', $homeUrl);
            }

            return \trailingslashit($homeUrl);
        });

        // uploads
        $app->singleton('url.uploads', function () {
            $upload = \wp_get_upload_dir();

            return empty($upload['error']) ? \untrailingslashit($upload['baseurl']) : null;
        });

        // theme
        $app->singleton('url.theme', function () {
            return \untrailingslashit(\get_template_directory_uri());
        });

        // assets
        $app->singleton('url.assets', function () use ($app) {
            return $app->get('url.theme') . '/assets';
        });
    }
}
