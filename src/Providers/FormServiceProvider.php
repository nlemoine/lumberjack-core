<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Form\Extension\Hcaptcha\HcaptchaExtension;
use Rareloop\Lumberjack\Form\Extension\HoneyPot\HoneyPotExtension;
use Rareloop\Lumberjack\Form\Extension\InvalidFeedback\InvalidFeedbackExtension;
use Rareloop\Lumberjack\Form\Extension\MagicQuotes\MagicQuotesExtension;
use Rareloop\Lumberjack\Form\Extension\Recaptcha\RecaptchaExtension;
use Rareloop\Lumberjack\Form\Extension\RequestHandler\HttpExtension;
use Rareloop\Lumberjack\Form\Extension\Sanitizer\SanitizerExtension;
use Rareloop\Lumberjack\Form\FileUploader;
use ReflectionClass;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use WP_Error;

class FormServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TranslatorInterface::class, function () {
            $translator = new Translator($this->app->get('locale.short'));
            $translator->addLoader('xlf', new \Symfony\Component\Translation\Loader\XliffFileLoader());

            $form_reflection = new ReflectionClass(Forms::class);
            $filename = $form_reflection->getFileName();
            if ($filename) {
                $translations = \dirname($filename) . '/Resources/translations/validators.fr.xlf';
                $translator->addResource('xlf', $translations, $this->app->get('locale.short'));
            }
            $validator_reflection = new ReflectionClass(Validation::class);
            $filename = $validator_reflection->getFileName();
            if ($filename) {
                $translations = \dirname($filename) . '/Resources/translations/validators.fr.xlf';
                $translator->addResource('xlf', $translations, $this->app->get('locale.short'));
            }
            return $translator;
        });

        $this->app->singleton('form.wp_error_handler', function (FormInterface $form, WP_Error $errors) {
            foreach ($errors->get_error_messages() as $message) {
                $form->addError(new FormError($message));
            }

            return $form;
        });

        $this->app->singleton('validator', function () {
            return Validation::createValidatorBuilder()
                ->setTranslator($this->app->get(TranslatorInterface::class))
                ->addMethodMapping('loadValidatorMetadata')
                ->getValidator()
            ;
        });

        $this->app->singleton('form.factory', function () {
            $form_factory = Forms::createFormFactoryBuilder();
            foreach ($this->app->get('form.extensions') as $extension) {
                $form_factory->addExtension($extension);
            }

            return $form_factory->getFormFactory();
        });

        // $this->app->singleton('form.csrf_manager', function () {
        //     if (!$this->app->has(SessionInterface::class)) {
        //         throw new \RuntimeException('You must register a session service provider to use the CSRF extension.');
        //     }

        //     $csrfGenerator = new UriSafeTokenGenerator();
        //     $csrfStorage = new SessionTokenStorage($this->app->get(SessionInterface::class));

        //     return new CsrfTokenManager($csrfGenerator, $csrfStorage);
        // });

        $this->app->singleton('form.extensions', function () {
            $extensions = [];

            $extensions[] = new HttpExtension();
            if ($this->app->has('validator')) {
                $extensions[] = new ValidatorExtension($this->app->get('validator'));
            }
            $extensions[] = new InvalidFeedbackExtension();
            $extensions[] = new MagicQuotesExtension();
            $extensions[] = new SanitizerExtension();

            $honeypot_config = $this->getConfig('form.honeypot', []);
            $extensions[] = new HoneyPotExtension($honeypot_config);

            if ($this->app->get('form.recaptcha.site_key') && $this->app->get('form.recaptcha.secret_key')) {
                $recaptcha_config = $this->getConfig('form.recaptcha', []);
                $extensions[] = new RecaptchaExtension(
                    $this->app->get('form.recaptcha.site_key'),
                    $this->app->get('form.recaptcha.secret_key'),
                    $recaptcha_config['global'],
                    $recaptcha_config['score_threshold'] ?? null,
                    $recaptcha_config['field'] ?? null,
                    'Une erreur a eu lieu lors de la validation du captcha, veuillez soumettre à nouveau le formulaire.'
                );

                $form_themes = $this->app->get('form.form_themes');
                \array_unshift($form_themes, 'recaptcha3_widget.html.twig');
                $this->app->bind('form.form_themes', $form_themes);
            }

            if ($this->app->get('form.hcaptcha.site_key') && $this->app->get('form.hcaptcha.secret_key')) {
                $hcaptcha_config = $this->getConfig('form.hcaptcha', []);
                $extensions[] = new HcaptchaExtension(
                    $this->app->get('form.hcaptcha.site_key'),
                    $this->app->get('form.hcaptcha.secret_key'),
                    $hcaptcha_config['global'],
                    $hcaptcha_config['field'] ?? null,
                    'Une erreur a eu lieu lors de la validation du captcha, veuillez soumettre à nouveau le formulaire.'
                );

                $form_themes = $this->app->get('form.form_themes');
                \array_unshift($form_themes, 'hcaptcha_widget.html.twig');
                $this->app->bind('form.form_themes', $form_themes);
            }

            // $extensions[] = new CsrfExtension($this->app->get('form.csrf_manager'));

            return $extensions;
        });

        $this->app->bind('form.form_themes', $this->getConfig('form.themes', []));

        $this->app->singleton('form.recaptcha.site_key', function () {
            return $this->getConfig('form.recaptcha.site_key', []);
        });
        $this->app->singleton('form.recaptcha.secret_key', function () {
            return $this->getConfig('form.recaptcha.secret_key', []);
        });
        $this->app->singleton('form.hcaptcha.site_key', function () {
            return $this->getConfig('form.hcaptcha.site_key', []);
        });
        $this->app->singleton('form.hcaptcha.secret_key', function () {
            return $this->getConfig('form.hcaptcha.secret_key', []);
        });

        $this->app->singleton('form.paths', function () {
            return \array_filter([
                $this->getTwigBridgeFormPath(),
                $this->app->get('form.recaptcha.site_key') && $this->app->get('form.recaptcha.secret_key') ? $this->getRecaptchaExtensionPath() : null,
                $this->app->get('form.hcaptcha.site_key') && $this->app->get('form.hcaptcha.secret_key') ? $this->getHcaptchaExtensionPath() : null,
            ]);
        });

        $this->app->singleton('form.uploader.target_directory', function () {
            return $this->app->get('path.uploads');
        });

        $this->app->singleton(FileUploader::class, function () {
            return new FileUploader($this->app->get('form.uploader.target_directory'));
        });
        $this->app->singleton('form.uploader', $this->app->get(FileUploader::class));
    }

    /**
     * Undocumented function
     */
    public function boot()
    {
        $this->app->bind('app.request', function () {
            return \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        });

        \add_filter('timber/twig', [$this, 'addTwigExtension']);
        \add_filter('timber/loader/loader', [$this, 'addSymfonyFormThemePath']);
    }

    /**
     * Add Symfony form theme path
     */
    public function addSymfonyFormThemePath(FilesystemLoader $loader): FilesystemLoader
    {
        foreach ($this->app->get('form.paths') as $path) {
            $loader->addPath($path);
        }
        return $loader;
    }

    /**
     * Add the form extension to the Twig environment
     */
    public function addTwigExtension(Environment $twig): Environment
    {
        $form_themes = \array_merge(['bootstrap_5_layout.html.twig'], $this->app->get('form.form_themes'));

        $formEngine = new TwigRendererEngine($form_themes, $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => function () use ($formEngine) {
                return new FormRenderer($formEngine);
            },
        ]));
        $twig->addExtension(new FormExtension());
        if ($this->app->has(TranslatorInterface::class)) {
            $twig->addExtension(new TranslationExtension($this->app->get(TranslatorInterface::class)));
        }

        return $twig;
    }

    /**
     * Undocumented function
     */
    protected function getRecaptchaExtensionPath(): ?string
    {
        $recaptcha_reflection = new ReflectionClass(RecaptchaExtension::class);
        $filename = $recaptcha_reflection->getFileName();
        if (!$filename) {
            return null;
        }
        return \dirname($filename) . '/views';
    }

    /**
     * Undocumented function
     */
    protected function getHcaptchaExtensionPath(): ?string
    {
        $recaptcha_reflection = new ReflectionClass(HcaptchaExtension::class);
        $filename = $recaptcha_reflection->getFileName();
        if (!$filename) {
            return null;
        }
        return \dirname($filename) . '/views';
    }

    /**
     * Undocumented function
     */
    protected function getTwigBridgeFormPath(): ?string
    {
        $app_variable_reflection = new ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $filename = $app_variable_reflection->getFileName();
        if (!$filename) {
            return null;
        }
        return \dirname($filename) . '/Resources/views/Form';
    }
}
