<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/ecs.php',
    ])
    /**
     * Import
     * @see https://cs.symfony.com/doc/rules/index.html#import
     */
    ->withRules([
        FullyQualifiedStrictTypesFixer::class,
        NoLeadingImportSlashFixer::class,
        SingleImportPerStatementFixer::class,
    ])
    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
    ])
    ->withConfiguredRule(BinaryOperatorSpacesFixer::class, [
        'operators' => [
            '=>' => 'align_single_space',
        ],
    ])
    /**
     * NativeFunctionInvocation
     * @see https://cs.symfony.com/doc/rules/function_notation/native_function_invocation.html
     */
    ->withConfiguredRule(NativeFunctionInvocationFixer::class, [
        'include' => [
            '@all',
        ],
        'scope'  => 'namespaced',
        'strict' => true,
    ])
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
    )
    ->withSkip([
        DeclareStrictTypesFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        RemoveUselessDefaultCommentFixer::class,
        MethodChainingIndentationFixer::class,
    ])
;
