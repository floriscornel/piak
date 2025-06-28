#!/bin/bash

# check if example directory is provided, otherwise use current directory
if [ -z "$1" ]; then
    echo "No example directory provided, using current directory"
    example_dir="."
else
    example_dir="$1"
fi

echo "Running vacuum..."
if [ -f $example_dir/openapi.yaml ]; then
    vacuum lint $example_dir/openapi.yaml
else
    echo "No openapi.yaml file found in $example_dir, running vacuum on all openapi.yaml files"
    vacuum lint ./**/openapi.yaml
fi


echo "Running Pint..."
pint $example_dir

echo "Running PHPStan..."
phpstan analyze --memory-limit=512M --level=9 $example_dir

echo "Running PHPUnit..."
phpunit $example_dir

echo "Done!"