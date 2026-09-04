# Validation Report

The project was checked before packaging.

- All PHP files pass `php -l` syntax validation.
- The JavaScript file passes `node --check` syntax validation.
- All named `url('...')` route references map to defined MVC routes.
- CSS brace balance is valid.
- Demo password hashes were verified with `password_verify()`.

A live MySQL integration test was not run in the build container because that runtime does not include the PDO MySQL driver/server. The target environment is XAMPP, where `pdo_mysql` is normally enabled. Follow `README.md` to import `database.sql` and run the project.
