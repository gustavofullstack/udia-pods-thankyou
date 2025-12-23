## What's Changed

This release focuses on improving the reliability of the application and its release process. The main fix resolves a critical validation error, while a new test ensures the automated release system functions correctly.

### 🐛 Bug Fixes
*   **Resolve AppID validation error and add customer object:** Fixes a bug where the application failed due to an invalid AppID during validation. The fix also properly integrates the customer object into the relevant processes.

### 🔧 Maintenance
*   **Add automated release system validation:** Introduces a new automated test to validate the integrity and functionality of the release pipeline, helping to prevent issues in future deployments.
