#!/bin/bash

# Stock 360 - Quick SSH Deployment Setup
# This script automates the initial setup for SSH-based deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

info() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

# Banner
echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    Stock 360 Deployment Setup               ║"
echo "║                  SSH Pipeline Configuration                  ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running in project directory
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    error "Please run this script from your Laravel project root directory"
fi

log "🔍 Checking prerequisites..."

# Check for required tools
command -v ssh >/dev/null 2>&1 || error "SSH client is required but not installed"
command -v git >/dev/null 2>&1 || error "Git is required but not installed"

success "✅ Prerequisites check passed"

# Collect configuration
echo ""
log "📝 Please provide your server configuration:"

read -p "Server hostname/IP: " SERVER_HOST
read -p "SSH username: " SERVER_USER
read -p "SSH port (default 22): " SSH_PORT
SSH_PORT=${SSH_PORT:-22}

read -p "Git repository URL: " REPO_URL
read -p "Domain name (e.g., yourdomain.com): " DOMAIN_NAME

# Database configuration
echo ""
log "🗄️  Database configuration:"
read -p "Database name: " DB_NAME
read -p "Database username: " DB_USER
read -s -p "Database password: " DB_PASS
echo ""

# Email configuration
echo ""
log "📧 Email configuration:"
read -p "SMTP host (e.g., mail.yourdomain.com): " MAIL_HOST
read -p "SMTP username: " MAIL_USER
read -s -p "SMTP password: " MAIL_PASS
echo ""

# Generate SSH key if it doesn't exist
SSH_KEY_PATH="$HOME/.ssh/stock360_deploy_key"
if [ ! -f "$SSH_KEY_PATH" ]; then
    log "🔑 Generating SSH key pair..."
    ssh-keygen -t rsa -b 4096 -C "stock360-deploy@$(hostname)" -f "$SSH_KEY_PATH" -N ""
    success "SSH key generated: $SSH_KEY_PATH"
else
    info "SSH key already exists: $SSH_KEY_PATH"
fi

# Create deployment scripts directory
log "📁 Creating deployment scripts..."
mkdir -p deployment-scripts

# Create server setup script
cat > deployment-scripts/setup-server.sh << EOF
#!/bin/bash

# Stock 360 Server Setup Script
set -e

echo "🚀 Setting up Stock 360 deployment environment..."

# Configuration
APP_NAME="stock360"
APP_USER=\$(whoami)
APP_PATH="/home/\$APP_USER/\$APP_NAME"
WEB_PATH="/home/\$APP_USER/public_html"

# Create directory structure
echo "📁 Creating directory structure..."
mkdir -p \$APP_PATH/{releases,shared,repo,backups}
mkdir -p \$APP_PATH/shared/{storage/{app,framework,logs},uploads}
mkdir -p \$APP_PATH/shared/storage/framework/{cache,sessions,views}
mkdir -p \$APP_PATH/shared/storage/app/public

# Set permissions
chmod -R 775 \$APP_PATH/shared/storage
chmod -R 775 \$APP_PATH/shared/uploads

# Create shared .env file
if [ ! -f "\$APP_PATH/shared/.env" ]; then
    echo "📝 Creating shared .env file..."
    cat > \$APP_PATH/shared/.env << 'ENVEOF'
APP_NAME="Stock 360"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN_NAME}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=587
MAIL_USERNAME=${MAIL_USER}
MAIL_PASSWORD=${MAIL_PASS}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${MAIL_USER}
MAIL_FROM_NAME="\${APP_NAME}"
ENVEOF
    echo "✅ Environment file created"
fi

# Initialize git repository
if [ ! -d "\$APP_PATH/repo/.git" ]; then
    echo "🔧 Initializing git repository..."
    cd \$APP_PATH/repo
    git init
    git remote add origin ${REPO_URL}
fi

# Create scripts directory
mkdir -p /home/\$APP_USER/scripts

echo "✅ Server setup completed!"
echo "📝 Next steps:"
echo "   1. Review \$APP_PATH/shared/.env configuration"
echo "   2. Run your first deployment"
EOF

# Create main deployment script
cat > deployment-scripts/deploy.sh << 'EOF'
#!/bin/bash

# Stock 360 Deployment Script
set -e

