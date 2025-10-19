# Render Deployment Guide

Deploy Query Miner to Render for free with automatic SSL and GitHub integration.

## Prerequisites

- GitHub account
- Render account (sign up at https://render.com with GitHub)
- Google Custom Search API credentials

## Deployment Steps

### 1. Push to GitHub

Ensure your code is pushed to GitHub:

```bash
git add .
git commit -m "Prepare for Render deployment"
git push origin master
```

### 2. Deploy on Render

1. Go to https://render.com/dashboard
2. Click **"New +"** → **"Blueprint"**
3. Connect your GitHub account if not already connected
4. Select the **query-miner** repository
5. Render will automatically detect `render.yaml`
6. Click **"Apply"**

### 3. Configure Environment Variables

After the blueprint is applied, you need to set the required secrets:

1. Go to your service in Render dashboard
2. Click **"Environment"** tab
3. Add the following variables:
   - `GOOGLE_API_KEY`: Your Google Custom Search API key
   - `GOOGLE_CX`: Your Google Custom Search Engine ID

### 4. Wait for Deployment

- First deployment takes 5-10 minutes
- Render will build the Docker image
- You'll get a URL like: `https://query-miner.onrender.com`

### 5. Access Your Application

Visit your Render URL to see the application live.

## What Happens During Deployment

The `render.yaml` blueprint configures:

1. **Docker Build**: Uses `Dockerfile.render` to build the app
2. **Environment**: Sets production Laravel environment variables
3. **Dependencies**: Installs Composer and npm packages
4. **Assets**: Builds frontend assets with Vite
5. **Optimization**: Caches Laravel configuration, routes, and views
6. **Server**: Starts Laravel's built-in server on Render's port

## Render Features

- **Free Tier**: 750 hours/month (enough for continuous running)
- **Automatic SSL**: HTTPS enabled by default
- **Auto-deploys**: Pushes to master branch trigger automatic deployment
- **Logs**: View real-time logs in the dashboard
- **Custom Domains**: Add your own domain (optional)

## Limitations (Free Tier)

- Service spins down after 15 minutes of inactivity
- Takes ~30 seconds to wake up on first request
- 512 MB RAM limit
- Shared CPU

## Troubleshooting

### Build Fails

Check the build logs in Render dashboard:
- Verify `Dockerfile.render` exists
- Ensure all dependencies are in `composer.json` and `package.json`
- Check for PHP version compatibility

### Application Error (500)

1. View logs in Render dashboard
2. Verify environment variables are set:
   - `GOOGLE_API_KEY`
   - `GOOGLE_CX`
3. Check if APP_KEY was generated (automatic via entrypoint script)

### Service Won't Start

- Ensure port is correctly configured (Render provides `$PORT` env var)
- Check `docker-entrypoint-render.sh` is executable
- Verify PHP 8.2 compatibility

## Updating the Application

1. Make changes locally
2. Commit and push to GitHub:
```bash
git add .
git commit -m "Your changes"
git push origin master
```
3. Render automatically rebuilds and redeploys

## Cost

Render's free tier includes:
- 750 hours/month execution time
- Perfect for hobby projects and demos
- No credit card required

For production applications with guaranteed uptime, upgrade to:
- **Starter**: $7/month (no sleep, 512 MB RAM)
- **Standard**: $25/month (1 GB RAM, better performance)

## Support

- Documentation: https://docs.render.com
- Community: https://community.render.com
- Status: https://status.render.com
