<?php

namespace App\Domain\Payment\Exceptions;

use RuntimeException;

/**
 * The gateway responded successfully (HTTP-wise) but refused the request
 * at the business level — e.g. PayDunya's merchant KYC not yet validated
 * (response_code != "00"). Distinct from RequestException/ConnectionException
 * (bootstrap/app.php), which cover the HTTP call itself failing; this is
 * for a call that succeeded but said no. Same class of problem from the
 * user's point of view though, so rendered the same way.
 */
class PaymentGatewayException extends RuntimeException {}
