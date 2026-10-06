PHP ?= php

.PHONY: benchmark benchmark-generate benchmark-serde benchmark-client benchmark-update-results benchmark-clean

benchmark: benchmark-serde benchmark-client

benchmark-generate:
	$(PHP) benchmarks/serde/generate.php

benchmark-serde: benchmark-generate
	$(PHP) benchmarks/serde/run.php

benchmark-client: benchmark-generate
	$(PHP) benchmarks/client/run.php

benchmark-update-results: benchmark-generate
	$(PHP) benchmarks/serde/run.php --update-readme
	$(PHP) benchmarks/client/run.php --update-readme

benchmark-clean:
	$(PHP) benchmarks/serde/generate.php --clean
