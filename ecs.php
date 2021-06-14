<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/tests']);

    $containerConfigurator->import(SetList::COMMON);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::CLEAN_CODE);

    $services = $containerConfigurator->services();
    $services->set(\PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]]);

    $services->set(\PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer::class)
        ->call('configure', [[
            'include' => [
                '@all',
            ],
            'scope' => 'namespaced'
        ]]);

    $services->set(\PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class)
        ->call('configure', [[
            'operators' => ['=>' => 'align_single_space'],
        ]]);

    $services->remove(\PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer::class);
    $services->remove(\PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class);
    $services->remove(\Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer::class);
};
