<?php

namespace App\Exceptions;

use RuntimeException;

/** Coupon cannot be applied; the message explains why and is safe to show to the customer. */
class CouponException extends RuntimeException
{
}
