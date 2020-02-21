<?php declare( strict_types = 1 );

namespace PegasusICT\PhpHelpers;
const ARRAY_IS       = 0b0000;
const ARRAY_IS_SEQ   = 0b0001;
const ARRAY_IS_ASSOC = 0b0010;
const ARRAY_IS_NUM   = 0b0100;
const ARRAY_IS_EMPTY = 0b1000;
const ARRAY_RESULTS  = [
    ARRAY_IS_SEQ   => "sequential",
    ARRAY_IS_NUM   => "numerical",
    ARRAY_IS_ASSOC => "associative",
    ARRAY_IS_EMPTY => "empty",
    ARRAY_IS       => "what?"
];

/**
 * Class ArrayTools
 *
 * PHP version ^7.2
 *
 * @package   PegasusICT\PhpHelpers
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2002-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 */
abstract class ArrayTools {

    /**
     * @param string|int|float $key
     * @param array            $array
     *
     * @return int
     */
    static function indexOff( $key, array $array ): int {
        if( in_array( $key, $array ) ) {
            return array_flip( $array )[$key];
        }

        return -1;
    }

    /**
     * @param array $array
     * @param int   $test
     *
     * @return int
     */
    static function testArray( string $label, array $array, int $test = ARRAY_IS ) {
        Logger::getInstance( __TRAIT__, ( __CLASS__ ?? null ) ) ->debug( __FUNCTION__, "test = " . ARRAY_RESULTS[ $test ] );
        $result = ARRAY_IS_NUM;
        if( array() === $array ) {
            $result = ARRAY_IS_EMPTY;
        }
        elseif( array_keys( $array ) === range( 0, count( $array ) - 1 ) ) {
            $result = ARRAY_IS_SEQ;
        }
        elseif( count( array_filter( array_keys( $array ), 'is_string' ) ) > 0) {
            $result = ARRAY_IS_ASSOC;
        }
        Logger::getInstance( __TRAIT__,( __CLASS__ ?? null ) )
              ->debug( __FUNCTION__ ,  "array $label = " . ARRAY_RESULTS[ $result ] );

        if( ARRAY_IS === $test ) {
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
    public function sortValueBeforeSubArray( $varA, $varB ): int {
        $is_arrayA = is_array( $varA );
        $is_arrayB = is_array( $varB );

        if( $is_arrayA == $is_arrayB ) { return 0; }
        return ( $is_arrayA < $is_arrayB ) ? -1 : 1;
    }

}