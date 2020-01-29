<?php declare( strict_types = 1 );

namespace PegasusICT\PhpHelpers;

/**
 * Class AttributeException
 *
 * @package PegasusICT\PhpHelpers
 */
class AttributeException extends GeneralException {
    // Attribute Exceptions
    const EXCEPT_ATTR_ERROR            = 3000;
    const EXCEPT_ATTR_ELEMENT_MISMATCH = 3001;
}
