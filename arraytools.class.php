<?php declare( strict_types = 1 );

namespace PegasusICT\PhpHelpers;

/**
 * Class ArrayTools
 *
 * PHP version ^7.3
 *
 * @package   PegasusICT\PhpHelpers
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2002-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 */
abstract class ArrayTools {
    public const ARRAY_IS       = 0b0000;
    public const ARRAY_IS_SEQ   = 0b0001;
    public const ARRAY_IS_ASSOC = 0b0010;
    public const ARRAY_IS_NUM   = 0b0100;
    public const ARRAY_IS_EMPTY = 0b1000;
    public const ARRAY_RESULTS  = [
        self::ARRAY_IS_SEQ   => "sequential",
        self::ARRAY_IS_NUM   => "numerical",
        self::ARRAY_IS_ASSOC => "associative",
        self::ARRAY_IS_EMPTY => "empty",
        self::ARRAY_IS       => "what?"
    ];
    /**
     * @param string|int|float $key
     * @param array            $array
     *
     * @return int
     */
    public static function indexOff( $key, array $array ): int {
        if( in_array( $key, $array ) ) {
            return array_flip( $array )[$key];
        }

        return -1;
    }


    /**
     * Alias for static call to logger
     *
     * @return Logger
     */
    private static function log() {
        return Logger::getInstance( __CLASS__,  null );
    }

    /**
     * Tells what kind of array we're dealing with
     * If told to perform a specific test, returns a boolean result.
     *
     * @param string $label name of the array
     * @param array  $array array to be tested
     * @param int    $test  which test to perform
     *
     * @return int
     */
    public static function testArray( string $label, array $array, int $test = self::ARRAY_IS ) {
        self::log()->debug( __FUNCTION__, "test = " . self::ARRAY_RESULTS[$test ] );
        $result = self::ARRAY_IS_NUM;
        if( array() === $array ) {
            $result = self::ARRAY_IS_EMPTY;
        }
        elseif( array_keys( $array ) === range( 0, count( $array ) - 1 ) ) {
            $result = self::ARRAY_IS_SEQ;
        }
        elseif( count( array_filter( array_keys( $array ), 'is_string' ) ) > 0 ) {
            $result = self::ARRAY_IS_ASSOC;
        }
        self::log()->debug( __FUNCTION__ , "array $label = " . self::ARRAY_RESULTS[$result ] );

        if( self::ARRAY_IS === $test ) {
            return $result;
        }

        return ( $test | $result );
    }

    /**
     * Callback function for sorting regular values before sub arrays
     *
     * @param mixed $varA
     * @param mixed $varB
     *
     * @return int
     * @noinspection PhpUnused
     */
    public static function sortValueBeforeSubArray( $varA, $varB ): int {
        $is_arrayA = is_array( $varA );
        $is_arrayB = is_array( $varB );

        if( $is_arrayA == $is_arrayB ) { return 0; }
        return ( $is_arrayA < $is_arrayB ) ? -1 : 1;
    }

}