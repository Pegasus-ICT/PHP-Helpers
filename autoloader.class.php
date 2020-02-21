<?php declare(strict_types=1);

/**
 * Class AutoloaderClass
 *
 * @package   PegasusICT/PhpHelpers
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2002-2020 Pegasus ICT Dienstverlening
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 * @license   MIT License
 */
class AutoloaderClass {

    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = array();

    /**
     * AutoloaderClass constructor.
     */
    public function __construct() {
        $this->addNamespace( "PegasusICT\PhpHelpers", "PegasusICT/PhpHelpers" );
        $this->addNamespace( "PegasusICT\IniGenerator", "PegasusICT/PhpIniGenerator/src" );
        $this->register();
    }

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register() {
        spl_autoload_register( [ $this, 'loadClass' ] );
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix   The namespace prefix.
     * @param string $base_dir A base directory for class files in the namespace.
     * @param bool   $prepend  If true, prepend the base directory to the stack instead of appending it; this causes it to be searched first rather than last.
     *
     * @return void
     */
    public function addNamespace( $prefix, $base_dir, $prepend = false ) {
        $prefix = trim( $prefix, '\\' ) . '\\';  // normalize namespace prefix
        // normalize the base directory with a trailing separator
        $base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . '/';
        // initialize the namespace prefix array
        if( isset( $this->prefixes[$prefix] ) === false ) { $this->prefixes[$prefix] = array(); }

        // retain the base directory for the namespace prefix
        if( $prepend ) { array_unshift( $this->prefixes[$prefix], $base_dir ); }
        else { array_push( $this->prefixes[$prefix], $base_dir ); }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param  string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass( $class ) {
        // the current namespace prefix
        $prefix = $class;

        // work backwards through the namespace names of the fully-qualified class name to find a mapped file name
        while( false !== ( $pos = strrpos( $prefix, '\\' ) ) ) {
            // retain the trailing namespace separator in the prefix
            $prefix = substr( $class, 0, $pos + 1 );
            // the rest is the relative class name
            $relative_class = substr( $class, $pos + 1 );
            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile( $prefix, $relative_class );
            if( $mapped_file ) { return $mapped_file; }

            // remove the trailing namespace separator for the next iteration
            $prefix = rtrim($prefix, '\\');
        }

        return false; // never found a mapped file
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix         The namespace prefix.
     * @param string $relative_class The relative class name.
     *
     * @return string|Boolean        False if no mapped file can be loaded, or the name of the mapped file that was loaded.
     */
    protected function loadMappedFile( $prefix, $relative_class ) {
        // are there any base directories for this namespace prefix?
        if( isset( $this->prefixes[$prefix] ) === false ) { return false; }
        foreach( $this->prefixes[$prefix] as $base_dir ) { // look through base directories for this namespace prefix

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            // if the mapped file exists, require it
            if ($this->requireFile($file)) { return $file; }
        }

        return false; // never found it
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param  string $file The file to require.
     * @return bool        True if the file exists, false if not.
     */
    protected function requireFile( string $file ) {
        if( file_exists( $file ) ) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;

            return true;
        }

        return false;
    }

}
