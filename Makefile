PHP ?= php
BENCHMARK_PHP ?= $(PHP) -d opcache.enable_cli=1 -d opcache.jit=tracing -d opcache.jit_buffer_size=128M

.PHONY: benchmark benchmark-generate benchmark-serde benchmark-client benchmark-update-results benchmark-clean
.NOTPARALLEL: benchmark

benchmark: benchmark-serde benchmark-client

benchmark-generate:
	$(PHP) benchmarks/serde/generate.php

benchmark-serde: benchmark-generate
	$(BENCHMARK_PHP) benchmarks/serde/run.php

benchmark-client: benchmark-generate
	$(BENCHMARK_PHP) benchmarks/client/run.php

benchmark-update-results: benchmark-generate
	$(BENCHMARK_PHP) benchmarks/serde/run.php --update-readme
	$(BENCHMARK_PHP) benchmarks/client/run.php --update-readme

benchmark-clean:
	$(PHP) benchmarks/serde/generate.php --clean
