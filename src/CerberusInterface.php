<?php
/**
 * @see       https://github.com/lansoweb/cerberus for the canonical source repository
 */

declare(strict_types=1);

namespace Los\Cerberus;

interface CerberusInterface
{
    public const CLOSED    = 0;
    public const OPEN      = 1;
    public const HALF_OPEN = 2;

    /**
     * Is the circuit available to connections.
     */
    public function isAvailable(string $serviceName = '') : bool;

    /**
     * Returns the current status of the circuit.
     *
     * @param string|null $serviceName The service name
     *
     * @return int CLOSED (0), OPEN (1) or HALF_OPEN (2)
     */
    public function getStatus(string $serviceName = '') : int;

    /**
     * Signals that the connection was failed, incrementing the failure count.
     *
     * @param string|null $serviceName The service name
     */
    public function reportFailure(string $serviceName = '') : void;

    /**
     * Signals that the connection was a success, reseting the failure count.
     *
     * @param string|null $serviceName The service name
     */
    public function reportSuccess(string $serviceName = '') : void;
}
