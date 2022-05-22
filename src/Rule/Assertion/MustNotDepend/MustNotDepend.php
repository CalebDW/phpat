<?php

namespace PHPat\Rule\Assertion\MustNotDepend;

use PHPat\Selector\Classname;
use PHPat\Selector\SelectorInterface;
use PHPat\Statement\Builder\StatementBuilderFactory;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule as PHPStanRule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

abstract class MustNotDepend implements PHPStanRule
{
    /** @var array<array{SelectorInterface, array<SelectorInterface>}> */
    protected array $statements;

    private ReflectionProvider $reflectionProvider;

    public function __construct(StatementBuilderFactory $statementBuilderFactory, ReflectionProvider $reflectionProvider)
    {
        $this->statements = $statementBuilderFactory->create(__CLASS__)->build();
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $targets = $this->extractTargetClassNames($node, $scope);

        if (!$this->ruleApplies($scope, $targets)) {
            return [];
        }

        return $this->validateGetErrors($scope, $targets);
    }

    /**
     * @return iterable<class-string>
     */
    abstract protected function extractTargetClassNames(Node $node, Scope $scope): iterable;

    /**
     * @param iterable<class-string> $targets
     */
    protected function ruleApplies(Scope $scope, iterable $targets): bool
    {
        if (empty($targets) || !($scope->isInClass())) {
            return false;
        }

        foreach ($targets as $target) {
            if (!(new Classname($target))->matches($scope->getClassReflection())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param iterable<class-string> $targets
     * @return array<RuleError>
     * @throws \PHPStan\ShouldNotHappenException
     */
    protected function validateGetErrors(Scope $scope, iterable $targets): array
    {
        $subject = $scope->getClassReflection();
        $errors = [];

        foreach ($this->statements as [$selector, $ruleTargets]) {
            if (!$selector->matches($subject)) {
                continue;
            }

            foreach ($ruleTargets as $ruleTarget) {
                foreach ($targets as $target) {
                    $t = $this->reflectionProvider->getClass($target);
                    if ($ruleTarget->matches($t)) {
                        $errors[] = RuleErrorBuilder::message(
                            sprintf(
                                '%s must not depend on %s',
                                $subject->getName(),
                                $target
                            )
                        )->build();
                    }
                }
            }
        }

        return $errors;
    }
}
