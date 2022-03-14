<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Form\Extension\HoneyPot\HoneyPotExtension;
use Rareloop\Lumberjack\Form\Extension\InvalidFeedback\InvalidFeedbackExtension;
use Rareloop\Lumberjack\Form\Extension\MagicQuotes\MagicQuotesExtension;
use Rareloop\Lumberjack\Form\Extension\Sanitizer\SanitizerExtension;
use Rareloop\Lumberjack\Form\Extension\Recaptcha\RecaptchaExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
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

            $form_reflection = new \ReflectionClass(Forms::class);
            $filename = $form_reflection->getFileName();
            if ($filename) {
                $translations = \dirname($filename) . '/Resources/translations/validators.fr.xlf';
                $translator->addResource('xlf', $translations, $this->app->get('locale.short'));
            }
            $validator_reflection = new \ReflectionClass(Validation::class);
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

        $this->app->singleton('form.csrf_manager', function () {
            if (!$this->app->has('session')) {
                throw new \RuntimeException('You must register a session service provider to use the CSRF extension.');
            }

            $csrfGenerator = new UriSafeTokenGenerator();
            $csrfStorage = new SessionTokenStorage($this->app->get('session'));

            return new CsrfTokenManager($csrfGenerator, $csrfStorage);
        });

        $this->app->singleton('form.extensions', function () {
            $extensions = [];
            if ($this->app->has('validator')) {
                $extensions[] = new ValidatorExtension($this->app->get('validator'));
            }
            $extensions[] = new InvalidFeedbackExtension();
            $extensions[] = new MagicQuotesExtension();
            $extensions[] = new SanitizerExtension();

            $honeypot_config = $this->app->get('config')->get('forms.honeypot', []);
            $extensions[] = new HoneyPotExtension($honeypot_config);

            $recaptcha_config = $this->app->get('config')->get('forms.recaptcha', []);
            if(!empty($recaptcha_config['site_key']) && !empty($recaptcha_config['secret_key'])) {
                $extensions[] = new RecaptchaExtension(
                    $recaptcha_config['site_key'],
                    $recaptcha_config['secret_key'],
                    $recaptcha_config['global'],
                    $recaptcha_config['score_threshold'] ?? null,
                    $recaptcha_config['field'] ?? null,
                    \__('Une erreur a eu lieu lors de la validation du captcha, veuillez soumettre à nouveau le formulaire.', 'regilait')
                );
            }

            $extensions[] = new CsrfExtension($this->app->get('form.csrf_manager'));

            return $extensions;
        });

        $this->app->singleton('form.default_theme', $this->app->get('config')->get('form.default_theme', null));
    }

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
        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $filename = $appVariableReflection->getFileName();
        if (!$filename) {
            return $loader;
        }
        $vendorTwigBridgeDirectory = \dirname($filename);
        $loader->addPath($vendorTwigBridgeDirectory . '/Resources/views/Form');

        return $loader;
    }

    /**
     * Add the form extension to the Twig environment
     */
    public function addTwigExtension(Environment $twig): Environment
    {
        $defaultFormTheme = $this->app->get('form.default_theme') ?? 'bootstrap_5_layout.html.twig';

        $formEngine = new TwigRendererEngine([$defaultFormTheme], $twig);
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
}
