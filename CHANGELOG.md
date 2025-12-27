## What's Changed

This release introduces enhanced security for webhook processing, corrects a critical deployment path, and adds extensive documentation to the project.

### ✨ Features
*   **Webhook Security:** Add HMAC signature validation for OpenPix webhooks to ensure data integrity and authenticity.
*   **Webhook Processing:** Introduce event constants and enhance charge data parsing for more robust and reliable webhook handling.

### 🐛 Bug Fixes
*   **Deployment:** Correct file and directory references from `udia-pods-thankyou` to `triqhub-thank-you` in the deployment workflow, ensuring successful builds and deployments.

### 📚 Documentation
*   Add extremely detailed documentation covering Architecture, API, and Guides.
*   Generate comprehensive project documentation using the Deepseek agent.

### 🔧 Maintenance
*   Bump project version to 1.0.19.
