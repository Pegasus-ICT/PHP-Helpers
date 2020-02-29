<?php declare( strict_types=1 );
/**
 * demo.php
 *
 * Demonstration if IniGenerator
 *
 * @package   PegasusICT/PhpHelpers
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2002-2020 Pegasus ICT Dienstverlening
 * @version   Release v0.1.0-dev
 * @link      https://github.com/Pegasus-ICT/PhpHelpers/
 * @license   MIT License
 */
require_once "autoloader.class.php";
$loader = new Autoloader();

$iniGenerator = new PegasusICT\PhpIniGenerator\IniGenerator(null,null,'demo.ini');