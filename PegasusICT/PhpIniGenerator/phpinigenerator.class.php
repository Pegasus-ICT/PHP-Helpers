<?php declare( strict_types = 1 );
/**
 * PhpIniGenerator
 *
 * PHP version ^7.2
 *
 * @package   PegasusICT/PhpIniGenerator
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2019-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpIniGenerator/
 */
/**
 *
 */
namespace PegasusICT\PhpIniGenerator {

    use PegasusICT\PhpHelpers\MasterClass;
    use PegasusICT\PhpHelpers\ArrayTools;
    use function gettype;

    /**
     * Class IniGenerator
     *
     * @package PegasusICT\PhpIniGenerator
     *
     * @property int          level
     * @property array|string delimiter
     */
    class IniGenerator extends MasterClass {
        protected const DEFAULTS =[
            'delimiters'=>[3=>'◉',4=>'✔',5=>'❄']
        ];

        /**
         * Generates ini string from given array,
         * If $section is specified, only this section. along with main level key/value pairs will be parsed,
         *
         * @param array       $array        array with configuration data
         * @param string|null $section      (optional) section to include exclusively
         *
         * @return string                   generated ini string
         */
        public function array2ini( array $array = [], ?string $section = null ) :string {
            $this->logger->debug( __FUNCTION__, "Level: " . $this->level );
            $result = '';
            if( ! empty( $array ) ) {
                uasort( $array, "PegasusICT\\PhpHelpers\\ArrayTools::sortValueBeforeSubArray" );
                $this->level++;
                foreach( $array as $key => $value ) {
                    if( strncmp( $key, ';', 1 ) === 0 ) {
                        $this->logger->debug(__FUNCTION__, "inserting comment line");
                        $result .= "; " . preg_replace("/[@]{3}/", date("Y-m-d H:i:s T"), $value) . "\n";
                        continue;
                    }
                    if( is_array( $value ) ) {
                        if( 1 == $this->level ) {
                            if( null !== $section || $key === $section ) { $result .= "[" . $key . "]\n"; }
                            $result .= $this->processSecondaryArray( $key, $value );
                            continue;
                        }
                        elseif( 3 <= $this->level ) {
                            if( null !== $section ) { $key = $section . "[" . $key . "]"; }
                            $result .= $key . " = \"" . implode( $this->getDelimiter( $this->level), $value) . "\"\n";
                            continue;
                        }
                        $result .= $this->processSubArray( $key, $value, $section );
                        continue;
                    }
                    switch(gettype($value)) {
                        case 'boolean':
                            $result .= "$key = " . ($value ? 'true' : 'false') . "\n";
                            break;
                        case 'integer': // treat same as 'double'
                        case 'double':
                            $result .= "$key = $value\n";
                            break;
                        case 'string':
                            $result .= "$key = \"$value\"\n";
                            break;
                        case 'array':
                            break; // skip
                        default:
                            $result .= "$key = null\n";
                            break; // "NULL", "object", "resource", "resource (closed)", "unknown type"
                    }
                }
                return $result;
            }

            $this->logger->notice(__FUNCTION__, "array is empty");
            return $result;
        }

        /**
         * @param string $label
         * @param array  $array
         *
         * @return string
         */
        private function processSecondaryArray( string $label, array $array): string {
            $this->logger->debug(__FUNCTION__, "Level: " . $this->level++);
            $result     = '';
            $arrayType = ArrayTools::testArray( $label, $array);
            if( ( ArrayTools::ARRAY_IS_ASSOC === $arrayType ) || ( ArrayTools::ARRAY_IS_NUM === $arrayType ) ) {
                foreach($array as $subKey => $subValue) {
                    if(is_array($subValue)) {
                        $result .= $this->processSecondaryArray( $label . "[" . $subKey . "]", $subValue);
                        continue;
                    }
                    $result .= $label . "[" . $subKey . "] = $subValue\n";
                }
                $this->level--;
                return $result;
            }
            $this->logger->debug(__FUNCTION__, "$label = sequential");

            foreach($array as $subKey => $subValue) {
                if(is_array($subValue)) {
                    $result .= $this->processSecondaryArray( $label . "[]", $subValue);
                    continue;
                }
                $result .= $label . "[] = $subValue\n";
            }

            $this->level--;
            return $result;
        }

