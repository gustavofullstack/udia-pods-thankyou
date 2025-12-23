# DeepSeek AI - Changelog Generator Configuration

## API Details
- **Model**: deepseek-chat
- **API Key**: sk-f233af4f1527475eb89fb5aa48c0a1d3
- **Endpoint**: https://api.deepseek.com/v1/chat/completions

## Features

### Automatic Changelog Generation
Every time you push to `main`, GitHub Actions will:
1. ✅ Detect new commits since last release
2. ✅ Send commits to DeepSeek AI
3. ✅ Generate professional, categorized changelog
4. ✅ Create GitHub Release with changelog
5. ✅ Bump plugin version automatically

### Changelog Format

DeepSeek generates changelogs with:
- **Emojis** for visual clarity (✨, 🐛, 📚, ⚡, 🔧)
- **Categories**: Features, Bug Fixes, Documentation, Performance, Maintenance
- **User-friendly descriptions** (not just raw commit messages)
- **Breaking changes** highlighted with ⚠️
- **Markdown formatting** ready for GitHub

### Example Output

```markdown
## What's Changed

### ✨ Features
- Add Woovi PIX payment gateway integration with QR code generation
- Implement real-time payment confirmation via webhooks
- Add premium glassmorphism UI with countdown timer

### 🐛 Bug Fixes
- Fix payment gateway registration timing issue
- Correct CSS syntax error in mobile responsive layout

### 📚 Documentation
- Add comprehensive troubleshooting guide
- Create implementation walkthrough with screenshots

### ⚡ Performance
- Optimize API calls to reduce checkout latency
- Implement caching for QR code images
```

## How It Works

### Workflow Trigger
```yaml
on:
  push:
    branches:
      - main
    paths:
      - '**.php'
      - '**.css'
      - '**.js'
```

### DeepSeek Integration
```bash
curl https://api.deepseek.com/v1/chat/completions \
  -H "Authorization: Bearer sk-f233af4f1527475eb89fb5aa48c0a1d3" \
  -d '{
    "model": "deepseek-chat",
    "messages": [{
      "role": "user",
      "content": "Generate changelog from: [commits]"
    }]
  }'
```

### Version Bumping
- Automatically increments patch version (1.0.2 → 1.0.3)
- Updates `udia-pods-thankyou.php` header
- Commits back to repository

### Release Creation
- Creates GitHub Release with tag `v1.0.3`
- Attaches `udia-pods-thankyou.zip`
- Includes DeepSeek-generated changelog
- Adds installation instructions

## WordPress Integration

### Auto-Update Detection

The plugin uses `plugin-update-checker` library:

```php
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/gustavofullstack/udia-pods-thankyou',
    __FILE__,
    'udia-pods-thankyou'
);
```

### Where Updates Appear

1. **Dashboard → Updates**
   ```
   Plugins (1)
   ✓ Udia Pods Pós-Checkout Experience
     There is a new version available. View version 1.0.3 details.
     [Update Now]
   ```

2. **Plugins Page**
   ```
   Udia Pods Pós-Checkout Experience
   Version 1.0.2 | By Udia Pods
   [There is a new version of Udia Pods Pós-Checkout Experience available. View version 1.0.3 details or update now.]
   ```

3. **Plugin Details Modal**
   - Click "View version 1.0.3 details"
   - Shows DeepSeek-generated changelog
   - Displays installation instructions
   - Provides download link

## Testing

### Test Auto-Update
1. Make a code change
2. Commit and push to main
3. Wait 2-3 minutes for GitHub Action
4. Check GitHub Releases page
5. Go to WordPress → Dashboard → Updates
6. Click "Check Again"
7. Should see new version available!

### Manual Test
```bash
# Trigger workflow manually
gh workflow run deploy.yml

# Check status
gh run list

# View logs
gh run view [run-id] --log
```

## Troubleshooting

### Update Not Appearing
**Solution**: Wait 10-15 minutes for WordPress transient cache to expire, or click "Check Again"

### DeepSeek API Error
**Check**: GitHub Actions logs for API response
**Verify**: API key is valid and has credits

### Release Not Created
**Check**: GitHub Actions permissions (needs `contents: write`)
**Verify**: No conflicts in version numbers

## Cost Estimate

### DeepSeek API Pricing
- Model: deepseek-chat
- Cost: ~$0.001 per request
- Tokens: ~2000 per changelog
- **Monthly cost**: < $0.10 (for ~100 releases)

**Conclusion**: Essentially free! 🎉

## Security

### API Key Storage
- ✅ Stored in GitHub Actions workflow file
- ✅ Not exposed in logs or artifacts
- ✅ Only accessible by workflow runners
- ⚠️ Visible in `.github/workflows/deploy.yml` (private repo only)

### Best Practice (Optional)
For public repositories, use GitHub Secrets:
1. Go to: Settings → Secrets → Actions
2. Add: `DEEPSEEK_API_KEY` = `sk-f233af4f1527475eb89fb5aa48c0a1d3`
3. Update workflow: `${{ secrets.DEEPSEEK_API_KEY }}`

## Future Enhancements

### Potential Additions
- 📊 Generate release statistics (files changed, lines added/removed)
- 🏷️ Auto-tag contributors in changelog
- 📸 Include screenshots in release notes
- 🌐 Multi-language changelog generation
- 📧 Email notifications to team on new release
- 🔗 Post release notes to Slack/Discord

### Advanced DeepSeek Prompts
```javascript
{
  "role": "system",
  "content": "You are a senior WordPress plugin developer creating release notes."
}
```

This would make DeepSeek optimize specifically for WordPress context!

---

**Maintained by**: gustavofullstack (almeida.me@icloud.com)  
**Last Updated**: 2025-12-23
