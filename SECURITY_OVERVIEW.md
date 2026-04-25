# Security and Data Privacy Overview - Serbisko v2

This document outlines the security layers implemented to ensure the privacy of the application and protection against data leaks.

## 1. Network Privacy: Tailscale Mesh VPN
- **Purpose:** Acts as the primary gateway. The application is invisible to the public internet and the local physical network (Wi-Fi/LAN).
- **Mechanism:** Only devices authenticated with the authorized Tailscale account can resolve the server's IP (`100.122.145.109`).

## 2. Infrastructure Security: UFW Firewall Lockdown
- **Purpose:** Restricts traffic at the OS level.
- **Mechanism:** 
    - Default Deny for all incoming traffic.
    - Explicitly allows traffic only via the `tailscale0` interface.
    - Blocks all access from the local subnet (e.g., `192.168.0.x`).

## 3. Application Hardening: Laravel Production Config
- **Status:** `APP_DEBUG=false` | `APP_ENV=production`
- **Purpose:** Prevents information disclosure. 
- **Mechanism:** Detailed error traces (which include database credentials and environment variables) are suppressed and replaced with generic error messages.

## 4. Database Isolation: Loopback Binding
- **Status:** `bind-address=127.0.0.1`
- **Purpose:** Prevents remote database hijacking.
- **Mechanism:** The MySQL service (XAMPP) is configured to only accept connections from the local machine. It is unreachable even from other devices within the Tailscale network.

## 5. Intrusion Prevention: Fail2Ban
- **Purpose:** Protects against SSH brute-force attacks.
- **Mechanism:** Automatically bans IP addresses that exhibit suspicious login behavior, adding a dynamic layer of defense to the SSH port.

## 6. Access Control: Linux Permissions
- **Purpose:** Ensures data integrity and prevents unauthorized file access.
- **Mechanism:** Optimized `chown` and `chmod` settings for `storage` and `bootstrap/cache` directories, ensuring only the web server user (`daemon`) has the necessary write permissions.

---
*Document generated on: 2026-04-25*
