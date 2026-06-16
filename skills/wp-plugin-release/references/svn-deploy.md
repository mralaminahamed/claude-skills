# SVN Deploy to WordPress.org

## Initial Checkout (first time only)

```bash
# Slow — checks out the full repo including all tags
svn checkout https://plugins.svn.wordpress.org/my-plugin /tmp/my-plugin-svn --depth immediates

# Speed up: only expand trunk and assets, not tags
svn update /tmp/my-plugin-svn/trunk --set-depth infinity
svn update /tmp/my-plugin-svn/assets --set-depth infinity
svn update /tmp/my-plugin-svn/tags --set-depth immediates
```

## Commit New Release

```bash
# 1. Copy built plugin files to trunk (exclude dev files)
rsync -avz \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='src' \
  --exclude='tests' \
  --exclude='phpunit.xml.dist' \
  --exclude='phpcs.xml.dist' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  /path/to/my-plugin/ /tmp/my-plugin-svn/trunk/

# 2. Stage all changes (add new files, delete removed files)
cd /tmp/my-plugin-svn/trunk

# Add new files
svn status | grep '^?' | awk '{print $2}' | xargs svn add

# Delete removed files  
svn status | grep '^!' | awk '{print $2}' | xargs svn delete

# Review changes
svn status

# 3. Commit trunk
svn commit -m "Release 1.2.0" --username your-wp-org-username

# 4. Create tag from trunk
svn copy \
  https://plugins.svn.wordpress.org/my-plugin/trunk \
  https://plugins.svn.wordpress.org/my-plugin/tags/1.2.0 \
  -m "Tagging version 1.2.0" \
  --username your-wp-org-username
```

## Update Banner / Screenshots (assets/)

Assets (banners, icons, screenshots) live in `assets/`, NOT in `trunk/`:

```bash
# Copy assets
cp assets/* /tmp/my-plugin-svn/assets/

cd /tmp/my-plugin-svn
svn status | grep '^?' | awk '{print $2}' | xargs svn add
svn status | grep '^!' | awk '{print $2}' | xargs svn delete

svn commit assets/ -m "Update screenshots and banner" --username your-wp-org-username
```

Asset filenames:
```
assets/banner-772x250.png    # required
assets/banner-1544x500.png   # 2x (retina)
assets/icon-128x128.png      # required
assets/icon-256x256.png      # 2x (retina)
assets/screenshot-1.png      # matches readme.txt screenshot 1
assets/screenshot-2.jpg      # .jpg also supported
```

## Automated GitHub Actions Deploy

```yaml
# .github/workflows/deploy.yml
name: Deploy to WordPress.org

on:
  push:
    tags:
      - 'v[0-9]+.[0-9]+.[0-9]+'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Build assets
        run: npm ci && npm run build

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          tools: composer

      - name: Install PHP dependencies (production only)
        run: composer install --no-dev --prefer-dist --optimize-autoloader

      - name: Deploy to WordPress.org
        uses: 10up/action-wordpress-plugin-deploy@stable
        env:
          SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
          SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
          SLUG: my-plugin
          BUILD_DIR: .
          ASSETS_DIR: assets
```

Required GitHub secrets: `SVN_USERNAME`, `SVN_PASSWORD` (from wordpress.org account).

## SVN Tips

```bash
# See what's changed vs last commit
svn diff

# Revert uncommitted changes
svn revert --recursive trunk/

# List existing tags
svn list https://plugins.svn.wordpress.org/my-plugin/tags/

# Check trunk vs tag diff (after tagging)
svn diff \
  https://plugins.svn.wordpress.org/my-plugin/trunk \
  https://plugins.svn.wordpress.org/my-plugin/tags/1.2.0

# Delete a wrong tag (before it gets language packs)
svn delete \
  https://plugins.svn.wordpress.org/my-plugin/tags/1.2.0 \
  -m "Removing incorrect tag" \
  --username your-wp-org-username
```

## .svnignore / svn:ignore

```bash
# Set ignore on trunk
cd /tmp/my-plugin-svn/trunk
svn propset svn:ignore "
node_modules
src
tests
.git
.github
phpunit.xml.dist
phpcs.xml.dist
composer.json
composer.lock
package.json
package-lock.json
webpack.config.js
.env
" .
svn commit -m "Set svn:ignore"
```
