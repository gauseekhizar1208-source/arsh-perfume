<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business-rule failure with a message that is safe to show to the customer / administrator
 * (out of stock, empty cart, invalid status change, payment mismatch...).
 */
class CheckoutException extends RuntimeException
{
}
