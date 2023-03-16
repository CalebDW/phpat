<?php

declare(strict_types=1);

namespace Tests\PHPat\unit\rules\ShouldBeFinal;

use PHPat\Configuration;
use PHPat\Rule\Assertion\Declaration\ShouldBeFinal\IsFinalRule;
use PHPat\Rule\Assertion\Declaration\ShouldBeFinal\ShouldBeFinal;
use PHPat\Selector\Classname;
use PHPat\Statement\Builder\StatementBuilderFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Tests\PHPat\fixtures\FixtureClass;
use Tests\PHPat\unit\FakeTestParser;

/**
 * @extends RuleTestCase<IsFinalRule>
 */
class ShowRuleNameFinalClassTest extends RuleTestCase
{
    public const RULE_NAME = 'test_FixtureClassShouldBeFinal';
    public function testRule(): void
    {
        $this->analyse(['tests/fixtures/FixtureClass.php'], [
            [sprintf('%s: %s should be final', self::RULE_NAME, FixtureClass::class), 31],
        ]);
    }

    protected function getRule(): Rule
    {
        $testParser = FakeTestParser::create(
            self::RULE_NAME,
            ShouldBeFinal::class,
            [new Classname(FixtureClass::class, false)],
            []
        );

        return new IsFinalRule(
            new StatementBuilderFactory($testParser),
            new Configuration(false, true),
            $this->createReflectionProvider(),
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }
}