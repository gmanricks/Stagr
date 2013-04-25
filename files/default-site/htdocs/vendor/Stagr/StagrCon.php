<?php

namespace Stagr;

Class StagrCon
{
	private $stagrFilePath;

	public function __construct ($path = "/home/vagrant/.stagr")
	{
		$this->stagrFilePath = $path;
	}

	public function stagrFileExists()
	{
		return file_exists($this->stagrFilePath);
	}

	public function getAppNames()
	{
		$yaml = yaml_parse_file($this->stagrFilePath);
		$rawApps = $yaml['apps'];
		$apps = array();
		foreach ($rawApps as $name => $properties) {
        	array_push($apps, $name);
		}
		return $apps;
	}

	public function getApp($app)
	{
		$yaml = yaml_parse_file($this->stagrFilePath);
		$rawApps = $yaml['apps'];
		if (array_key_exists($app, $rawApps)) {
			return array(
				"name" => $app,
				"settings" => $rawApps[$app]
			);
		} else {
			return false;
		}
	}

	public function createNewApp($name)
	{
		return shell_exec("sudo /usr/bin/stagr setup " . $name);
	}

	public function createStagrFile($email, $key)
	{
		$data = array(
			"email" => $email,
			"sshkeys" => array(
				$key
			),
			"apps" => array()
		);
		file_put_contents($this->stagrFilePath, yaml_emit($data));
	}

	public function saveApp($name, $settings)
	{
		$cmd = 'sudo /usr/bin/stagr set ' . $name . ' ' . implode(" ", $settings);
		error_log("RUN CMD: $cmd");
		exec($cmd);
	}

	public function getTimeZones()
	{
		return array(
			"Europe/Amsterdam",
			"Europe/Andorra",
			"Europe/Athens",
			"Europe/Belfast",
			"Europe/Belgrade",
			"Europe/Berlin",
			"Europe/Bratislava",
			"Europe/Brussels",
			"Europe/Bucharest",
			"Europe/Budapest",
			"Europe/Copenhagen",
			"Europe/Dublin",
			"Europe/Helsinki",
			"Europe/Istanbul",
			"Europe/Kaliningrad",
			"Europe/Kiev",
			"Europe/Lisbon",
			"Europe/Ljubljana",
			"Europe/London",
			"Europe/Luxembourg",
			"Europe/Madrid",
			"Europe/Minsk",
			"Europe/Monaco",
			"Europe/Moscow",
			"Europe/Oslo",
			"Europe/Paris",
			"Europe/Prague",
			"Europe/Riga",
			"Europe/Rome",
			"Europe/Sarajevo",
			"Europe/Sofia",
			"Europe/Stockholm",
			"Europe/Tallinn",
			"Europe/Vienna",
			"Europe/Vilnius",
			"Europe/Volgograd",
			"Europe/Warsaw",
			"Europe/Zagreb",
			"Europe/Zurich",
			"America/Puerto_Rico",
			"America/New_York",
			"America/Chicago",
			"America/Boise",
			"America/Phoenix",
			"America/Los_Angeles",
			"America/Detroit",
			"America/Mexico_City",
			"America/Montreal",
			"America/Argentina/Buenos_Aires",
			"Australia/ACT",
			"Australia/Brisbane",
			"Australia/LHI",
			"Australia/Melbourne",
			"Australia/North",
			"Australia/NSW",
			"Australia/South",
			"Australia/Sydney",
			"Australia/West",
			"CET",
			"WET",
			"EET",
			"Etc/GMT",
			"Etc/GMT+0",
			"Etc/GMT+1",
			"Etc/GMT+10",
			"Etc/GMT+11",
			"Etc/GMT+12",
			"Etc/GMT+2",
			"Etc/GMT+3",
			"Etc/GMT+4",
			"Etc/GMT+5",
			"Etc/GMT+6",
			"Etc/GMT+7",
			"Etc/GMT+8",
			"Etc/GMT+9",
			"Etc/GMT-0",
			"Etc/GMT-1",
			"Etc/GMT-10",
			"Etc/GMT-11",
			"Etc/GMT-12",
			"Etc/GMT-13",
			"Etc/GMT-14",
			"Etc/GMT-2",
			"Etc/GMT-3",
			"Etc/GMT-4",
			"Etc/GMT-5",
			"Etc/GMT-6",
			"Etc/GMT-7",
			"Etc/GMT-8",
			"Etc/GMT-9",
			"Etc/GMT0",
			"Etc/Greenwich",
			"Etc/UCT",
			"Etc/Universal",
			"Etc/UTC"
		);
	}

	public function getExecutionTimes()
	{
		return array(
			"10",
			"30",
			"60",
			"120",
			"240",
			"300",
			"600",
			"1200"
		);
	}

	public function getMemorySizes()
	{
		return array(
			"64M",
			"128M"
		);
	}

	public function getApcSizes()
	{
		return array(
			"64M",
			"128M"
		);
	}

	public function getUploadSizes()
	{
		return array(
			"64M",
			"128M",
			"256M"
		);
	}

	public function getPostSizes()
	{
		return array(
			"64M",
			"128M",
			"256M"
		);
	}

	public function getOutputBufferSizes()
	{
		return array(
			"0",
			"4096"
		);
	}

}

