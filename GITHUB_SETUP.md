# GitHub Repository Setup Guide

This guide will help you set up the `mumzworld/laravel-opentelemetry` package on GitHub.

## 🚀 Quick Setup

### Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com) and create a new repository
2. Repository name: `laravel-opentelemetry`
3. Organization: `mumzworld`
4. Description: `OpenTelemetry integration package for Laravel applications with complete observability stack`
5. Make it **Public** or **Private** (based on your preference)
6. **Don't** initialize with README (we already have one)

### Step 2: Push Package Code

```bash
cd /Users/sahibmmz/Projects/mmz/backend/laravel-opentelemetry

# Initialize git repository
git init

# Add all files
git add .

# Initial commit
git commit -m "Initial release v1.0.0

- TracerService for custom business logic tracing
- Complete Docker observability stack (Collector, Tempo, Grafana)
- Environment-driven configuration
- Automatic Laravel service provider registration
- Comprehensive documentation and examples
- Migration guide from custom implementations"

# Set main branch
git branch -M main

# Add remote origin (replace with your actual repository URL)
git remote add origin https://github.com/mumzworld/laravel-opentelemetry.git

# Push to GitHub
git push -u origin main
```

### Step 3: Create Release Tag

```bash
# Create and push version tag
git tag -a v1.0.0 -m "Release version 1.0.0

Features:
- Complete OpenTelemetry integration for Laravel
- Docker observability stack included
- Environment-driven configuration
- Production-ready setup"

git push origin v1.0.0
```

### Step 4: Verify Repository

1. Visit your repository: `https://github.com/mumzworld/laravel-opentelemetry`
2. Check that all files are present
3. Verify the release tag appears in the "Releases" section
4. Test clone access: `git clone https://github.com/mumzworld/laravel-opentelemetry.git`

## 📦 Using the Package

Once the repository is set up, you can install it in any Laravel project:

### Method 1: Via Composer (if public repository)

```bash
composer require mumzworld/laravel-opentelemetry:^1.0
```

### Method 2: Via VCS Repository

Add to `composer.json`:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mumzworld/laravel-opentelemetry"
        }
    ],
    "require": {
        "mumzworld/laravel-opentelemetry": "^1.0"
    }
}
```

Then run:
```bash
composer install
```

## 🔐 Private Repository Access

If using a private repository, team members need access:

### For HTTPS (Personal Access Token)

1. Create Personal Access Token in GitHub
2. Configure Composer auth:
```bash
composer config --global github-oauth.github.com YOUR_PERSONAL_ACCESS_TOKEN
```

### For SSH

1. Add SSH key to GitHub account
2. Use SSH URL in composer.json:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:mumzworld/laravel-opentelemetry.git"
        }
    ]
}
```

## 🏷️ Version Management

### Creating New Releases

```bash
# Make changes and commit
git add .
git commit -m "Add new feature"
git push

# Create new version tag
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0
```

### Semantic Versioning

- **Major** (v2.0.0): Breaking changes
- **Minor** (v1.1.0): New features, backward compatible
- **Patch** (v1.0.1): Bug fixes, backward compatible

## 🤝 Team Collaboration

### Repository Settings

1. **Branch Protection**: Protect `main` branch
2. **Required Reviews**: Require PR reviews
3. **Status Checks**: Add CI/CD checks
4. **Access Control**: Manage team permissions

### Contributing Workflow

1. Fork or create feature branch
2. Make changes
3. Create Pull Request
4. Code review
5. Merge to main
6. Create release tag

## 📞 Support

If you encounter issues:

1. Check repository access permissions
2. Verify Composer authentication
3. Ensure proper version constraints
4. Review GitHub repository settings

---

**Ready to use!** Once set up, the package can be used across all your Laravel projects.