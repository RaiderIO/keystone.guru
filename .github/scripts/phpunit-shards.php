<?php

/**
 * Splits the PHPUnit suite over the php-tests shard jobs and verifies afterwards that the shards
 * together ran every test exactly once.
 *
 * Runs on the runner's own PHP as well as the setup-php one, so it uses nothing beyond PHP 8.3 and
 * ext-dom / ext-simplexml.
 *
 *   split <shard> <total> <timings.json> <test-files.txt> <phpunit.xml> <out.xml> [<reserved seconds on the last shard>]
 *       <test-files.txt> is the output of `phpunit --list-test-files` with the same group filters
 *       the shard runs with. Assigns every file to exactly one shard, longest first onto the
 *       currently lightest shard (files without a timing weigh the mean), and writes <out.xml>: a
 *       copy of <phpunit.xml> whose only test suite is this shard's files, longest first.
 *
 *   verify <artifact dir>
 *       <artifact dir> holds one sub directory per shard, each with the `phpunit --list-tests`
 *       output of the whole suite (phpunit-expected-tests.txt) and the junit files the shard wrote.
 *       Fails unless every shard listed the same suite and the junit files hold exactly the listed
 *       number of tests for every test class.
 *
 *   timings <junit.xml>...
 *       Prints per-file seconds (averaged over the given junit files) as JSON - the content of
 *       .github/phpunit-timings.json.
 */

const EXPECTED_TESTS_FILE = 'phpunit-expected-tests.txt';

/**
 * @return list<string>
 */
function readListedLines(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fail(sprintf('Unable to read %s', $path));
    }

    $result = [];
    foreach ($lines as $line) {
        if (str_starts_with($line, ' - ')) {
            $result[] = substr($line, 3);
        }
    }

    return $result;
}

function relativeTestPath(string $path): string
{
    $position = strpos($path, '/tests/');

    return $position === false ? $path : substr($path, $position + 1);
}

function fail(string $message): never
{
    fwrite(STDERR, sprintf("::error::%s\n", $message));
    exit(1);
}

/**
 * @param list<string> $arguments
 */
function split(array $arguments): void
{
    if (count($arguments) < 6) {
        fail('split <shard> <total> <timings.json> <test-files.txt> <phpunit.xml> <out.xml> [<reserved seconds on the last shard>]');
    }

    [$shard, $total, $timingsPath, $testFilesPath, $configPath, $outPath] = $arguments;
    $shard                                                                = (int)$shard;
    $total                                                                = (int)$total;
    $reservedSeconds                                                      = (float)($arguments[6] ?? 0);
    if ($total < 1 || $shard < 1 || $shard > $total) {
        fail(sprintf('Invalid shard %d of %d', $shard, $total));
    }

    /** @var array<string, float> $timings */
    $timings = json_decode((string)file_get_contents($timingsPath), true, flags: JSON_THROW_ON_ERROR);

    $files = array_values(array_unique(array_map(relativeTestPath(...), readListedLines($testFilesPath))));
    if ($files === []) {
        fail(sprintf('%s lists no test files', $testFilesPath));
    }

    $known        = array_intersect_key($timings, array_flip($files));
    $fallback     = $known === [] ? 1.0 : array_sum($known) / count($known);
    $weights      = [];
    $unknownCount = 0;
    foreach ($files as $file) {
        if (isset($timings[$file])) {
            $weights[$file] = (float)$timings[$file];
        } else {
            $weights[$file] = $fallback;
            $unknownCount++;
        }
    }

    // Longest first, path as the tie-breaker, so every shard computes the same assignment.
    uksort($weights, static fn(string $a, string $b): int => [$weights[$b], $a] <=> [$weights[$a], $b]);

    $loads = array_fill(1, $total, 0.0);
    $loads[$total] += $reservedSeconds;
    $assigned = array_fill(1, $total, []);
    foreach ($weights as $file => $weight) {
        $lightest = 1;
        foreach ($loads as $candidate => $load) {
            if ($load < $loads[$lightest]) {
                $lightest = $candidate;
            }
        }
        $assigned[$lightest][] = $file;
        $loads[$lightest] += $weight;
    }

    $document                     = new DOMDocument();
    $document->preserveWhiteSpace = false;
    $document->formatOutput       = true;
    if (!$document->load($configPath)) {
        fail(sprintf('Unable to parse %s', $configPath));
    }
    $root = $document->documentElement;
    foreach (iterator_to_array($root->getElementsByTagName('testsuites')) as $existing) {
        $root->removeChild($existing);
    }
    $testSuites = $document->createElement('testsuites');
    $testSuite  = $document->createElement('testsuite');
    $testSuite->setAttribute('name', sprintf('Shard %d of %d', $shard, $total));
    foreach ($assigned[$shard] as $file) {
        $testSuite->appendChild($document->createElement('file', sprintf('./%s', $file)));
    }
    $testSuites->appendChild($testSuite);
    $root->insertBefore($testSuites, $root->firstChild);
    if ($document->save($outPath) === false) {
        fail(sprintf('Unable to write %s', $outPath));
    }

    printf("%d test files, %d without a timing (weighed %.1fs each)\n", count($files), $unknownCount, $fallback);
    foreach ($loads as $index => $load) {
        printf(
            "%s shard %d: %3d files, %6.1fs estimated%s\n",
            $index === $shard ? '>' : ' ',
            $index,
            count($assigned[$index]),
            $load,
            $index === $total && $reservedSeconds > 0 ? sprintf(' (incl. %.0fs reserved)', $reservedSeconds) : '',
        );
    }
}

