#!/bin/bash

# Smart Resto POS - Railway Deployment Script
# Usage: ./deploy.sh

set -e

echo "🚂 Smart Resto POS - Railway Deployment Script"
echo "=============================================="
echo ""

# Check if Railway CLI is installed
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI not found!"
    echo "📦 Installing Railway CLI..."
    npm i -g @railway/cli
fi

# Check if git is initialized
if [ ! -d .git ]; then
    echo "📦 Initializing Git repository..."
    git init
    git add .
    git commit -m "Initial commit - Smart Resto POS"
fi

# Login to Railway
echo "🔐 Logging in to Railway..."
railway login

# Check if project is linked
if ! railway status &> /dev/null; then
    echo "🆕 Creating new Railway project..."
    railway init
    
    echo "🗄️ Adding MySQL database..."
    railway add --plugin mysql
    
    echo "⏳ Waiting for MySQL to be ready (30 seconds)..."
    sleep 30
else
    echo "✅ Project already linked"
fi

# Deploy application
echo "🚀 Deploying application..."
railway up

# Wait for deployment
echo "⏳ Waiting for deployment to complete (15 seconds)..."
sleep 15

# Check if database schema needs to be imported
echo "🗄️ Do you want to import database schema? (y/n)"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "📥 Importing database schema..."
    railway run mysql -u root -p railway < "Database Schema.sql"
    echo "✅ Database schema imported successfully!"
fi

# Show environment variables
echo ""
echo "🔧 Environment Variables:"
railway variables

# Show deployment URL
echo ""
echo "🌐 Application URL:"
railway domain

# Open in browser
echo ""
echo "🎉 Deployment complete!"
echo "📱 Opening application in browser..."
railway open

echo ""
echo "==============================================="
echo "✅ Deployment successful!"
echo "📚 Default Login:"
echo "   Username: admin"
echo "   Password: password"
echo ""
echo "⚠️  IMPORTANT: Change default password after login!"
echo "==============================================="
