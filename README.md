# 🍽️ Smart Resto POS - Railway Deployment Guide

## 🚀 Quick Deploy to Railway

### Prerequisites
- Git installed
- Railway CLI installed: `npm i -g @railway/cli`
- Railway account (free at https://railway.app)

---

## 📦 Step 1: Prepare Repository

```bash
# Initialize git (if not already)
git init

# Add all files
git add .

# First commit
git commit -m "Initial commit - Smart Resto POS"
```

---

## 🚂 Step 2: Deploy to Railway

### Option A: Via Railway CLI (Recommended)

```bash
# Login to Railway
railway login

# Create new project
railway init

# Link to project
railway link

# Add MySQL database
railway add --plugin mysql

# Deploy application
railway up

# Open in browser
railway open
```

### Option B: Via GitHub

1. Push repository ke GitHub
2. Login ke https://railway.app
3. Click "New Project" → "Deploy from GitHub repo"
4. Select your repository
5. Railway will auto-detect PHP project

---

## 🗄️ Step 3: Setup Database

### Import Database Schema

```bash
# Get Railway MySQL credentials
railway variables

# Connect to MySQL
railway connect mysql

# Or import via MySQL client
mysql -h <MYSQL_HOST> \
      -P <MYSQL_PORT> \
      -u <MYSQL_USER> \
      -p<MYSQL_PASSWORD> \
      <MYSQL_DATABASE> < "Database Schema.sql"
```

### Via Railway CLI (Easiest)

```bash
# Import directly
railway run mysql -u root -p railway < "Database Schema.sql"
```

---

## 🔐 Step 4: Environment Variables (Auto-Set by Railway)

Railway akan otomatis set variables ini saat MySQL plugin ditambahkan:

```bash
MYSQL_HOST=containers-us-west-xxx.railway.app
MYSQL_PORT=7431
MYSQL_USER=root
MYSQL_PASSWORD=xxx-auto-generated-xxx
MYSQL_DATABASE=railway
RAILWAY_PUBLIC_DOMAIN=your-app-production.up.railway.app
RAILWAY_ENVIRONMENT=production
```

---

## ✅ Step 5: Verify Deployment

```bash
# Check logs
railway logs

# Check status
railway status

# Test database connection
railway run php -r "include 'config.php'; echo 'DB Connected!';"

# Open application
railway open
```

---

## 🔑 Default Login Credentials

- **Username**: `admin`
- **Password**: `password`

⚠️ **IMPORTANT**: Change password immediately after first login!

---

## 📊 Railway Commands Cheat Sheet

```bash
railway login              # Login to Railway
railway init               # Initialize new project
railway link               # Link to existing project
railway up                 # Deploy application
railway logs               # View logs
railway logs --follow      # Live logs
railway variables          # List environment variables
railway variables set KEY=value  # Set variable
railway connect mysql      # Connect to MySQL
railway run <command>      # Run command
railway status             # Check project status
railway open               # Open in browser
railway domain             # Manage custom domains
```

---

## 🐛 Troubleshooting

### Database Connection Error

```bash
# Check environment variables
railway variables

# Test connection
railway run php -r "
  echo 'Host: ' . getenv('MYSQL_HOST') . PHP_EOL;
  echo 'Port: ' . getenv('MYSQL_PORT') . PHP_EOL;
  echo 'Database: ' . getenv('MYSQL_DATABASE') . PHP_EOL;
  echo 'User: ' . getenv('MYSQL_USER') . PHP_EOL;
"

# Check logs for errors
railway logs
```

### Application Not Starting

```bash
# Check deployment logs
railway logs

# Verify Procfile exists
cat Procfile

# Re-deploy
git add .
git commit -m "Fix deployment"
railway up
```

### Session Issues

Railway menggunakan ephemeral file system. Session disimpan di `/tmp` dan akan hilang saat redeploy. Untuk production, pertimbangkan:

1. Database sessions
2. Redis/Memcached
3. Sticky sessions (Railway Enterprise)

---

## 💰 Estimated Monthly Cost

- **Free Tier**: $5 credit (~500 execution hours)
- **Hobby Plan**: $10/month (200GB bandwidth, 8GB RAM)
- **PHP App**: ~$5-10/month
- **MySQL Database**: ~$5-10/month
- **Total**: $10-20/month for small restaurant

---

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Set strong MySQL password
- [ ] Enable HTTPS (auto by Railway)
- [ ] Review user permissions
- [ ] Setup database backups
- [ ] Enable Railway project access controls
- [ ] Review API endpoints security

---

## 📞 Support

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- GitHub Issues: [Your repo URL]

---

## 📝 License

Proprietary - Smart Resto POS © 2025
