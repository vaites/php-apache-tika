#!/usr/bin/env bash

hyperfine --warmup 3 "php scripts/bench.php --cli" "php scripts/bench.php --rest"