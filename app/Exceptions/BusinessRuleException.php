<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that is authorized but violates a business invariant
 * (duplicate invitation, last owner, expired activation link...).
 */
class BusinessRuleException extends RuntimeException {}
