#!/bin/bash

# Make sure we're in the plugin directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Installing All The Hooks plugin dependencies..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Composer is not installed. Please install Composer first."
    echo "Visit https://getcomposer.org/download/ for installation instructions."
    exit 1
fi

# Install dependencies
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "Dependencies installed successfully!"
    echo "You can now activate the plugin in WordPress."
else
    echo "Failed to install dependencies. Please check error messages above."
    exit 1
fi

# Set correct permissions
echo "Setting correct file permissions..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod +x install.sh

echo "All done! The plugin is ready to use."
echo "Activate it in WordPress and run: wp all-the-hooks scan --plugin=<plugin-slug>" 