<?php

namespace App\Exceptions;

use RuntimeException;

/** A coupon code that can't be used; the message is shown to the customer as is. */
class InvalidCoupon extends RuntimeException {}
