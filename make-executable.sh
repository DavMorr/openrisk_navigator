#!/bin/bash

# Make scripts executable
chmod +x pre-install-dependencies.sh
chmod +x post-install-setup.sh

echo "Scripts are now executable"
echo "Run: ./pre-install-dependencies.sh"
echo "Then: drush en openrisk_navigator -y" 
echo "Finally: ./post-install-setup.sh"
