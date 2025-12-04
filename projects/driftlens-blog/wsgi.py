"""
WSGI configuration for DriftLens Blog on PythonAnywhere
Place this file in your PythonAnywhere WSGI configuration
"""

import sys
import os

# Add your project directory to the path
# UPDATE THIS PATH with your actual PythonAnywhere username
path = '/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog'
if path not in sys.path:
    sys.path.insert(0, path)

# Change to your project directory
os.chdir(path)

# Import your Flask app
from app import app as application

if __name__ == "__main__":
    application.run()

