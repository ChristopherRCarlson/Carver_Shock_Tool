# Security Policy

## Supported Versions

Only the latest version of the Carver Shock Tool is supported for security updates.

| Version | Supported          |
| ------- | ------------------ |
| Main    | :white_check_mark: |
| < 4.0.0 | :x:                |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Please report any security concerns directly to the project administrator.
Reports should include:

- A description of the vulnerability.
- Steps to reproduce (POC).
- Potential impact.

## Security Architecture Notes

- **Internal Only:** This tool is designed for LAN deployment. It should never be exposed to the public internet.
- **Audit Logging:** All changes to the CSV database are logged via `system_files/audit_logger.php`.
- **Data Sanitization:** The tool uses `clean_input()` to strip legacy Excel artifacts and whitespace.
