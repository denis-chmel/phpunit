<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework;

use PHPUnit\Util\Filter;
use Throwable;

/**
 * Wraps Exceptions thrown by code under test.
 *
 * Re-instantiates Exceptions thrown by user-space code to retain their original
 * class names, properties, and stack traces (but without arguments).
 *
 * Unlike PHPUnit\Framework_\Exception, the complete stack of previous Exceptions
 * is processed.
 */
class ExceptionWrapper extends Exception
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var null|ExceptionWrapper
     */
    protected $previous;

    /**
     * @var Throwable
     */
    private $originalException;

    /**
     * @param Throwable $t
     */
    public function __construct(Throwable $t)
    {
        // PDOException::getCode() is a string.
        // @see https://php.net/manual/en/class.pdoexception.php#95812
        parent::__construct($t->getMessage(), (int) $t->getCode());

        $this->className = \get_class($t);
        $this->file      = $t->getFile();
        $this->line      = $t->getLine();
        $this->originalException = $t;

        $this->serializableTrace = $t->getTrace();

        foreach ($this->serializableTrace as $i => $call) {
            unset($this->serializableTrace[$i]['args']);
        }

        if ($t->getPrevious()) {
            $this->previous = new self($t->getPrevious());
        }
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = TestFailure::exceptionToString($this);

        if ($trace = Filter::getFilteredStacktrace($this)) {
            $string .= "\n" . $trace;
        }

        if ($this->previous) {
            $string .= "\nCaused by\n" . $this->previous;
        }

        return $string;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return ExceptionWrapper
     */
    public function getPreviousWrapped(): ?self
    {
        return $this->previous;
    }

    /**
     * @return Throwable
     */
    public function getOriginalException(): Throwable
    {
        return $this->originalException;
    }
}