        /**
         * @param string      $key
         * @param array       $value
         * @param string|null $section
         *
         * @return string
         */
        private function processSubArray( string $key = '', array $value = [], string $section = null ) {
            $this->logger->debug(__FUNCTION__,
                                 "key = $key, value has " . count($value) . " elements, section = " . ($section ? : "null") .
                                 " level = " . $this->level);
            if( 1 !== $this->level ) {
                return $this->array2ini($value, $key);
            }

            if(( ( null === $section ) || ( $key === $section ) ) && ! empty($value)) {
                return "\n[$key]\n" . $this->array2ini($value, null);
            }
            return '';
        }

        /**
         * @param $array
         *
         * @return array
         */
        private function expandArray( $array ) {
            $result = [];
            foreach( $array as $key => $value ) {
                if(is_array($value)) {
                    $result[$key] = $this->expandArray( $value);
                } elseif(is_string($value) && strpos($value, $this->delimiter) !== false) {
                    $result[$key] = explode($this->delimiter, $value) ? : $value;

                    continue;
                }
                $result[$key] = $value;
            }
            return $result;
        }

        /**
         * @param string $ini
         * @param bool   $isFile
         *
         * @return array
         */
        public function ini2array(string $ini, $isFile = true): array {
            $this->logger->debug(__FUNCTION__, "Parsing a " . ($isFile ? "file" : "string") . ".");

            if($isFile) {
                return $this->expandArray( parse_ini_file( $ini, true, INI_SCANNER_TYPED));
            }

            return $this->expandArray( parse_ini_string( $ini, true, INI_SCANNER_TYPED));
        }

        /**
         * @param array  $configData
         * @param string $cfgFile
         * @param string $configDir
         * @param null   $fileHeader
         * @param bool   $timestamp
         */
        public function generateIniFile($configData = [], $cfgFile = "", $configDir = "cfg", $fileHeader = null, $timestamp = true): void {
            $this->logger->debug(__FUNCTION__);
            //check: Tools::checkDir($configDir);
            file_put_contents($configDir . $cfgFile, ($fileHeader ? : "; Config file generated at ") .
                                                     ($timestamp ? $this->timestamp() : '') . "\n" . $this->array2ini($configData));
        }

        /**
         * Set a delimiter for a specific level above 2 (defaults to level 3)
         * If $delimiter is an array, checks if the array keys are valid level numbers
         * otherwise fixes this issue and replaces the delimiter settings in the dataStore
         *
         * @param string|array $delimiter
         * @param int|null     $level
         *
         * @return IniGenerator
         */
        public function setDelimiter( $delimiter, int $level=null ){
            if( is_array( $delimiter ) ) {
                if(3!=array_key_first($delimiter)){
                    array_unshift($delimiter, '','','');
                }
                $this->data['delimiters'] = array_replace_recursive($this->data['delimiters'], $delimiter);
                return $this;
            }
            $level = ( null==$level || !is_int( $level ) ) ? 3 : $level;
            $level = ( $level < 3 ) ? $level+3 : $level;
            $this->data['delimiters'][$level] = $delimiter;

            return $this;
        }

        /**
         * Returns the delimiter corresponding to the level given or false if none found.
         * If level is not an integer of 3 or higher, level is set to 3
         *
         * @param int $level
         *
         * @return bool|string
         */
        public function getDelimiter( int $level ) {
            if(false===is_int($level) || 3 > $level ){
                $level = 3;
            }

            if(array_key_exists($level, $this->data['delimiters'])) {
                return $this->data['delimiters'][$level];
            }
            return false;
        }

    }
}