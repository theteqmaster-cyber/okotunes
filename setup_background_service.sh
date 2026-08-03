#!/bin/bash
set -e

echo "🔒 Allowing port 8080 through the firewall..."
sudo ufw allow 8080/tcp

echo "⚙️ Installing systemd service..."
sudo cp /home/mphatic/Desktop/mspot/mspot.service /etc/systemd/system/mspot.service

echo "🔄 Reloading systemd daemon..."
sudo systemctl daemon-reload

echo "🚀 Enabling MSpot service to start on boot..."
sudo systemctl enable mspot.service

echo "🛑 Stopping any manually running MSpot server instances..."
pkill -f "php -S 0.0.0.0:8080" || true

echo "🟢 Starting the MSpot background service..."
sudo systemctl restart mspot.service

echo "📊 Checking service status..."
sudo systemctl status mspot.service
