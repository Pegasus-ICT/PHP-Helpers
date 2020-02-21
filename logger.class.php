<?php declare( strict_types=1 );
/**
 * Logger
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
namespace PegasusICT\PhpHelpers;
use PegasusICT\MasterClass;
use function \in_array;

require_once __DIR__."configuration.class.php";
/**
 * Class Logger
 *
 * @method  void critical( string $__FUNCTION__, string $line = '' )
 * @method  void error   ( string $__FUNCTION__, string $line = '' )
 * @method  void warning ( string $__FUNCTION__, string $line = '' )
 * @method  void notice  ( string $__FUNCTION__, string $line = '' )
 * @method  void info    ( string $__FUNCTION__, string $line = '' )
 * @method  void verbose ( string $__FUNCTION__, string $line = '' )
 * @method  void debug   ( string $__FUNCTION__, string $line = '' )
 */
class Logger extends MasterClass {
    private const DEFAULTS = [
        'logDir'   => __DIR__ . "../logs/",
        'fileSize' => 4,
        'fileName' => "PegasusICT_Logger",
        'maxLevel' => "warning",
        'subjects' => [],
        'cycle'    => "day",
    ];
    private const LEVELS   = [ "disabled", "critical", "error", "warning", "notice", "info", "verbose", "debug" ];

// internal variables
    /**
     * @var string
     */
    private $maxLevel;
    /**
     * @var string
     */
    private $logDir;
    /**
     * @var array
     */
    private $subjects;
    /**
     * @var null|int|string either day|week|month or number of days or null to cycle only by filesize limit
     */
    private $cycle;
    /**
     * @var int filesize limit of logfiles
     */
    private $fileSize;
    /**
     * @var string base name for logfiles
     */
    private $fileName;

///// Singleton start /////
    /**
     * @var Logger
     */
    private static $instance;
    /**
     * Lazy Instantiation of Logger for use in Traits and other static stuff
     *
     * @param string            $__CLASS__  Class or File name of the caller
     * @param string|null       $superClass Class or File name of the caller's caller
     * @param string|array|null $cfg        Logger config provided by caller
     *
     * @return Logger
     */
    public static function getInstance( string $__CLASS__, string $superClass=null, $cfg = null ): Logger {
        if( !is_object( self::$instance ) ) {
            self::$instance = new Logger( $__CLASS__, $superClass, $cfg );
        }

        return self::$instance;
    }
///// Singleton end /////

    /**
     * Logger constructor.
     *
     * @param string            $__CLASS__  Class or File name of the caller
     * @param string|null       $superClass Class or File name of the caller's caller
     * @param string|array|null $cfg        Logger config provided by caller
     */
    public function __construct( string $__CLASS__, $superClass=null, $cfg = null ) {
        $this->callerClass = $__CLASS__ ?? false;
        $this->superClass  = $superClass??false;

        $this->init( $cfg );
    }

    /**
     * Gets the configuration
     *
     * @param string|null       $__CLASS__  Class name of the caller
     * @param string|array|null $cfg        Logger config provided by caller
     */
    private function init( $cfg = null ):void {
        $this->config = new Configuration( __CLASS__ , $this->callerClass ?? null, $cfg );
        foreach( self::DEFAULTS as $key => $defaultValue ) {
            $this->$key = $this->config->get( $key ) ?? $defaultValue;
        }
    }

    /**
     *  Setting the maximum level to process
     *
     * If no argument is provided, the configuration is checked.
     * If all else fails, "warning" is set (production safe...)
     *
     * @param string|null $maxLevel
     *
     * @return Logger
     */
    public function setMaxLevel( ?string $maxLevel=null ): Logger {
        $this->maxLevel = $maxLevel ??
                          $this->config->get( 'maxLevel') ??
                          self::DEFAULTS['maxLevel'];

        return $this;
    }

