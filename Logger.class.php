<?php declare(strict_types=1);

namespace PegasusICT\Logger;

/**
 * Class Logger
 *
 * A note on configuration: You can either use 'log_file'(1) or the 4 fields denoted with (2) in DEFAULTS.
 * If 'log_file' is set, the rest is ignored!!!
 * The sub-arrays in log_file_error/messages/debug are the maximum level that will be logged in that particular file
 *
 * So in the first case, the filename will be 'log_file'.'log_file_ext' eg. PegasusICT.log
 *  and in the latter the names will be:
 *
 *          << Filename >>               << logLevels >>
 *      PegasusICT_errors.log       critical, error and warning
 *      PegasusICT_messages.log          info and verbose
 *      PegasusICT_debug.log                   debug
 *
 * Ultimately 'log_level' constitutes which messages are actually written to the logfile(s)
 *
 * @package PegasusICT\Logger
 *
 * @method static critical(string $message, string $callerClass, string $callerFunction)
 * @method static error(string $message, string $callerClass, string $callerFunction)
 * @method static warning(string $message, string $callerClass, string $callerFunction)
 * @method static info(string $message, string $callerClass, string $callerFunction)
 * @method static verbose(string $message, string $callerClass, string $callerFunction)
 * @method static debug(string $message, string $callerClass, string $callerFunction)
 */
class Logger {
// CONSTANTS

    /**
     * @const array     Default configuration
     */
    private const DEFAULTS = [
        'log_dir'           =>  'logs/',
//        'log_file'          =>  'PegasusICT',          // ad) 1
        'log_file_base'     =>  'PegasusICT',           // ad) 2
        'log_file_error'    =>  [2,'errors'],           // ad) 2
        'log_file_messages' =>  [4,'messages'],         // ad) 2
        'log_file_debug'    =>  'debug',                // ad) 2

        'log_file_ext'      =>  'log',
        'log_level'         =>  'debug'
    ];

    /**
     * @const array     Indexed list of all log levels used in this class
     */
    private const LOG_LEVELS = [
        'critical',     // level 0
        'error',        // level 1
        'warning',      // level 2
        'info',         // level 3
        'verbose',      // level 4
        'debug'         // level 5
    ];

// STATICS
    /**
     * @var $instance Logger
     */
    private static $instance = null;

    /**
     * Catches all Log calls and passes them on to a private function which does the heavy lifting
     *
     * @param $logLevel
     * @param $arguments
     */
    public static function __callStatic($logLevel, $arguments) {
        self::_getInstance()->_log($logLevel, $arguments[0], $arguments[1], $arguments[2]);
    }

    /**
     * Gets the instance of Logger via lazy initialization (created on first usage)
     *
     * @return \PegasusICT\Logger\Logger
     */
    private static function _getInstance(): Logger {
        if( static::$instance === null ) static::$instance = new Logger();

        return static::$instance;
    }

// variables
    /**
     * @var int maximum LogLevel which gets written
     */
    private $_logLevel = 5;
    /**
     * @var string LogFile if one file is being used for all log levels
     */
    private $_logFile  = '';
    /**
     * @var array LogFiles if several files are being used, which file depending on log level
     */
    private $_logFiles = [];
    /**
     * @var array Configuration
     */
    private $_cfg      = [];

    /**
     * \PegasusICT\Logger\Logger constructor.
     */
    private function __construct() {
        $this->_init();

        return $this;
    }

    /**
     * Parses configuration data
     */
    private function _init() {
        $cfg = &$this->_cfg;
// load default settings
        $cfg = array_replace_recursive($cfg,self::DEFAULTS);
        $cfg['docroot'] = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;

// check for & parse config file if available
        $cfgFile = $cfg['docroot'] . "cfg" . DIRECTORY_SEPARATOR . "PegasusICT" . DIRECTORY_SEPARATOR . "logger.ini";
        if(file_exists($cfgFile)) $cfg = array_replace_recursive($cfg, parse_ini_file($cfgFile));

// set logfile(s)
        if(array_key_exists('log_file',$cfg) && !empty($cfg['log_file'])) {
            $this->_logFile = $cfg['docroot'].$cfg['log_dir'].$cfg['log_file'];
            $this->_logFiles = false;
        } else {
            $this->_logFile = false;
            foreach( array_keys(self::LOG_LEVELS) as $key ) {
                if( $key <= $cfg['log_file_error'][0] ) {
                    $this->_logFiles[$key] = $cfg['log_file_error'][1];
                } elseif( $key <= $cfg['log_file_messages'][0] ) {
                    $this->_logFiles[$key] = $cfg['log_file_messages'][1];
                } else {
                    $this->_logFiles[$key] = $cfg['log_file_debug'];
                }
            }
        }

        if( !empty($cfg['log_level'] ) &&
           in_array( $cfg['log_level'],self::LOG_LEVELS ) ) {
            $this->_logLevel = array_flip(self::LOG_LEVELS)[$cfg['log_level']];
        } else {
            $this->_logLevel = array_key_last(self::LOG_LEVELS);
        }
    }

    /**
     * Validates log level called, verifies the log level threshold,
     *
     * @param string      $logLevel
     * @param string      $message
     * @param string|null $callerClass
     * @param string|null $callerFunction
     */
    private function _log( $logLevel, $message, $callerClass=null, $callerFunction=null ) {
// validates the log level used in the call
        if( !in_array( $logLevel, self::LOG_LEVELS ) )
            self::error( "Unknown log level '$logLevel', " . $message, __CLASS__, __FUNCTION__ );

// get the log level number
        $logLevelToNumber = array_flip( self::LOG_LEVELS );
        $logLevelNumber   = $logLevelToNumber[ $logLevel ];

// decide whether to process the message
        if( $logLevelNumber <= $this->_logLevel ) {

// add date and time including nanoseconds
            $line  = date( "Y-m-d H.i.s,u" );

// format the line label
            $line .= " [ " . strtoupper( $logLevel ) . " ] ";

// add class and function names of caller if present
            $line .= $callerClass    ? " " . $callerClass . "->"  : null;
            $line .= $callerFunction ? $callerFunction . "(): " : null;

// add message
            $line .= $message . "\n";

// determine which file to write to
            $logFile = $this->_logFile ?? $this->_logFiles[ $logLevelNumber ];

// append to file, create if it doesn't exist
            file_put_contents( $logFile, $line, FILE_APPEND );
        }
    }
}
