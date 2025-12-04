"""
Database Migration Script
Adds related_id and related_type columns to Notification table
"""
import sqlite3
import os

# List of possible database paths to check
db_paths = ['driftlens.db', 'instance/driftlens.db']

# Find all existing databases
existing_dbs = [path for path in db_paths if os.path.exists(path)]

if not existing_dbs:
    print("No databases found in expected locations!")
    exit(1)

print(f"Found {len(existing_dbs)} database(s) to migrate:\n")

# Migrate each database
for db_path in existing_dbs:
    print(f"Migrating database: {db_path}")
    print("-" * 50)
    
    # Connect to database
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    
    # Check if columns already exist
    cursor.execute("PRAGMA table_info(notification)")
    columns = [column[1] for column in cursor.fetchall()]
    
    print(f"Current columns in notification table: {columns}")
    
    # Add new columns if they don't exist
    try:
        if 'related_id' not in columns:
            cursor.execute("ALTER TABLE notification ADD COLUMN related_id INTEGER")
            print("[OK] Added column: related_id")
        else:
            print("[OK] Column 'related_id' already exists")
        
        if 'related_type' not in columns:
            cursor.execute("ALTER TABLE notification ADD COLUMN related_type VARCHAR(50)")
            print("[OK] Added column: related_type")
        else:
            print("[OK] Column 'related_type' already exists")
        
        # Commit changes
        conn.commit()
        print(f"[OK] Database {db_path} migration completed successfully!\n")
        
    except Exception as e:
        conn.rollback()
        print(f"[ERROR] Error during migration of {db_path}: {e}\n")
        
    finally:
        conn.close()

print("All database migrations completed!")

