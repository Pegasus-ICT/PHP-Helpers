<?php
declare( strict_types=1 );

namespace PegasusICT\PhpHelpers;

/**
 * Class TypeException
 *
 * @package PegasusICT\PhpHelpers
 */
class TypeException extends GeneralException {
    const EXCEPT_TYPE_ERROR = 2000;
    const EXCEPT_NO_URL     = 2001;
}