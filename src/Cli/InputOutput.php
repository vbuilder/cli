<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 *
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 *
 * For more information visit http://www.vbuilder.cz
 *
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Cli;

use vBuilder\Utils\Strings;

/**
 * Console input / output interface
 *
 * @warning This class was only tested on *nix systems!
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 5, 2014
 */
class InputOutput {

	/** Flags */
	const NO_COLORS = 0x01;
	/** */

	/** @var file handler Input */
	public $i;

	/** @var file handler Output */
	public $o;

	/** @var int flags */
	private $flags = 0;

	/** @var array of formatting presets */
	private $presets = array(
		'cReset' => "\033[0m",

		'cRed' => "\033[0;31m",
		'cRedBold' => "\033[1;31m",

		'cGreen' => "\033[0;32m",
		'cGreenBold' => "\033[1;32m",

		'cYellow' => "\033[0;33m",
		'cYellowBold' => "\033[1;33m",

		'cBlue' => "\033[0;34m",
		'cBlueBold' => "\033[1;34m",

		'cMagenta' => "\033[0;35m",
		'cMagentaBold' => "\033[1;35m",

		'cCyan' => "\033[0;36m",
		'cCyanBold' => "\033[1;36m",

		'cWhite' => "\033[0;37m",
		'cWhiteBold' => "\033[1;37m"
	);

	/**
	 * Constructor.
	 *
	 * @param fd input
	 * @param fd output
	 */
	public function __construct($input = NULL, $output = NULL, $flags = 0) {
		$this->i = $input ?: fopen('php://stdin', 'r');
		$this->o = $output ?: fopen('php://stdout', 'w');
		$this->flags = $flags;
	}

	/**
	 * Sets flag bit
	 *
	 * @param int flag
	 * @param bool value
	 */
	function setFlag($flag, $value = TRUE) {
		if($value)
			$this->flags |= $flag;
		else
			$this->flags &= ~ $flag;
	}

	/**
	 * Writes string
	 *
	 * @param string
	 */
	function write($message) {
		fwrite($this->o, $message);
	}

	/**
	 * Writes formatted string
	 *
	 * @param string
	 */
	function writef($message, $arguments = array()) {
		$allArguments = $arguments;
		foreach($this->presets as $k => $v) {
			if(!array_key_exists($k, $allArguments))
				$allArguments[$k] = $this->flags & self::NO_COLORS ? '' : $v;
		}

		return $this->write(Strings::sprintf($message, $allArguments));
	}

	/**
	 * Writes line of text
	 *
	 * @param string
	 */
	function writeln($line = '') {
		return $this->write($line . PHP_EOL);
	}

	/**
	 * Writes line of formatted string
	 *
	 * @param string
	 */
	function writelnf($line = '', $arguments = array()) {
		return $this->writef($line . PHP_EOL, $arguments);
	}

	/**
	 * Asks for input
	 *
	 * @param string prompt
	 * @return string
	 */
	function ask($prompt) {
		$this->write(rtrim($prompt) . ' ');
		return trim(fgets($this->i));
	}

	/**
	 * Asks for confirmation.
	 *
	 * @param string question
	 * @param bool default
	 * @return bool
	 */
	function askConfirmation($question, $default = TRUE) {

		do {
			$this->write(rtrim($question) . ' ');
			$this->write('(');
			$this->write($default ? 'Y' : 'y');
			$this->write('/');
			$this->write($default ? 'n' : 'N');
			$this->write(') ');

			$answer = $this->sttyHelper(array('cbreak', '-echo'), function ($io) {
				return fgetc($io->i);
			});

			switch($answer) {
				case 'Y':
				case 'y':
					$this->writelnf('%{cGreen}Yes%{cReset}');
					return TRUE;

				case 'N':
				case 'n':
					$this->writelnf('%{cRed}No%{cReset}');
					return FALSE;

				case chr(13):
				case chr(10):
					$this->writelnf($default ? '%{cGreen}Yes%{cReset}' : '%{cRed}No%{cReset}');
					return $default;

				default:
					$this->writeln('Unknown answer');
			}

		} while(TRUE);

	}

	/**
	 * Asks for an input while hiding inputed charactes.
	 *
	 * @param string prompt
	 * @return string
	 */
	function askAndHideAnswer($prompt = '') {

		// Prompt
		if($prompt != '') $this->write(rtrim($prompt) . ' ');

		$answer = $this->sttyHelper(array('-echo'), function ($io) {
			return fgets($io->i);
		});

		$this->writeln();
		return trim($answer);
	}

	/**
	 * Asks for password with prompt.
	 * All inputed characters are masked for stars.
	 *
	 * @param string prompt
	 * @return string
	 */
	function askForPassword($prompt = '') {

		// Prompt
		if($prompt != '') $this->write(rtrim($prompt) . ' ');

		// STTY context
		$password = $this->sttyHelper(array('cbreak', '-echo'), function ($io) {
			$password = '';

			do {
				$c = fgetc($io->i);
				switch($c) {
					case chr(13):	// CR
					case chr(10):	// LF
					case chr(27):	// ESC
						break 2;

					default:
						$password .= $c;
						fwrite($io->o, '*');
				}

			} while(TRUE);

			return $password;
		});

		$this->writeln();
		return $password;
	}

	/**
	 * Creates context with set STTY environment
	 * and automatically sets everything back to normal after
	 * execution.
	 *
	 * @warning this is *nix only!
	 *
	 * @param string|array STTY settings
	 * @param callable
	 * @return mixed callable output
	 * @throws Exception if STTY command failed
	 */
	protected function sttyHelper($settings, $callback) {
		$settings = is_array($settings) ? $settings : $callback;

		// Retrieve current settings ----------

		// Prepare arguments
		$re = '';
		$newSettings = array();
		$rollbackSettings = array();
		foreach($settings as $s) {
			$name = ltrim($s, '-');

			$newSettings[$name] = escapeshellarg($s);
			$rollbackSettings[$name] = escapeshellarg($name == $s ? "-$s" : $name);
			$re .= '|-?' . preg_quote($name, '/') . '\\b';
		}

		$re = '/' . substr($re, 1) . '/';

		// Query existing settings
		$cmd = '/bin/stty';
		exec($e = $cmd . ' -a', $outputedLines, $exitCode);
		if($exitCode != 0)
			throw new Exception("STTY command failed ($e)");

		// Process command output
		foreach($outputedLines as $line) {
			if(preg_match($re, $line, $matches)) {
				$name = ltrim($matches[0], '-');
				$rollbackSettings[$name] = escapeshellarg($matches[0]);
			}
		}

		// Set new settings -------------------

		exec($e = $cmd . ' ' . implode($newSettings, ' '), $outputedLines, $exitCode);
		if($exitCode != 0) throw new \Exception("STTY command failed ($e)");

		// Callback ---------------------------

		try {
			$exception = NULL;
			$result = NULL;

			// We need to handle all errors
			set_error_handler(function($severity, $message) {
				restore_error_handler();
				throw new \Exception($message);
			});

			$result = $callback($this);
			restore_error_handler();

		} catch(\Exception $exception) {

		}

		// Rollback old settings --------------
		exec($e = $cmd . ' ' . implode($rollbackSettings, ' '), $outputedLines, $exitCode);
		if($exitCode != 0) throw new \Exception("STTY command failed ($e)");

		if($exception) throw $exception;
		return $result;
	}

}