# Configuration
APP_NAME="stock360"
APP_USER=$(whoami)
APP_PATH="/home/$APP_USER/$APP_NAME"
WEB_PATH="/home/$APP_USER/public_html"
REPO_URL="$(cd $APP_PATH/repo && git remote get-url origin)"
BRANCH="${1:-main}"
KEEP_RELEASES=5

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Start deployment
log "🚀 Starting deployment of Stock 360..."

# Create release directory
RELEASE_NAME=$(date +%Y%m%d-%H%M%S)
RELEASE_PATH="$APP_PATH/releases/$RELEASE_NAME"

log "📦 Creating release: $RELEASE_NAME"
mkdir -p $RELEASE_PATH

# Clone/update repository
log "📥 Updating repository..."
cd $APP_PATH/repo
if [ ! -d ".git" ]; then
    git clone $REPO_URL .
else
    git fetch origin
fi

git reset --hard origin/$BRANCH
git checkout $BRANCH

# Copy files to release directory
log "📋 Copying files to release directory..."
rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' $APP_PATH/repo/ $RELEASE_PATH/

# Create symbolic links to shared files
log "🔗 Creating symbolic links..."
cd $RELEASE_PATH

# Remove existing directories/files that should be linked
rm -rf storage .env

# Create symbolic links
ln -sf $APP_PATH/shared/.env .env
ln -sf $APP_PATH/shared/storage storage

# Install Composer dependencies
log "📦 Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
else
    warning "Composer not found, skipping dependency installation"
fi

# Install Node.js dependencies and build assets (if package.json exists)
if [ -f "package.json" ]; then
    log "🎨 Building frontend assets..."
    if command -v npm &> /dev/null; then
        npm ci --production
        npm run build
    else
        warning "npm not found, skipping asset build"
    fi
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" $APP_PATH/shared/.env; then
    log "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
log "🗃️  Running database migrations..."
php artisan migrate --force

# Cache optimization
log "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update web directory
log "🌐 Updating web directory..."
cd $WEB_PATH

# Backup current public files
if [ -L "index.php" ] || [ -f "index.php" ]; then
    log "📦 Backing up current public files..."
    mkdir -p $APP_PATH/backups/$(date +%Y%m%d-%H%M%S)
    cp -r * $APP_PATH/backups/$(date +%Y%m%d-%H%M%S)/ 2>/dev/null || true
fi

# Remove old public files
rm -rf *

