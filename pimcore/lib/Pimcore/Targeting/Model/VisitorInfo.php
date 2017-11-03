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

namespace Pimcore\Targeting\Model;

use Pimcore\Model\Tool\Targeting\Persona;
use Pimcore\Model\Tool\Targeting\Rule;
use Symfony\Component\HttpFoundation\Request;

class VisitorInfo implements \IteratorAggregate
{
    const VISITOR_ID_COOKIE_NAME = '_pc_vis';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string|null
     */
    private $visitorId;

    /**
     * Matched targeting rules
     *
     * @var Rule[]
     */
    private $targetingRules = [];

    /**
     * Applied personas
     *
     * @var Persona[]
     */
    private $personas = [];

    /**
     * @var array
     */
    private $data = [];

    public function __construct(Request $request, string $visitorId = null, array $data = [])
    {
        $this->request   = $request;
        $this->visitorId = $visitorId;
        $this->data      = $data;
    }

    public static function fromRequest(Request $request): self
    {
        $visitorId = $request->cookies->get(self::VISITOR_ID_COOKIE_NAME);

        return new static($request, $visitorId);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function hasVisitorId(): bool
    {
        return !empty($this->visitorId);
    }

    /**
     * @return string|null
     */
    public function getVisitorId()
    {
        return $this->visitorId;
    }

    /**
     * @return Rule[]
     */
    public function getTargetingRules(): array
    {
        return $this->targetingRules;
    }

    /**
     * @param Rule[] $targetingRules
     */
    public function setTargetingRules(array $targetingRules = [])
    {
        $this->targetingRules = [];
        foreach ($targetingRules as $targetingRule) {
            $this->addTargetingRule($targetingRule);
        }
    }

    public function addTargetingRule(Rule $targetingRule)
    {
        $this->targetingRules[] = $targetingRule;
    }

    /**
     * @return Persona[]
     */
    public function getPersonas(): array
    {
        return $this->personas;
    }

    /**
     * @param Persona[] $personas
     */
    public function setPersonas(array $personas = [])
    {
        $this->personas = [];
        foreach ($personas as $persona) {
            $this->addPersona($persona);
        }
    }

    public function addPersona(Persona $persona)
    {
        $this->personas[] = $persona;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
