<?php
/**
 * @see       https://github.com/lansoweb/cerberus for the canonical source repository
 * @copyright Copyright (c) 2019 Leandro Silva
 * @license   https://github.com/lansoweb/cerberus/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Los\Cerberus;

use Psr\SimpleCache\CacheInterface;

use function time;

class Cerberus implements CerberusInterface
{
    /**
     * The storage object.
     *
     * @var CacheInterface
     */
    private $storage;

    /**
     * Maximum number of failures before open the circuit.
     *
     * @var int
     */
    private $maxFailures;

    /**
     * Number of seconds to change from OPEN to HALF OPEN and try the connection again.
     *
     * @var int
     */
    private $timeout;

    public function __construct(CacheInterface $storage, int $maxFailures = 5, int $timeout = 30)
    {
        $this->maxFailures = $maxFailures;
        $this->timeout     = $timeout;
        $this->storage     = $storage;
    }

    public function isAvailable(string $serviceName = '') : bool
    {
        return $this->getStatus($serviceName) !== CerberusInterface::OPEN;
    }

    public function getStatus(string $serviceName = '') : int
    {
        $failures = (int) $this->storage->get($serviceName . 'failures', 0);

        // Still has failures left
        if ($failures < $this->maxFailures) {
            return CerberusInterface::CLOSED;
        }

        $lastAttempt = $this->storage->get($serviceName . 'last_attempt', null);

        // This is the first attempt after a failure, open the circuit
        if ($lastAttempt === null) {
            $lastAttempt = time();
            $this->storage->set($serviceName . 'last_attempt', $lastAttempt);

            return CerberusInterface::OPEN;
        }

        // Reached maxFailures but has passed the timeout limit, so we can try again
        // We update the lastAttempt so only one call passes through
        if (time() - $lastAttempt >= $this->timeout) {
            $lastAttempt = time();
            $this->storage->set($serviceName . 'last_attempt', $lastAttempt);

            return CerberusInterface::HALF_OPEN;
        }

        return CerberusInterface::OPEN;
    }

    public function reportSuccess(string $serviceName = '') : void
    {
        $this->storage->set($serviceName . 'failures', 0);
    }

    public function reportFailure(string $serviceName = '') : void
    {
        $failures  = $this->storage->get($serviceName . 'failures', 0);
        $failures += 1;
        $this->storage->set($serviceName . 'failures', $failures);
    }
}
