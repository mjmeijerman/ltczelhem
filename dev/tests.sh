#!/bin/sh

# Unit tests
vendor/bin/codecept run unit
codecept_unit_exit_code=$?

make analyse
phpstan_exit_code=$?

# Always run all the test tools, but exit with
# a non-zero exit code on failures for Travis.
exit $((codecept_unit_exit_code + phpstan_exit_code))
