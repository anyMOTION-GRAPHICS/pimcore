<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting;

use Pimcore\Targeting\Condition\DataProviderDependentConditionInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionMatcher implements ConditionMatcherInterface
{
    /**
     * @var DataProviderLocatorInterface
     */
    private $dataProviders;

    /**
     * @var ConditionFactoryInterface
     */
    private $conditionFactory;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(
        DataProviderLocatorInterface $dataProviders,
        ConditionFactoryInterface $conditionFactory
    )
    {
        $this->dataProviders      = $dataProviders;
        $this->conditionFactory   = $conditionFactory;
        $this->expressionLanguage = new ExpressionLanguage(); // TODO
    }

    /**
     * @inheritdoc
     */
    public function match(VisitorInfo $visitorInfo, array $conditions): bool
    {
        $parts  = [];

        $values = [];
        $valueIndex = 1;

        foreach ($conditions as $conditionConfig) {
            if (!empty($parts)) {
                $parts[] = $this->normalizeOperatorValue($conditionConfig['operator']);
            }

            if ($conditionConfig['bracketLeft']) {
                $parts[] = '(';
            }

            $valueKey          = $conditionConfig['type'] . '_' . $valueIndex++;
            $values[$valueKey] = $this->matchCondition($visitorInfo, $conditionConfig);

            $parts[] = $valueKey;

            if ($conditionConfig['bracketRight']) {
                $parts[] = ')';
            }
        }

        $expression = implode(' ', $parts);
        $result     = $this->expressionLanguage->evaluate($expression, $values);

        return (bool)$result;
    }

    private function normalizeOperatorValue(string $operator = null): string
    {
        if (empty($operator)) {
            $operator = '&&';
        }

        $mapping = [
            'and'     => '&&',
            'or'      => '||',
            'and_not' => '&& not'
        ];

        if (isset($mapping[$operator])) {
            $operator = $mapping[$operator];
        }

        return $operator;
    }

    private function matchCondition(VisitorInfo $visitorInfo, array $config): bool
    {
        $condition = $this->conditionFactory->build($config);

        // check prerequisites - e.g. a condition without a value
        // (= all values match) does not need to fetch provider data
        // as location or browser
        // TODO does unconfigured resolve to true or false?
        if (!$condition->canMatch()) {
            return true;
        }

        if ($condition instanceof DataProviderDependentConditionInterface) {
            foreach ($condition->getDataProviderKeys() as $dataProviderKey) {
                $dataProvider = $this->dataProviders->get($dataProviderKey);
                $dataProvider->load($visitorInfo);
            }
        }

        return $condition->match($visitorInfo);
    }
}
