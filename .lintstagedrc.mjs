import path from "node:path";

// PHP tools run inside the Docker image, where the project lives at /app: pass relative paths.
const relativeTo = (dir, files) => files.map((file) => path.relative(path.join(process.cwd(), dir), file)).join(" ");

export default {
  // Laravel code: Pint (Laravel style).
  "{app,bootstrap,config,database,routes,tests}/**/*.php": (files) =>
    `docker compose run --rm -T app vendor/bin/pint ${relativeTo(".", files)}`,
  // The WordPress plugin follows the WordPress Coding Standards instead: check, don't rewrite.
  "wordpress-plugin/**/*.php": (files) =>
    `docker compose run --rm -T -w /app/wordpress-plugin app vendor/bin/phpcs ${relativeTo("wordpress-plugin", files)}`,
};
