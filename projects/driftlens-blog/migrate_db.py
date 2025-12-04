"""
Database Migration Script
Adds new columns to User table: display_name, bio, profile_picture
"""
import sqlite3
import os

# Path to database (check both locations)
db_path = 'instance/driftlens.db' if os.path.exists('instance/driftlens.db') else 'driftlens.db'

if not os.path.exists(db_path):
    print(f"Database {db_path} not found!")
    exit(1)

# Connect to database
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# Check if columns already exist
cursor.execute("PRAGMA table_info(user)")
columns = [column[1] for column in cursor.fetchall()]

print(f"Current columns in user table: {columns}")

# Add new columns if they don't exist
try:
    if 'display_name' not in columns:
        cursor.execute("ALTER TABLE user ADD COLUMN display_name VARCHAR(100)")
        print("Added column: display_name")
    else:
        print("Column 'display_name' already exists")
    
    if 'bio' not in columns:
        cursor.execute("ALTER TABLE user ADD COLUMN bio TEXT")
        print("Added column: bio")
    else:
        print("Column 'bio' already exists")
    
    if 'profile_picture' not in columns:
        cursor.execute("ALTER TABLE user ADD COLUMN profile_picture VARCHAR(255)")
        print("Added column: profile_picture")
    else:
        print("Column 'profile_picture' already exists")
    
    # Commit changes
    conn.commit()
    print("\nDatabase migration completed successfully!")
    
except Exception as e:
    conn.rollback()
    print(f"\nError during migration: {e}")
    
finally:
    conn.close()

