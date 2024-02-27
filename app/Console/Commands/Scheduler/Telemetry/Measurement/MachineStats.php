<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use InfluxDB\Point;

class MachineStats extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getPoints(): array
    {
        $cpuLoadAvg = sys_getloadavg();
        $memStats = $this->getMemStats();
        $totalDiskSpace = (int) disk_total_space('/');
        $usedDiskSpace = (int) ($totalDiskSpace - disk_free_space('/'));

        $tags = array_merge($this->getTags(), ['server' => 'maisie']);

        return [
            new Point(
                'cpu',
                null,
                $tags,
                [
                    'load_percent' => (is_array($cpuLoadAvg) ? $cpuLoadAvg[1] : 0),
                ],
                time()
            ),

            new Point(
                'mem',
                null,
                $tags,
                [
                    'total' => $memStats['MemTotal'],
                    'used' => $memStats['MemTotal'] - $memStats['MemAvailable'],
                    'used_percent' => round((($memStats['MemTotal'] - $memStats['MemAvailable']) / $memStats['MemTotal']) * 100, 2),
                ],
                time()
            ),

            new Point(
                'disk',
                null,
                $tags,
                [
                    'total' => $totalDiskSpace,
                    'used' => $totalDiskSpace,
                    'used_percent' => round(($usedDiskSpace / $totalDiskSpace) * 100, 2),
                ],
                time()
            ),

        ];
    }

    /**
     * @see https://github.com/dimitar-kunchev/proc-meminfo/blob/master/ProcMeminfo.php
     */
    public function getMemStats(): array
    {
        $fh = fopen('/proc/meminfo', 'r');
        $out = [];
        $multipliers = ['kb' => 1024, 'mb' => 1024 * 1024, 'gb' => 1024 * 1024 * 1024, 'tb' => 1024 * 1024 * 1024 * 1024];

        while ($line = fgets($fh)) {
            [$key, $val] = explode(':', $line, 2);
            $val = trim($val);
            $chunk = explode(' ', $val, 2);
            $val = intval($chunk[0]);
            if (count($chunk) > 1) {
                $suffix = strtolower($chunk[1]);
                $val *= $multipliers[$suffix] ?? 1;
            }

            $out[$key] = $val;
        }

        fclose($fh);

        return $out;
    }

    //    /**
    //     * @return array|null
    //     * @see https://www.php.net/manual/en/function.sys-getloadavg.php#118673
    //     */
    //    function _getServerLoadLinuxData(): ?array
    //    {
    //        if (is_readable("/proc/stat")) {
    //            $stats = @file_get_contents("/proc/stat");
    //
    //            if ($stats !== false) {
    //                // Remove double spaces to make it easier to extract values with explode()
    //                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
    //
    //                // Separate lines
    //                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
    //                $stats = explode("\n", $stats);
    //
    //                // Separate values and find line for main CPU load
    //                foreach ($stats as $statLine) {
    //                    $statLineData = explode(" ", trim($statLine));
    //
    //                    // Found!
    //                    if ((count($statLineData) >= 5) && ($statLineData[0] == "cpu")) {
    //                        return array(
    //                            $statLineData[1],
    //                            $statLineData[2],
    //                            $statLineData[3],
    //                            $statLineData[4],
    //                        );
    //                    }
    //                }
    //            }
    //        }
    //
    //        return null;
    //    }
    //
    //    /**
    //     * @return float|int|mixed|null
    //     * @see https://www.php.net/manual/en/function.sys-getloadavg.php#118673
    //     */
    //    function getServerLoad(): ?float
    //    {
    //        $load = null;
    //
    //        if (stristr(PHP_OS, "win")) {
    //            $cmd = "wmic cpu get loadpercentage /all";
    //            @exec($cmd, $output);
    //
    //            if ($output) {
    //                foreach ($output as $line) {
    //                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
    //                        $load = (float)$line;
    //                        break;
    //                    }
    //                }
    //            }
    //        } else if (is_readable("/proc/stat")) {
    //            // Collect 2 samples - each with 1 second period
    //            // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
    //            $statData1 = $this->_getServerLoadLinuxData();
    //            sleep(1);
    //            $statData2 = $this->_getServerLoadLinuxData();
    //
    //            if (!is_null($statData1) && !is_null($statData2)) {
    //                // Get difference
    //                $statData2[0] -= $statData1[0];
    //                $statData2[1] -= $statData1[1];
    //                $statData2[2] -= $statData1[2];
    //                $statData2[3] -= $statData1[3];
    //
    //                // Sum up the 4 values for User, Nice, System and Idle and calculate
    //                // the percentage of idle time (which is part of the 4 values!)
    //                $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];
    //
    //                // Invert percentage to get CPU time, not idle time
    //                $load = (float)100 - ($statData2[3] * 100 / $cpuTime);
    //            }
    //        }
    //
    //        return $load;
    //    }
}
