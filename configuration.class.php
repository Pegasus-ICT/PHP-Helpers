<?php declare(strict_types=1);

namespace PegasusICT\PhpHelpers;

use BadFunctionCallException;
use PegasusICT\MasterClass;

/**
 * Class Configuration
 *
 * This class is responsible for:
 * 1.   finding, loading and parsing configuration files
 * 2.   return configuration values/(sub)sections (arrays)
 * 3.   update/create configuration files
 *
 * Currently supports: ini, json, array
 *
 * @package   PegasusICT/PhpHelpers
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2002-2020 Pegasus ICT Dienstverlening
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 * @license   MIT License
 */
class Configuration extends MasterClass {

    private const CFG_DIR    = __DIR__ . "/../../cfg/";

    const FILE_TYPES = [
        "ini"   => "ini",
        "json"  => "json",
        "array" => "cfg.php",
    ];

    private $data = [];

    /* @var Configuration $instance */
    private static $instance;

    /**
     * Lazy instantiation of Configuration class for use in for instance Traits
     *
     * @param string            $__CLASS__  Class name of the caller
     * @param string|null       $superClass Class name of the caller's caller
     * @param string|array|null $config     path to config file or array of config data
     *
     * @return Configuration
     */
    public static function getInstance( string $__CLASS__, ?string $superClass=null, $config=null ): Configuration{
        if( !is_object(self::$instance ) ) self::$instance = new Configuration( $__CLASS__, $superClass, $config );
        return self::$instance;
    }

    /**
     * Configuration constructor.
     *
     * @param string            $__CLASS__
     * @param string|null       $superClass
     * @param string|array|null $config
     */
    public function __construct( string $__CLASS__, ?string $superClass=null, $config=null ) {
        $this->callerClass = $__CLASS__;
        $this->superClass = $superClass ?? false;
        if( !$this->loadCfg( $config ) ) {
            throw new BadFunctionCallException();
        }
    }

    /**
     * @param string|array|null $config
     *
     * @return bool
     */
    private function loadCfg( $config=null ) : bool {
        $path = ( $this->superClass ) ? "$this->superClass/" : "";
        $filename = $path . $this->callerClass;
        $baseCfg = [];
        $cfg = [];
        if( false==( $fileFound = $this->findFile( $filename ) ) ){
             Logger::getInstance(__CLASS__)->warning(__FUNCTION__,"File '$filename' not found");
        }
        elseif( false ==($baseCfg = $this->processFile( $fileFound[0], $fileFound[1] ) ) ) {
            Logger::getInstance(__CLASS__)->error(__FUNCTION__,"Error processing file " . $fileFound[0]);
        }
        if( null != $config ) {
            if( is_string( $config ) ) {
                $fileFound = $this->findFile( $config );
                $cfg = $this->processFile( $fileFound[0], $fileFound[1] )
                       ?? parse_ini_string( $config, true, INI_SCANNER_TYPED )
                          ?? json_decode( $config, true );
                if( ( !$cfg ) ) Logger::getInstance(__CLASS__)->error(__FUNCTION__, "Unable to process '$config'");
            }
            elseif( empty($config) || false==( $cfg = array_replace_recursive( [], $config ) ) ) {
                Logger::getInstance(__CLASS__)->error(__FUNCTION__,"Unable to process configuration array");
            }
        }
        if( false==( $this->data = array_replace_recursive( $this->data, $baseCfg, $cfg ) ) ) {
            Logger::getInstance(__CLASS__)->error(__FUNCTION__, "Unable to load Configuration!");

            return false;
        }

        return true;
    }

    /**
     * @param string $filename
     *
     * @return array|bool  returns either correct path and extension or false if not found
     */
    private function findFile( string $filename ): array {
        foreach( [ "", self::CFG_DIR ] as $path ) {
            foreach( self::FILE_TYPES as $fileType => $ext ) {
                $file = sprintf( "%s%s.%s", $path, $filename, $ext );
                if( file_exists( $file ) ) {
                    return [ $file, $fileType ];
                }
            }
        }

        return false;
    }

    /**
     * @param string $filePath
     * @param string $fileType
     *
     * @return array|bool
     */
    private function processFile( string $filePath, string $fileType ): array {
        $result = [];
        switch( $fileType ) {
            case "ini":
                $result = parse_ini_file( $filePath, true, INI_SCANNER_TYPED );
                break;
            case "json":
                $result = json_decode( file_get_contents( $filePath ), true );
                break;
            case "array":
                /** @noinspection PhpIncludeInspection */
                $result = require_once $filePath;
                break;
            default:
                $result = false;
        }

        return !is_array( $result ) ?: $result;
    }

    /**
     * Retrieve value from configuration array
     *
     * @param string|int|float $key        Configuration key
     * @param string|int|float $section    Optional section
     * @param string|int|float $subsection Optional subsection
     *
     * @return string|array|int|float|boolean      Either returns value/(sub)section or false if nothing found
     */
    public function get( $key='all', $section = null, $subsection = null ) {
        $result = false;
        if('all' == strtolower($key)) return $this->data;
        if( 'main' === $section ) {
            $section = $subsection;
            $subsection = null;
        }
        if( !empty( $subsection ) ) {
            $result = array_key_exists($key, $this->data[$section][$subsection])
                ? $this->data[$section][$subsection][$key]
                : false;
        } elseif( !empty( $section ) ) {
            $result = array_key_exists($key, $this->data[$section])
                ? $this->data[$section][$key]
                : false;
        } elseif( !empty( $key ) ) {
            $result = array_key_exists($key, $this->data)
                ? $this->data[$key]
                : false;
        }

        return $result;
    }

    public function keyExists( $key, $section = null, $subsection = null ) {
        $result = false;
        if( 'main' === $section ) {
            $section = $subsection;
            $subsection = null;
        }
        if( !empty( $subsection ) ) {
            $result = array_key_exists($key, $this->data[$section][$subsection]);
        }
        elseif( !empty( $section ) ) {
            $result = array_key_exists($key, $this->data[$section]);
        }
        elseif( !empty( $key ) ) {
            $result = array_key_exists($key, $this->data);
        }

        return $result;
    }

    /**
     * Set value to configuration stack
     *
     * @param string|int|float                    $key        Configuration key
     * @param string|array|int|float|boolean|null $value      Configuration value
     * @param string|int|float|null               $section    Optional section
     * @param string|int|float|null               $subsection Optional subsection
     *
     * @return Configuration|bool                             False on failure, fluent otherwise
     */
    public function set( $key, $value = null, $section = null, $subsection = null ) : Configuration {
        $result = false;
        if( !empty( $subsection ) && !empty($section) && !empty( $key ) ) {
            $this->data[$section][$subsection][$key] = $value;
            $result                                  = true;
        }
        elseif( !empty( $section ) && !empty( $key ) ) {
            $this->data[$section][$key] = $value;
            $result                     = true;
        }
        elseif(!empty( $key ) ) {
            $this->data[$key] = $value;
            $result           = true;
        }

        if(!$result) {
            Logger::getInstance()->error(__CLASS__.__FUNCTION__, "Invalid arguments supplied");
        }

        return $this;
    }

}
