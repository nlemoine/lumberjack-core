<?php

namespace Rareloop\Lumberjack\Providers;

class PathServiceProvider extends ServiceProvider
{
    public function register()
    {
        // project
        $this->app->singleton('path.project', function () {
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
        $this->app->singleton('path.root', function () {
            $abspath = \untrailingslashit(ABSPATH);
            if (\is_file($abspath . '/../index.php')) {
                return \realpath($abspath . '/..');
            }
            if (\is_file($abspath . '/index.php')) {
                return \realpath($abspath);
            }

            return null;
        });

        // log
        $this->app->singleton('path.log', function ($app) {
            $logPath = $app->get('path.project') . '/var/log';

            return \is_dir($logPath) ? $logPath : null;
        });

        // uploads
        $this->app->singleton('path.uploads', function () {
            $upload = \wp_get_upload_dir();

            return empty($upload['error']) ? \untrailingslashit($upload['basedir']) : null;
        });

        // theme
        $this->app->singleton('path.theme', function () {
            return \untrailingslashit(\get_template_directory());
        });

        // assets
        $this->app->singleton('path.assets', function ($app) {
            return $app->get('path.theme') . '/assets';
        });
        $this->app->singleton('path.languages', function ($app) {
            return $app->get('path.theme') . '/languages';
        });

        // URLS

        // home
        $this->app->singleton('url.home', function () {
            $homeUrl = \home_url('/');
            $path = \parse_url($homeUrl, PHP_URL_PATH);
            if ($path !== '/') {
                $home_url = \str_replace($path, '', $homeUrl);
            }

            return \trailingslashit($homeUrl);
        });

        // uploads
        $this->app->singleton('url.uploads', function () {
            $upload = \wp_get_upload_dir();

            return empty($upload['error']) ? \untrailingslashit($upload['baseurl']) : null;
        });

        // theme
        $this->app->singleton('url.theme', function () {
            return \untrailingslashit(\get_template_directory_uri());
        });

        // assets
        $this->app->singleton('url.assets', function ($app) {
            return $app->get('url.theme') . '/assets';
        });
    }
}
