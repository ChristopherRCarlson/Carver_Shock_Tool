# Carver Shock Tool (Internal)

A networked web application developed to manage shock absorber specifications and Bills of Materials (BOM).

## Technical Overview

* **Architecture:** Host-Client LAN deployment using a PHP built-in server.
* **Data Storage:** Relational SQLite database (`carver_database.sqlite`) utilizing PDO for secure, exception-based data handling.
* **Code Quality & Security:**
  * **Static Analysis:** Integrated Psalm for type-checking and identifying potential bugs.
  * **Linting:** PHP_CodeSniffer implementation to maintain consistent coding standards.
* **Concurrency:** Leverages SQLite’s native locking and ACID-compliant transactions.

## Key Features

* **Audit Trail:** Automated logging system that tracks record changes, actions, and timestamps to provide a full history of database updates.
* **Real-time Dashboard:** A centralized interface showing the current database status and the exact time of the most recent update.
* **Post-Redirect-Get (PRG):** Maintains a pattern to prevent duplicate form submissions during data entry.
* **Input Sanitization:** Automated cleanup of legacy artifacts and protection against common injection vulnerabilities via prepared statements.
* **Integrated Modules:**
  * **Shock Lookup:** Optimized search for specifications and BOMs.
  * **Data Entry:** Internal tools for adding and updating shock information.
  * **History Viewer:** Interface for searching and reviewing the audit logs.

## Note

This repository contains the source code for the interface and logic. The proprietary database and server configuration files are excluded for security.
