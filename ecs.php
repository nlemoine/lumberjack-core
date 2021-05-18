<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]]);

    // $services->set(NativeFunctionInvocationFixer::class)
    //     ->call('configure', [[
    //         'include' => [
    //             '@all',
    //         ],
    //         'scope' => 'namespaced'
    //     ]]);

    $services->set(BinaryOperatorSpacesFixer::class)
        ->call('configure', [[
            'operators' => ['=>' => 'align_single_space'],
        ]]);

    $containerConfigurator->import(SetList::PSR_12);
};