# Copy new public files
cp -r $RELEASE_PATH/public/* .

# Update index.php to point to new release
cat > index.php << PHPEOF
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(\$maintenance = __DIR__.'/../$APP_NAME/releases/$RELEASE_NAME/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require __DIR__.'/../$APP_NAME/releases/$RELEASE_NAME/vendor/autoload.php';

\$app = require_once __DIR__.'/../$APP_NAME/releases/$RELEASE_NAME/bootstrap/app.php';

\$kernel = \$app->make(Kernel::class);

\$response = \$kernel->handle(
    \$request = Request::capture()
)->send();

\$kernel->terminate(\$request, \$response);
PHPEOF

# Create storage link
log "🔗 Creating storage link..."
ln -sf ../$APP_NAME/shared/storage/app/public storage

# Update current release symlink
log "🔄 Updating current release..."
cd $APP_PATH
rm -f current
ln -sf releases/$RELEASE_NAME current

# Clean up old releases
log "🧹 Cleaning up old releases..."
cd $APP_PATH/releases
ls -t | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

# Set proper permissions
log "🔒 Setting permissions..."
chmod -R 755 $RELEASE_PATH
chmod -R 775 $APP_PATH/shared/storage

success "🎉 Deployment completed successfully!"
log "📊 Release: $RELEASE_NAME"
log "🌐 Application URL: $(grep APP_URL $APP_PATH/shared/.env | cut -d'=' -f2)"
EOF

# Create rollback script
cat > deployment-scripts/rollback.sh << 'EOF'
#!/bin/bash

# Stock 360 Rollback Script
set -e

APP_NAME="stock360"
APP_USER=$(whoami)
APP_PATH="/home/$APP_USER/$APP_NAME"
WEB_PATH="/home/$APP_USER/public_html"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Get previous release
cd $APP_PATH/releases
RELEASES=($(ls -t))
CURRENT_RELEASE=$(readlink $APP_PATH/current | sed 's/releases\///')

# Find previous release
PREVIOUS_RELEASE=""
for release in "${RELEASES[@]}"; do
    if [ "$release" != "$CURRENT_RELEASE" ]; then
        PREVIOUS_RELEASE=$release
        break
    fi
done

if [ -z "$PREVIOUS_RELEASE" ]; then
    error "No previous release found for rollback"
fi

log "🔄 Rolling back from $CURRENT_RELEASE to $PREVIOUS_RELEASE"

# Update web directory
cd $WEB_PATH
rm -rf *
cp -r $APP_PATH/releases/$PREVIOUS_RELEASE/public/* .

# Update index.php
cat > index.php << PHPEOF
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(\$maintenance = __DIR__.'/../$APP_NAME/releases/$PREVIOUS_RELEASE/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require __DIR__.'/../$APP_NAME/releases/$PREVIOUS_RELEASE/vendor/autoload.php';

\$app = require_once __DIR__.'/../$APP_NAME/releases/$PREVIOUS_RELEASE/bootstrap/app.php';

\$kernel = \$app->make(Kernel::class);

\$response = \$kernel->handle(
    \$request = Request::capture()
)->send();

\$kernel->terminate(\$request, \$response);
PHPEOF

# Update current symlink
cd $APP_PATH
rm -f current
ln -sf releases/$PREVIOUS_RELEASE current

# Recreate storage link
cd $WEB_PATH
ln -sf ../$APP_NAME/shared/storage/app/public storage

success "✅ Rollback completed successfully!"
log "📊 Current release: $PREVIOUS_RELEASE"
EOF

# Create local deployment management script
cat > deployment-scripts/deploy-local.sh << EOF
#!/bin/bash

# Local deployment script for Stock 360
set -e

# Configuration
SERVER_HOST="${SERVER_HOST}"
SERVER_USER="${SERVER_USER}"
SSH_KEY="$SSH_KEY_PATH"
SSH_PORT="${SSH_PORT}"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "\${BLUE}[\$(date +'%H:%M:%S')]\${NC} \$1"
}

success() {
    echo -e "\${GREEN}[SUCCESS]\${NC} \$1"
}

# SSH connection function
ssh_exec() {
    ssh -i \$SSH_KEY -p \$SSH_PORT \$SERVER_USER@\$SERVER_HOST "\$1"
}

scp_upload() {
    scp -i \$SSH_KEY -P \$SSH_PORT "\$1" \$SERVER_USER@\$SERVER_HOST:"\$2"
}

# Commands
case "\$1" in
    "setup")
        log "🔧 Setting up server..."
        ssh_exec "mkdir -p ~/scripts"
        scp_upload "deployment-scripts/setup-server.sh" "~/scripts/"
        ssh_exec "chmod +x ~/scripts/setup-server.sh && ~/scripts/setup-server.sh"
        ;;
    "deploy")
        log "🚀 Deploying application..."
        scp_upload "deployment-scripts/deploy.sh" "~/scripts/"
        ssh_exec "chmod +x ~/scripts/deploy.sh && ~/scripts/deploy.sh \${2:-main}"
        ;;
    "rollback")
        log "🔄 Rolling back deployment..."
        scp_upload "deployment-scripts/rollback.sh" "~/scripts/"
        ssh_exec "chmod +x ~/scripts/rollback.sh && ~/scripts/rollback.sh"
        ;;
    "status")
        log "📊 Checking deployment status..."
        ssh_exec "cd ~/stock360 && ls -la current && echo '--- Recent Releases ---' && ls -la releases | tail -5"
        ;;
    "logs")
        log "📋 Fetching logs..."
        ssh_exec "tail -50 ~/stock360/shared/storage/logs/laravel.log"
        ;;
    "ssh")
        log "🔗 Connecting to server..."
        ssh -i \$SSH_KEY -p \$SSH_PORT \$SERVER_USER@\$SERVER_HOST
        ;;
    *)
        echo "Usage: \$0 {setup|deploy|rollback|status|logs|ssh} [branch]"
        echo ""
        echo "Commands:"
        echo "  setup     - Initial server setup"
        echo "  deploy    - Deploy application (optionally specify branch)"
        echo "  rollback  - Rollback to previous release"
        echo "  status    - Show deployment status"
        echo "  logs      - Show application logs"
        echo "  ssh       - Connect to server via SSH"
        exit 1
        ;;
esac

success "✅ Operation completed!"
EOF

# Make scripts executable
chmod +x deployment-scripts/*.sh

# Create GitHub Actions workflow
mkdir -p .github/workflows
cat > .github/workflows/deploy.yml << EOF
name: Deploy Stock 360

on:
  push:
    branches: [ main, production ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
        
    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: '18'
        cache: 'npm'
        
    - name: Install PHP dependencies
      run: composer install --no-dev --optimize-autoloader --no-interaction
      
    - name: Install Node dependencies
      run: npm ci
      
    - name: Build assets
      run: npm run build
      
    - name: Run tests
      run: php artisan test
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v1.0.0
      with:
        host: \${{ secrets.HOST }}
        username: \${{ secrets.USERNAME }}
        key: \${{ secrets.SSH_PRIVATE_KEY }}
        port: \${{ secrets.PORT }}
        script: |
          cd /home/\${{ secrets.USERNAME }}/scripts
          ./deploy.sh main
EOF

# Display SSH public key
echo ""
log "🔑 Your SSH public key (add this to your server):"
echo -e "${YELLOW}"
cat "${SSH_KEY_PATH}.pub"
echo -e "${NC}"

# Create instructions file
cat > DEPLOYMENT_INSTRUCTIONS.md << EOF
# Stock 360 - SSH Deployment Instructions

## Quick Start

Your deployment environment has been configured! Here's how to use it:

### 1. Add SSH Key to Server
Copy your SSH public key to the server:
\`\`\`bash
ssh-copy-id -i ${SSH_KEY_PATH}.pub ${SERVER_USER}@${SERVER_HOST}
\`\`\`

Or manually add this key to your server's ~/.ssh/authorized_keys:
\`\`\`
$(cat "${SSH_KEY_PATH}.pub")
\`\`\`

### 2. Initial Server Setup
\`\`\`bash
cd deployment-scripts
./deploy-local.sh setup
\`\`\`

### 3. Deploy Your Application
\`\`\`bash
./deploy-local.sh deploy
\`\`\`

### 4. Monitor and Manage
\`\`\`bash
./deploy-local.sh status    # Check deployment status
./deploy-local.sh logs      # View application logs
./deploy-local.sh rollback  # Rollback if needed
./deploy-local.sh ssh       # Connect to server
\`\`\`

## Server Configuration

- **Host**: ${SERVER_HOST}
- **User**: ${SERVER_USER}
- **Port**: ${SSH_PORT}
- **Domain**: ${DOMAIN_NAME}
- **Database**: ${DB_NAME}

## GitHub Actions Setup

To enable automatic deployments, add these secrets to your GitHub repository:

1. Go to Settings → Secrets and variables → Actions
2. Add these repository secrets:
   - \`HOST\`: ${SERVER_HOST}
   - \`USERNAME\`: ${SERVER_USER}
   - \`SSH_PRIVATE_KEY\`: (content of ${SSH_KEY_PATH})
   - \`PORT\`: ${SSH_PORT}

## Files Created

- \`deployment-scripts/setup-server.sh\` - Server initialization
- \`deployment-scripts/deploy.sh\` - Main deployment script
- \`deployment-scripts/rollback.sh\` - Rollback script
- \`deployment-scripts/deploy-local.sh\` - Local management script
- \`.github/workflows/deploy.yml\` - GitHub Actions workflow

## Next Steps

1. Add your SSH key to the server
2. Run the initial setup: \`./deployment-scripts/deploy-local.sh setup\`
3. Deploy your application: \`./deployment-scripts/deploy-local.sh deploy\`
4. Configure GitHub Actions (optional)

For detailed documentation, see SSH_DEPLOYMENT_GUIDE.md
EOF

# Final success message
echo ""
success "🎉 SSH deployment setup completed!"
echo ""
info "📁 Files created:"
info "   • deployment-scripts/ - All deployment scripts"
info "   • .github/workflows/deploy.yml - GitHub Actions workflow"
info "   • DEPLOYMENT_INSTRUCTIONS.md - Quick start guide"
echo ""
info "📝 Next steps:"
info "   1. Add your SSH key to the server (see instructions above)"
info "   2. Run: ./deployment-scripts/deploy-local.sh setup"
info "   3. Run: ./deployment-scripts/deploy-local.sh deploy"
echo ""
warning "⚠️  Remember to:"
warning "   • Review and update the .env configuration on the server"
warning "   • Set up your database and run migrations"
warning "   • Configure your domain's DNS to point to the server"
echo ""
success "🚀 Happy deploying!" 