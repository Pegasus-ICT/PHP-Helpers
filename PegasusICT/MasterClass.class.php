<?php declare( strict_types=1 );
/**
 * PegasusICT MasterClass
 *
 * Provides basic infrastructure and initializes configuration data and logger
 * All other classes extend on this one.
 *
 * PHP version ^7.2
 *
 * @package   PhpHelpers
 * @author    Mattijs Snepvangers <pegasus[dot]ict[at]gmail[dot]com>
 * @copyright 2019-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 */

/**
 *
 */
namespace PegasusICT;

use PegasusICT\PhpHelpers\ArrayTools;
use PegasusICT\PhpHelpers\Configuration;
use PegasusICT\PhpHelpers\Logger;

/**
 * Class MasterClass
 *
 * @package PegasusICT
 *
 * @method getAll()
 */
abstract class MasterClass extends ArrayTools {
    /**
     * @var string class/file name of caller
     */
    protected $callerClass;
    /**
     * @var string class/file name of caller's caller
     */
    protected $superClass;
    /* @var Logger $logger */
    protected $logger;
    /* @var Configuration $config */
    protected $config;
    /**
     * @var array operational data store
     */
    protected $data =[];


    /**
     * Constructor.
     *
     * @param string            $__CLASS__  Class or File name of the caller
     * @param string|null       $superClass Class or File name of the caller's caller
     * @param string|array|null $cfg        Logger config provided by caller
     */
    public function __construct( string $__CLASS__, $superClass=null, $cfg = null ) {
        $this->callerClass = $__CLASS__ ?? false;
        $this->superClass  = $superClass ?? false;

        $this->init( $cfg );
    }

    /**
     * Gets value from dataStore, else queries configuration or class defaults as a last resort
     *
     * @param      $key
     *
     * @param null $section
     * @param null $subsection
     *
     * @return array|bool|float|int|string
     */
    public function __get( $key, $section=null, $subsection=null ) {
        if('main' === $section){
            $section = $subsection;
            $subsection = null;
        }
        $result = false;
        $value = null;
        $class = get_class();
        $dataArray = $this->data;
        if(null !== $subsection && array_key_exists($subsection, $dataArray[$section])) {
            $dataArray = $dataArray[$section][$subsection];
        }
        elseif(null !== $section && array_key_exists($section, $dataArray)) {
            $dataArray = $dataArray[$section];
        }
        //
        if( array_key_exists( $key, $dataArray ) ) {
            $value = $dataArray[$key];
            $result = true;
        }
        elseif($this->config->keyExists( $key, $section, $subsection )){
            $value = $this->config->get($key, $section, $subsection);
            $result = true;
        }
        if($result) return $value;
        // Unknown key provided!!!
        $trace = debug_backtrace();
        trigger_error(
            "Undefined property via __set(): '$key' in " . $class ?? $trace[0]['file'] . ' on line ' . $trace[0]['line'] . '.',
            E_USER_NOTICE
        );

        return $result;
    }

    /**
     * Sets $this->data[$key] to $value
     * If $key = All, value is ignored and sets each key from DEFAULTS to respective value from config if found, else from DEFAULTS
     *
     * If value needs to be empty, use ""
     *
     * @param string|int|float $key   configuration key
     * @param mixed            $value configuration value
     *
     * @param null             $section
     * @param null             $subsection
     *
     * @return object|boolean   Returns false if key is not found.
     */
    public function __set( $key, $value = null, $section=null, $subsection=null) {
        $class  = get_class();
        $defaults = $class::DEFAULTS;
        $dataArray = &$this->data;
        $result = false;

        if( 'all' == strtolower($key) ) {
            $this->data = array_replace_recursive($this->data, $defaults, $this->config->get());
            return true;
        }
        if(null !== $subsection) {
            $dataArray = &$this->data[$section][$subsection];
            $defaults = $defaults[$section][$subsection];
        }
        elseif(null !== $section) {
            $dataArray = &$this->data[$section];
            $defaults = $defaults[$section];
        }

        if( array_key_exists( $key, $defaults ) ) {
            $dataArray[$key] = ( null !== $value ) ? $value : $this->config->get( $key ) ?? $class::DEFAULTS[$key];
            $result     = true;
        }
        elseif( false !== ( $value = $this->config->get( $key ) ) ) {
            $this->data[$key] = $value;
            $result     = true;
        }
        if( !$result ) {
            // Unknown key provided!!!
            $trace = debug_backtrace();
            trigger_error(
                "Undefined property via __set(): '$key' in " . ( $class ?? $trace[0]['file'] ) . ' on line ' . $trace[0]['line'] . '.',
                E_USER_NOTICE
            );

            return false;
        }

        return $this;
    }

    /**
     * @param string|array|null $cfg
     *
     * @return Object
     */
    protected function init( $cfg = null ) {
        $class = $this->getShortName();
        print( "class: $class" );
        $superClass = $this->getShortName( $this->callerClass );
        print( "superclass: $superClass" );
        if( $class !== 'Logger' ) {
            $this->logger = new Logger( $class, $superClass );
        }
        if( $class !== 'Configuration' ) {
            $this->config = new Configuration( $class, $superClass );
        }
        $this->getAll();

        return $this;
    }

    /**
     * @param string|null $class
     *
     * @return string
     */
    protected function getShortName( ?string $class = null ) {
        return array_pop( explode( '\\', $class ?? get_class() ) );
    }

    /**
     * Returns array of caller class, file and function & callers' caller class, file and function
     * returns false if no caller was found.
     *
     * @param int $startingDepth
     *
     * @return array|bool
     */
    protected function getCallerClass( $startingDepth = 1 ) : array {
        $result  = [];
        $callers = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $startingDepth + 1 );
        for( $level = 1; $level <= 2; $level ++ ) {
            foreach( [ 'class', 'file', 'function' ] as $item ) {
                if( isset( $callers[$level][$item] ) && !empty( $callers[$level][$item] ) ) {
                    $result[$level][$item] = $callers[$level][$item];
                }
            }
        }

        $result = empty( $result ) ? false : $result;

        return $result;
    }

    /**
     * @param string $format
     * @param float  $microTime
     *
     * @return false|string
     */
    protected function timestamp( string $format = null, float $microTime = null ) : string {
        $microTime = $microTime ?? microtime( true );
        $format    = $format ?? "Y-m-d H:i:s,u T";

        $timestamp    = (int)floor( $microTime );
        $milliseconds = round( ( $microTime - $timestamp ) * 1000000 );

        return date( preg_replace( "`(?<!\\\\)u`", $milliseconds, $format ), $timestamp );
    }

}