# Robust Path Resolution

When determining the root directory path in shared PHP components (like navbars or headers) that can be included from multiple directory levels:

- **DO NOT** use hardcoded directory name checks (e.g., `basename(dirname($_SERVER['SCRIPT_FILENAME'])) !== 'landing'`). This approach breaks if the project directory is renamed or hosted under a different folder (like in localhost environments).
- **DO** use robust, relative path detection methods. For example, checking for the existence of a known file relative to the current script (e.g., `!file_exists('includes/navbar.php')`) or defining a central `BASE_URL` constant in a core configuration file.