/**
 * @return array<string, int>
 */
function countJunitTestsPerClass(string $junitPath, float &$seconds): array
{
    $xml = simplexml_load_file($junitPath);
    if ($xml === false) {
        fail(sprintf('Unable to parse %s', $junitPath));
    }

    $counts = [];
    foreach ($xml->xpath('//testcase') ?: [] as $testCase) {
        $class          = (string)$testCase['class'];
        $counts[$class] = ($counts[$class] ?? 0) + 1;
        $seconds += (float)$testCase['time'];
    }

    return $counts;
}

/**
 * @param list<string> $arguments
 */
function verify(array $arguments): void
{
    if (count($arguments) !== 1) {
        fail('verify <artifact dir>');
    }

    $shardDirectories = glob(sprintf('%s/*', rtrim($arguments[0], '/')), GLOB_ONLYDIR) ?: [];
    sort($shardDirectories);
    if ($shardDirectories === []) {
        fail(sprintf('No shard artifacts under %s', $arguments[0]));
    }

    $expectedTests = null;
    $executed      = [];
    foreach ($shardDirectories as $shardDirectory) {
        $listPath = sprintf('%s/%s', $shardDirectory, EXPECTED_TESTS_FILE);
        if (!is_file($listPath)) {
            fail(sprintf('%s is missing', $listPath));
        }
        $listed = readListedLines($listPath);
        if ($expectedTests === null) {
            $expectedTests = $listed;
        } elseif ($listed !== $expectedTests) {
            fail(sprintf('%s lists a different suite than %s', $listPath, $shardDirectories[0]));
        }

        $junitPaths = glob(sprintf('%s/*.xml', $shardDirectory)) ?: [];
        if ($junitPaths === []) {
            fail(sprintf('%s holds no junit file', $shardDirectory));
        }
        $shardCount   = 0;
        $shardSeconds = 0.0;
        foreach ($junitPaths as $junitPath) {
            foreach (countJunitTestsPerClass($junitPath, $shardSeconds) as $class => $count) {
                $executed[$class] = ($executed[$class] ?? 0) + $count;
                $shardCount += $count;
            }
        }
        printf("%s: %d tests, %.1fs of test time\n", basename($shardDirectory), $shardCount, $shardSeconds);
    }

    $expected = [];
    foreach ($expectedTests as $test) {
        $class            = explode('::', $test, 2)[0];
        $expected[$class] = ($expected[$class] ?? 0) + 1;
    }

    $mismatches = [];
    foreach (array_keys($expected + $executed) as $class) {
        if (($expected[$class] ?? 0) !== ($executed[$class] ?? 0)) {
            $mismatches[] = sprintf('%s: listed %d, ran %d', $class, $expected[$class] ?? 0, $executed[$class] ?? 0);
        }
    }

    printf("Listed %d tests in %d classes, ran %d\n", array_sum($expected), count($expected), array_sum($executed));
    if ($mismatches !== []) {
        echo implode("\n", $mismatches), "\n";
        fail(sprintf('%d test classes ran a different number of tests than PHPUnit lists', count($mismatches)));
    }
}

/**
 * @param list<string> $arguments
 */
function timings(array $arguments): void
{
    if ($arguments === []) {
        fail('timings <junit.xml>...');
    }

    $sums = [];
    foreach ($arguments as $junitPath) {
        $xml = simplexml_load_file($junitPath);
        if ($xml === false) {
            fail(sprintf('Unable to parse %s', $junitPath));
        }
        $perFile = [];
        foreach ($xml->xpath('//testcase') ?: [] as $testCase) {
            $file           = relativeTestPath((string)$testCase['file']);
            $perFile[$file] = ($perFile[$file] ?? 0.0) + (float)$testCase['time'];
        }
        foreach ($perFile as $file => $seconds) {
            $sums[$file][] = $seconds;
        }
    }

    $result = [];
    foreach ($sums as $file => $samples) {
        $result[$file] = round(array_sum($samples) / count($samples), 2);
    }
    ksort($result);

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
}

$mode = $argv[1] ?? '';
$rest = array_slice($argv, 2);
match ($mode) {
    'split'   => split($rest),
    'verify'  => verify($rest),
    'timings' => timings($rest),
    default   => fail('Usage: phpunit-shards.php split|verify|timings ...'),
};
