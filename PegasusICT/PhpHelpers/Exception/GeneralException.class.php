<?php declare( strict_types = 1 );

namespace PegasusICT\PhpHelpers;

use \Exception;
use \ReflectionClass;
use \ReflectionException;
use \Throwable;
use PegasusICT\PhpHelpers\Logger as Log;

/**
 * Class GeneralException
 *
 * @package PegasusICT\PhpHelpers
 */
class GeneralException extends Exception {

    const EXCEPT_GENERAL          = 9000;
    const EXCEPT_ILLEGAL_ARG      = 9001;
    const EXCEPT_UNKNOWN_LANGUAGE = 9002;
    const EXCEPT_LABEL_MISSING    = 9003;

    /**
     * HtmlFactoryException constructor.
     *
     * @param string      $message
     * @param int         $code
     * @param Throwable   $previous
     *
     * @param string|null $callerClass
     * @param string|null $callerFunction
     *
     * @throws ReflectionException
     */
    public function __construct( $message = null, $code = 0, Throwable $previous = null, string $callerClass = null, string $callerFunction = null ) {
        $class = new ReflectionClass("HtmlFactoryException");
        $errorCodeText = array_flip($class->getConstants())[ $code ];

        $message  = $message ?? "Unknown problem encountered.";
        $message .= "\nErrorCode $code translates to $errorCodeText.";

        Log::getInstance(__CLASS__.debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[2]['class'])->critical( $callerFunction,get_class() . " thrown: " . $message );

        parent::__construct( $message, $code, $previous );
    }
}
