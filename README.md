# Carver Shock Tool (Internal)

A networked web application developed to manage shock absorber specifications and Bills of Materials (BOM).

## Technical Overview
* **Architecture:** Host-Client LAN deployment (PHP built-in server).
* **Data Storage:** Flat-file CSV database with 30+ columns.
* **Key Features:**
    * **Post-Redirect-Get (PRG)** pattern to prevent duplicate submissions.
    * **File Locking:** `flock()` implementation for concurrency safety.
    * **Input Sanitization:** Automated cleanup of legacy Excel artifacts (NaN, #N/A).
    * **Smart Search:** Real-time lookup with E-Commerce integration.

## Note
This repository contains the source code for the interface and logic. The proprietary database and server configuration files are excluded for security.