    /**
     * Returns the maximum level which will be processed.
     * in case of flunt calls: if parameter $result is not _exactly_ null, it will hold the return value
     * @example $logger->getMaxLevel($maxLevel="");
     *
     * @param string|null $result
     *
     * @return Logger|string
     */
    public function getMaxLevel( ?string &$result=null ) {
        if(empty($this->maxLevel)){
            $this->setMaxLevel();
        }

        $result = (null!==$result) ? $this->maxLevel : null;

        return $result ?? $this;
    }

    /**
     * This function acts as a validator & switchboard for:
     *  - sending messages to the log stack,        <level>(__FUNCTION__ <message>)
     *
     * @param string     $level              actual call made
     * @param array|null $functionAndMessage function making the call, message
     *
     * @return Logger
     */
    public function __call( string $level, ?array $functionAndMessage ): Logger {
        $function = $functionAndMessage[0] ?? "_unknown Function_";
        $message = $functionAndMessage[1] ?? "";
        if( in_array( $level, self::LEVELS, false )
            && ArrayTools::indexOff( $level, self::LEVELS ) <= ArrayTools::indexOff( $this->maxLevel, self::LEVELS ) ) {
            return $this->_log( $level, $this->superClass."->".$this->callerClass, $function, $message );

        }
        elseif( ArrayTools::indexOff( "warning",self::LEVELS ) <= ArrayTools::indexOff( $this->maxLevel,self::LEVELS ) ) {
            return $this->_log( "warning", __CLASS__,__FUNCTION__,
                                "Unknown call \"$level\" made by " . $functionAndMessage[0 ] ?? "unknown function" .
                                                                                                " with argument: " . $functionAndMessage[1 ] ?? "none" );
        }

        return $this;
    }

    /**
     * The actual logging function
     *
     * @param string $function
     * @param string $line
     * @param string $level
     *
     * @return Logger
     */
    private function _log( string $level, string $class, string $function, string $line = "" ): Logger {
        $subjects=[];
        foreach( $this->subjects as $key => $value ) {
            if( is_array( $value ) ){ $subjects[$key]=$value; }
            foreach($subjects as $subject => $levels) {
                $min     = $this->index( $levels[ 'min' ] );
                $max     = $this->index( $levels[ 'max' ] );
                $index   = $this->index( $level );
                $Allowed = $this->index($this->maxLevel );
                if( $index >= $min && $index <= $max && $index <= $Allowed ) {
                    $logLine = date( "H:i:s,u" ) . " [" . strtoupper( $level ) . "] $class->$function(): $line";
                    $this->write($subject, $logLine);
                }
            }
        }
        return $this;
    }

    /**
     * @param string|null $logDir
     *
     * @return Logger
     */
    public function setLogDir( ?string $logDir=null ): Logger {
        return $this->set('logDir', $logDir);
    }

    /**
     * Writes $logLine to file corresponding with $subject.
     * Directories are created is necessary
     *
     * @param string $subject   which file to write to
     * @param string $logLine   log line
     *
     * @return Logger
     */
    private function write( string $subject, string $logLine ):Logger {
        $logDir = $this->logDir ?? "/var/log/phpLogger/";
        if( strpos( $logDir, '/' ) !== 0 ) { $logDir = __DIR__.$logDir; }
        if( !is_dir( $logDir ) ) { mkdir( $logDir, 0755, true ); }
        $fileName = $this->config->get( 'filename') ?? "PhpIniGenerator";
        $cycle = $this->config->get( 'cycle');
            switch($cycle){
                case "month":   $fileName .= date("_Y_m"); break;
                case "week":    $fileName .= date("_Y_W"); break;
                case "day":     // default
                default:        $fileName .= date("_Y_m_d"); break;
            }
        $fileName .= ( "all" !== $subject ) ? "_$subject" : "";
        file_put_contents($logDir.$fileName.".log",$logLine, FILE_APPEND );

        return $this;
    }

}
