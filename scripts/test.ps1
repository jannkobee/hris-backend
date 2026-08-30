param(
    [ValidateSet('All', 'Unit', 'Feature')]
    [string] $Suite = 'All'
)

$phpArguments = @()

php -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    $phpArguments += @('-d', 'extension=pdo_sqlite')
}

php -r "exit(extension_loaded('sqlite3') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    $phpArguments += @('-d', 'extension=sqlite3')
}

$phpArguments += 'vendor/bin/phpunit'

if ($Suite -ne 'All') {
    $phpArguments += @('--testsuite', $Suite)
}

& php @phpArguments
exit $LASTEXITCODE
