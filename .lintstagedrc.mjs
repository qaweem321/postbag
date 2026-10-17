import path from "node:path";

// PHP tools run inside the Docker image, where the project lives at /app: pass relative paths.
const relative = (files) => files.map((file) => path.relative(process.cwd(), file)).join(" ");

export default {
  "**/*.php": (files) => `docker compose run --rm -T app vendor/bin/pint ${relative(files)}`,
};
